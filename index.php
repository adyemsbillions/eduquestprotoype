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

    public function generateReferralCode($length = 8)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE referral_code = ?");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result->num_rows > 0);
        return $code;
    }

    public function handleSignup($postData)
    {
        if (!$this->validateCsrf($postData['csrf_token'])) {
            $this->errors[] = "Security token mismatch.";
            return false;
        }

        $username = filter_var($postData['username'], FILTER_SANITIZE_STRING);
        $email = filter_var($postData['email'], FILTER_SANITIZE_EMAIL);
        $password = $postData['password'];

        if (preg_match('/\s/', $username)) {
            $this->errors[] = "Username cannot contain spaces.";
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Invalid email format.";
            return false;
        }

        if ($this->checkExistingUser($username, $email)) {
            return false;
        }

        $referral_code = $this->generateReferralCode();
        $referred_by = $this->getReferredBy();
        $verification_token = bin2hex(random_bytes(32));
        $token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->conn->prepare(
            "INSERT INTO users (username, email, password, referral_code, referred_by, verification_token, token_expires) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssssss",
            $username,
            $email,
            $password_hash,
            $referral_code,
            $referred_by,
            $verification_token,
            $token_expires
        );

        if ($stmt->execute()) {
            $verification_link = "http://unimaidresources.com.ng/verify_email.php?token=$verification_token";
            $email_body = '
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Email Verification</title>
                </head>
                <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <tr>
                            <td style="background-color: #7d2ae8; padding: 20px; text-align: center; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Welcome to UNIMAID Resources!</h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 30px; text-align: center;">
                                <h2 style="color: #333333; font-size: 20px; margin-bottom: 20px;">Verify Your Email Address</h2>
                                <p style="color: #666666; font-size: 16px; line-height: 1.5; margin-bottom: 20px;">
                                    Thank you for joining UNIMAID Resources! To complete your registration, please verify your email address by clicking the button below.
                                </p>
                                <a href="' . $verification_link . '" style="display: inline-block; padding: 12px 24px; background-color: #7d2ae8; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 5px; margin-bottom: 20px;">Verify Email</a>
                                <p style="color: #666666; font-size: 14px; line-height: 1.5;">
                                    If the button doesn’t work, copy and paste this link into your browser:<br>
                                    <a href="' . $verification_link . '" style="color: #7d2ae8; text-decoration: underline;">' . $verification_link . '</a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: #f9f9f9; padding: 20px; text-align: center; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                                <p style="color: #666666; font-size: 12px; margin: 0;">
                                    If you didn’t create an account, please ignore this email.<br>
                                    © ' . date('Y') . ' UNIMAID Resources. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>
            ';

            if ($this->sendEmail($email, "Verify Your Email for UNIMAID Resources", $email_body)) {
                return ['status' => 'success', 'message' => 'email_sent', 'email' => $email];
            } else {
                $_SESSION['pending_verification_email'] = $email;
                $_SESSION['pending_verification_token'] = $verification_token;
                $email_error = $this->errors[count($this->errors) - 1] ?? 'Unknown email sending error';
                return ['status' => 'success', 'message' => 'email_failed', 'error' => $email_error];
            }
        } else {
            $this->errors[] = "Database error: " . $this->conn->error;
            error_log("Signup database error: " . $this->conn->error);
            return false;
        }
    }

    public function handleLogin($postData)
    {
        if (!$this->validateCsrf($postData['csrf_token'])) {
            $this->errors[] = "Security token mismatch.";
            return false;
        }

        $loginInput = $this->conn->real_escape_string($postData['email']);
        $password = $postData['password'];

        $stmt = $this->conn->prepare("SELECT id, username, email, password, email_verified FROM users WHERE email = ? OR username = ?");
        if (!$stmt) {
            $this->errors[] = "Failed to prepare user fetch statement: " . $this->conn->error;
            error_log("User fetch prepare error: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("ss", $loginInput, $loginInput);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if (!$user['email_verified']) {
                    $this->errors[] = "Please verify your email address first. Check your inbox.";
                    return false;
                }

                $_SESSION['username'] = $user['username'];
                $_SESSION['profile_picture'] = null;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['login_time'] = time();

                $login_method = isset($_GET['ref']) ? 'referral_link' : 'email';

                // Use non-prepared statement to avoid prepared statement cache issues
                $login_method_escaped = $this->conn->real_escape_string($login_method);
                $user_id_escaped = (int)$user['id'];
                $query = "UPDATE users SET last_login = NOW(), login_count = login_count + 1, login_method = '$login_method_escaped' WHERE id = $user_id_escaped";
                if ($this->conn->query($query)) {
                    error_log("Login stats updated successfully for user ID: $user_id_escaped");
                } else {
                    $this->errors[] = "Error updating login stats: " . $this->conn->error;
                    error_log("Login stats update error: " . $this->conn->error . " (Error Code: " . $this->conn->errno . ")");
                    return false;
                }

                return true;
            } else {
                $this->errors[] = "Invalid credentials.";
                return false;
            }
        } else {
            $this->errors[] = "No account found with that username or email.";
            return false;
        }
    }

    private function validateCsrf($token)
    {
        return isset($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function checkExistingUser($username, $email)
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $this->errors[] = "Username or email already exists.";
            return true;
        }
        return false;
    }

    private function getReferredBy()
    {
        if (!isset($_GET['ref'])) {
            return null;
        }

        $ref = $this->conn->real_escape_string($_GET['ref']);
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0 ? $result->fetch_assoc()['id'] : null;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}

