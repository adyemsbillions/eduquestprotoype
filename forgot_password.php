<?php
session_start();

// Database connection
require_once 'db_connection.php';

// Include PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

class AuthHandler
{
    private $conn;
    private $mailer;
    private $errors = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }

    private function setupMailer()
    {
    try {
            $this->mailer->isSMTP();
            $this->mailer->SMTPDebug = 0; // Set to 2 for debugging
            $this->mailer->Host = 'mail.unimaidresources.com.ng'; // Use Gmail SMTP
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'info@unimaidresources.com.ng'; // Replace with your Gmail address
            $this->mailer->Password = 'unimaid9_unimaidresources'; // Replace with Gmail App Password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            $this->mailer->setFrom('info@unimaidresources.com.ng', 'UNIMAID Resources');
        } catch (Exception $e) {
            $this->errors[] = "Mailer setup failed: " . $e->getMessage();
            error_log("Mailer setup error: " . $e->getMessage());
        }
    }

    public function sendEmail($to, $subject, $body)
    {
        try {
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            $errorMessage = "Failed to send email: " . $e->getMessage();
            $this->errors[] = $errorMessage;
            error_log("Email sending error to $to: " . $e->getMessage());
            return false;
        } finally {
            $this->mailer->clearAddresses();
        }
    }

    public function handleForgotPassword($postData)
    {
        if (!$this->validateCsrf($postData['csrf_token'])) {
            $this->errors[] = "Security token mismatch.";
            return false;
        }

        // Rate limiting
        if (!isset($_SESSION['reset_attempts'])) {
            $_SESSION['reset_attempts'] = 0;
            $_SESSION['last_reset_request'] = time();
        }

        if ($_SESSION['reset_attempts'] >= 10 && (time() - $_SESSION['last_reset_request']) < 3600) {
            $this->errors[] = "Too many reset attempts. Please try again in an hour.";
            return false;
        }

        $_SESSION['reset_attempts']++;
        $_SESSION['last_reset_request'] = time();

        // Escape input to prevent SQL injection
        $email = $this->conn->real_escape_string($postData['email']);
        $query = "SELECT id FROM users WHERE email = '$email'";
        error_log("Executing query: $query");
        $result = $this->conn->query($query);

        if ($result === false) {
            $this->errors[] = "Database error: " . $this->conn->error;
            error_log("SELECT query error: " . $this->conn->error);
            return false;
        }

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $reset_token = bin2hex(random_bytes(32));
            $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Escape values for the UPDATE query
            $reset_token_escaped = $this->conn->real_escape_string($reset_token);
            $reset_expires_escaped = $this->conn->real_escape_string($reset_expires);
            $user_id = (int)$user['id'];
            $query = "UPDATE users SET reset_token = '$reset_token_escaped', reset_expires = '$reset_expires_escaped' WHERE id = $user_id";
            error_log("Executing query: $query");
            $update_result = $this->conn->query($query);

            if ($update_result === false) {
                $this->errors[] = "Database error: " . $this->conn->error;
                error_log("UPDATE query error: " . $this->conn->error);
                return false;
            }

            $reset_link = "http://unimaidresources.com.ng/reset_password.php?token=$reset_token";
            $email_body = '
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Password Reset</title>
                </head>
                <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <tr>
                            <td style="background-color: #7d2ae8; padding: 20px; text-align: center; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Password Reset Request</h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 30px; text-align: center;">
                                <h2 style="color: #333333; font-size: 20px; margin-bottom: 20px;">Reset Your Password</h2>
                                <p style="color: #666666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
                                    We received a request to reset your password. Click the button below to set a new password.
                                </p>
                                <a href="' . $reset_link . '" style="display: inline-block; padding: 12px 24px; background-color: #7d2ae8; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 5px; margin-bottom: 20px;">Reset Password</a>
                                <p style="color: #666666; font-size: 14px; line-height: 1.5;">
                                    If the button doesn’t work, copy and paste this link into your browser:<br>
                                    <a href="' . $reset_link . '" style="color: #7d2ae8; text-decoration: underline;">' . $reset_link . '</a>
                                </p>
                                <p style="color: #666666; font-size: 14px; line-height: 1.5;">
                                    This link will expire in 1 hour.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: #f9f9f9; padding: 20px; text-align: center; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                                <p style="color: #666666; font-size: 12px; margin: 0;">
                                    If you didn’t request a password reset, please ignore this email.<br>
                                    © ' . date('Y') . ' UNIMAID Resources. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>
            ';

            if ($this->sendEmail($email, "Password Reset Request for UNIMAID Resources", $email_body)) {
                return ['status' => 'success', 'message' => 'Password reset link sent to your email.'];
            } else {
                $this->errors[] = "Failed to send reset email.";
                return false;
            }
        } else {
            $this->errors[] = "No account found with that email.";
            return false;
        }
    }

    private function validateCsrf($token)
    {
        return isset($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}

// Initialize AuthHandler
$auth = new AuthHandler($conn);

// Handle forgot password
$forgotResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $forgotResult = $auth->handleForgotPassword($_POST);
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | UNIMAID Resources</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords"
        content="unimaid, university resources, academic tools, campus updates, student interaction, news, unimaid resources, umstad online, University of Maiduguri, UNIMAID PORTAL, Unimaid courses, UNIMAID Portal Admission, Unimaid courses and fees, Unimaid school fees">
    <meta name="description"
        content="Unimaid Resources brings ease to students by connecting them with essential academic tools, campus updates, and legitimate, up-to-date news, along with features like groups, chatting, posting, social interaction, and more, all designed to enhance their university experience.">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Poppins", sans-serif;
    }

    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #7d2ae8;
        padding: 30px;
    }

    .container {
        position: relative;
        max-width: 500px;
        width: 100%;
        background: #fff;
        padding: 40px 30px;
        box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        border-radius: 8px;
    }

    .container .title {
        position: relative;
        font-size: 24px;
        font-weight: 500;
        color: #333;
    }

    .container .title:before {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 25px;
        background: #7d2ae8;
    }

    .container .input-boxes {
        margin-top: 30px;
    }

    .container .input-box {
        display: flex;
        align-items: center;
        height: 50px;
        width: 100%;
        margin: 10px 0;
        position: relative;
    }

    .input-box input {
        height: 100%;
        width: 100%;
        outline: none;
        border: none;
        padding: 0 30px;
        font-size: 16px;
        font-weight: 500;
        border-bottom: 2px solid rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }

    .input-box input:focus,
    .input-box input:valid {
        border-color: #7d2ae8;
    }

    .input-box i {
        position: absolute;
        color: #7d2ae8;
        font-size: 17px;
    }

    .container .text {
        font-size: 14px;
        font-weight: 500;
        color: #333;
        text-align: center;
    }

    .container .text a {
        text-decoration: none;
        color: #5b13b9;
    }

    .container .text a:hover {
        text-decoration: underline;
    }

    .container .button {
        color: #fff;
        margin-top: 40px;
        position: relative;
    }

    .container .button input {
        color: #fff;
        background: #7d2ae8;
        border-radius: 6px;
        padding: 0;
        cursor: pointer;
        width: 100%;
        height: 50px;
        border: none;
        font-size: 16px;
        font-weight: 500;
        transition: all 0.4s ease;
    }

    .container .button input:hover {
        background: #5b13b9;
    }

    .loader {
        display: none;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #7d2ae8;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        animation: spin 1s linear infinite;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    @keyframes spin {
        0% {
            transform: translate(-50%, -50%) rotate(0deg);
        }

        100% {
            transform: translate(-50%, -50%) rotate(360deg);
        }
    }

    .button.loading .loader {
        display: block;
    }

    .button.loading input {
        visibility: hidden;
    }

    @media (max-width: 730px) {
        .container {
            padding: 20px;
        }

        .container .title {
            font-size: 20px;
        }

        .input-box {
            height: 45px;
            margin: 10px 0;
        }

        .button input {
            height: 45px;
            font-size: 14px;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">Forgot Password</div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="input-boxes">
                <div class="input-box">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                <?php if (!empty($auth->getErrors())): ?>
                <p style="color: red;"><?php echo implode('<br>', $auth->getErrors()); ?></p>
                <?php endif; ?>
                <?php if (isset($forgotResult) && $forgotResult): ?>
                <p style="color: green;"><?php echo $forgotResult['message']; ?></p>
                <?php endif; ?>
                <div class="button input-box">
                    <input type="submit" name="forgot_password" value="Send Reset Link">
                    <span class="loader"></span>
                </div>
                <div class="text">
                    <a href="index.php">Back to Login</a>
                </div>
            </div>
        </form>
    </div>

    <script>
    // Form submission loading indicator
    document.querySelector('form').addEventListener('submit', function(e) {
        const button = this.querySelector('.button');
        if (button) button.classList.add('loading');
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>