// Session timeout check
$timeout = 5 * 60 * 60; // 5 hours
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) >= $timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?session_expired=1");
    exit();
}

// Initialize AuthHandler
$auth = new AuthHandler($conn);

// Handle signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $signupResult = $auth->handleSignup($_POST);
    if ($signupResult) {
        $redirect_url = "index.php?signup=success&verify=" . $signupResult['message'];
        if ($signupResult['message'] === 'email_failed' && isset($signupResult['error'])) {
            $redirect_url .= '&error=' . urlencode($signupResult['error'] . ' Please check your spam folder or contact support.');
        }
        if ($signupResult['message'] === 'email_sent' && isset($signupResult['email'])) {
            $redirect_url .= '&email=' . urlencode($signupResult['email']);
        }
        header("Location: $redirect_url");
        exit();
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($auth->handleLogin($_POST)) {
        $redirect = isset($_SESSION['redirect_reel'])
            ? "dashboard/reels.php?reel=" . $_SESSION['redirect_reel']
            : "dashboard/checker.php";
        unset($_SESSION['redirect_reel']);
        header("Location: $redirect");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title>Login and Registration | UNIMAID Resources</title>
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
        max-width: 850px;
        width: 100%;
        background: #fff;
        padding: 40px 30px;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        perspective: 2700px;
    }

    .container .cover {
        position: absolute;
        top: 0;
        left: 50%;
        height: 100%;
        width: 50%;
        z-index: 98;
        transition: all 1s ease;
        transform-origin: left;
        transform-style: preserve-3d;
        backface-visibility: hidden;
    }

    .container #flip:checked~.cover {
        transform: rotateY(-180deg);
    }

    .container #flip:checked~.forms .login-form {
        pointer-events: none;
    }

    .container .cover .front,
    .container .cover .back {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
    }

    .cover .back {
        transform: rotateY(180deg);
    }

    .container .cover img {
        position: absolute;
        height: 100%;
        width: 100%;
        object-fit: cover;
        z-index: 10;
    }

    .container .cover .text {
        position: absolute;
        z-index: 10;
        height: 100%;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .container .cover .text::before {
        content: '';
        position: absolute;
        height: 100%;
        width: 100%;
        opacity: 0.5;
        background: #7d2ae8;
    }

    .cover .text .text-1,
    .cover .text .text-2 {
        z-index: 20;
        font-size: 26px;
        font-weight: 600;
        color: #fff;
        text-align: center;
    }

    .cover .text .text-2 {
        font-size: 15px;
        font-weight: 500;
    }

    .container .forms {
        height: 100%;
        width: 100%;
    }

    .container .form-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .form-content .login-form,
    .form-content .signup-form {
        width: calc(100% / 2 - 25px);
    }

    .forms .form-content .title {
        position: relative;
        font-size: 24px;
        font-weight: 500;
        color: #333;
    }

    .forms .form-content .title:before {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        height: 3px;
        width: 25px;
        background: #7d2ae8;
    }

    .forms .signup-form .title:before {
        width: 20px;
    }

    .forms .form-content .input-boxes {
        margin-top: 30px;
    }

    .forms .form-content .input-box {
        display: flex;
        align-items: center;
        height: 50px;
        width: 100%;
        margin: 10px 0;
        position: relative;
    }

    .form-content .input-box input {
        height: 100%;
        width: 100%;
        outline: none;
        border: none;
        padding: 0 30px;
        font-size: 16px;
        font-weight: 500;
        border-bottom: 2px solid rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .form-content .input-box input:focus,
    .form-content .input-box input:valid {
        border-color: #7d2ae8;
    }

    .form-content .input-box i {
        position: absolute;
        color: #7d2ae8;
        font-size: 17px;
    }

    .eye-icon {
        right: 10px;
        cursor: pointer;
        font-size: 16px;
    }

    .forms .form-content .text {
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }

    .forms .form-content .text a {
        text-decoration: none;
    }

    .forms .form-content .text a:hover {
        text-decoration: underline;
    }

    .forms .form-content .button {
        color: #fff;
        margin-top: 40px;
        position: relative;
    }

    .forms .form-content .button input {
        color: #fff;
        background: #7d2ae8;
        border-radius: 6px;
        padding: 0;
        cursor: pointer;
        transition: all 0.4s ease;
    }

    .forms .form-content .button input:hover {
        background: #5b13b9;
    }

    .forms .form-content label {
        color: #5b13b9;
        cursor: pointer;
    }

    .forms .form-content label:hover {
        text-decoration: underline;
    }

    .forms .form-content .login-text,
    .forms .form-content .sign-up-text {
        text-align: center;
        margin-top: 25px;
    }

    .container #flip {
        display: none;
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
        .container .cover {
            display: none;
        }

        .form-content .login-form,
        .form-content .signup-form {
            width: 100%;
        }

        .form-content .signup-form {
            display: none;
        }

        .container #flip:checked~.forms .signup-form {
            display: block;
        }

        .container #flip:checked~.forms .login-form {
            display: none;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <input type="checkbox" id="flip">
        <div class="cover">
            <div class="front">
                <img src="images/student.jpg" alt="">
                <div class="text">
                    <span class="text-1">Education no suppose <br>HARD</span>
                    <span class="text-2">Let's get connected</span>
                </div>
            </div>
            <div class="back">
                <img class="backImg" src="images/fstudent.jpg" alt="">
                <div class="text">
                    <span class="text-1">Complete miles of journey <br> with one step</span>
                    <span class="text-2">Let's get started</span>
                </div>
            </div>
        </div>
        <div class="forms">
            <div class="form-content">
                <div class="login-form">
                    <div class="title">Login</div>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="input-boxes">
                            <div class="input-box">
                                <i class="fas fa-envelope"></i>
                                <input type="text" name="email" placeholder="Enter your username or email" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="login-password" name="password"
                                    placeholder="Enter your password" required>
                                <i class="fas fa-eye eye-icon" id="login-eye"></i>
                            </div>
                            <?php if (!empty($auth->getErrors())): ?>
                            <p style="color: red;">
                                <?php echo implode('<br>', $auth->getErrors()); ?>
                                <?php if (in_array("Please verify your email address first. Check your inbox.", $auth->getErrors())): ?>
                                <br><a
                                    href="resend_verification.php?email=<?= urlencode($_POST['email'] ?? '') ?>">Resend
                                    verification email</a>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                            <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
                            <?php if (isset($_GET['verify']) && $_GET['verify'] === 'email_sent'): ?>
                            <p style="color: green;">Account created successfully. Verification email sent to
                                <strong><?php echo isset($_GET['email']) ? htmlspecialchars(urldecode($_GET['email'])) : 'your email'; ?></strong>.
                                Please check your inbox.
                            </p>
                            <?php elseif (isset($_GET['verify']) && $_GET['verify'] === 'email_failed'): ?>
                            <p style="color: orange;">Account created but failed to send verification email:
                                <?php echo isset($_GET['error']) ? htmlspecialchars(urldecode($_GET['error'])) : 'Unknown error occurred'; ?>
                            </p>
                            <?php else: ?>
                            <p style="color: green;">Account created successfully. Please log in.</p>
                            <?php endif; ?>
                            <?php endif; ?>
                            <div class="text"><a href="forgot_password.php">Forgot password?</a></div>
                            <div class="button input-box">
                                <input type="submit" name="login" value="Login">
                                <span class="loader"></span>
                            </div>
                            <div class="text sign-up-text">Don't have an account? <label for="flip">Signup now</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="signup-form">
                    <div class="title">Signup</div>
                    <form method="POST"
                        action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . (isset($_GET['ref']) ? '?ref=' . $_GET['ref'] : ''); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="input-boxes">
                            <div class="input-box">
                                <i class="fas fa-user"></i>
                                <input type="text" name="username" placeholder="Enter your username" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" placeholder="Enter your email" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="signup-password" name="password"
                                    placeholder="Enter your password" required>
                                <i class="fas fa-eye eye-icon" id="signup-eye"></i>
                            </div>
                            <?php if (!empty($auth->getErrors())): ?>
                            <p style="color: red;"><?php echo implode('<br>', $auth->getErrors()); ?></p>
                            <?php endif; ?>
                            <?php if (isset($_GET['ref'])): ?>
                            <p style="color: #333;">Referred by: <?php echo htmlspecialchars($_GET['ref']); ?></p>
                            <?php endif; ?>
                            <div class="button input-box">
                                <input type="submit" name="signup" value="Register">
                                <span class="loader"></span>
                            </div>
                            <div class="text sign-up-text">Already have an account? <label for="flip">Login now</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Password toggle visibility
    document.getElementById('login-eye').addEventListener('click', function() {
        const passwordField = document.getElementById('login-password');
        const eyeIcon = document.getElementById('login-eye');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });

    document.getElementById('signup-eye').addEventListener('click', function() {
        const passwordField = document.getElementById('signup-password');
        const eyeIcon = document.getElementById('signup-eye');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });

    // Form submission loading indicators
    document.querySelector('.login-form form').addEventListener('submit', function(e) {
        const button = this.querySelector('.button');
        if (button) button.classList.add('loading');
    });

    document.querySelector('.signup-form form').addEventListener('submit', function(e) {
        const button = this.querySelector('.button');
        if (button) button.classList.add('loading');
    });

    // Register the Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then((registration) => {
                    console.log('Service Worker registered:', registration);
                })
                .catch((error) => {
                    console.error('Service Worker registration failed:', error);
                });
        });
    }
    </script>
</body>

</html>
<?php
$conn->close();
?>