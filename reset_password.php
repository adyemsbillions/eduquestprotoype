<?php
session_start();

// Database connection
require_once 'db_connection.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

class AuthHandler
{
    private $conn;
    private $errors = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function handleResetPassword($postData, $token)
    {
        if (!$this->validateCsrf($postData['csrf_token'])) {
            $this->errors[] = "Security token mismatch.";
            return false;
        }

        $password = $postData['password'];
        $confirm_password = $postData['confirm_password'];

        if (strlen($password) < 8) {
            $this->errors[] = "Password must be at least 8 characters long.";
            return false;
        }

        if ($password !== $confirm_password) {
            $this->errors[] = "Passwords do not match.";
            return false;
        }

        $token_escaped = $this->conn->real_escape_string($token);
        $query = "SELECT id, reset_expires FROM users WHERE reset_token = '$token_escaped'";
        error_log("Executing query: $query");
        $result = $this->conn->query($query);

        if ($result === false) {
            $this->errors[] = "Database error: " . $this->conn->error;
            error_log("SELECT query error: " . $this->conn->error);
            return false;
        }

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $current_time = date('Y-m-d H:i:s');

            if ($user['reset_expires'] > $current_time) {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $password_hash_escaped = $this->conn->real_escape_string($password_hash);
                $user_id = (int)$user['id'];
                $query = "UPDATE users SET password = '$password_hash_escaped', reset_token = NULL, reset_expires = NULL WHERE id = $user_id";
                error_log("Executing query: $query");
                $update_result = $this->conn->query($query);

                if ($update_result === false) {
                    $this->errors[] = "Database error: " . $this->conn->error;
                    error_log("UPDATE query error: " . $this->conn->error);
                    return false;
                }

                return ['status' => 'success', 'message' => 'Password reset successfully. Please log in.'];
            } else {
                $this->errors[] = "Reset link has expired.";
                return false;
            }
        } else {
            $this->errors[] = "Invalid or expired reset link.";
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

// Validate token
$token = isset($_GET['token']) ? $conn->real_escape_string($_GET['token']) : '';
$valid_token = false;
if ($token) {
    $query = "SELECT id, reset_expires FROM users WHERE reset_token = '$token'";
    error_log("Executing query: $query");
    $result = $conn->query($query);
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $valid_token = $user['reset_expires'] > date('Y-m-d H:i:s');
    }
}

// Handle password reset
$resetResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $valid_token) {
    $resetResult = $auth->handleResetPassword($_POST, $token);
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Reset Password | UNIMAID Resources</title>
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

    .eye-icon {
        right: 10px;
        cursor: pointer;
        font-size: 16px;
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
        <div class="title">Reset Password</div>
        <?php if (!$valid_token): ?>
        <p style="color: red;">Invalid or expired reset link. Please request a new one.</p>
        <div class="text">
            <a href="forgot_password.php">Request New Reset Link</a>
        </div>
        <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="input-boxes">
                <div class="input-box">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter new password" required>
                    <i class="fas fa-eye eye-icon" id="password-eye"></i>
                </div>
                <div class="input-box">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm new password" required>
                    <i class="fas fa-eye eye-icon" id="confirm-password-eye"></i>
                </div>
                <?php if (!empty($auth->getErrors())): ?>
                <p style="color: red;"><?php echo implode('<br>', $auth->getErrors()); ?></p>
                <?php endif; ?>
                <?php if (isset($resetResult) && $resetResult): ?>
                <p style="color: green;"><?php echo $resetResult['message']; ?></p>
                <?php endif; ?>
                <div class="button input-box">
                    <input type="submit" name="reset_password" value="Reset Password">
                    <span class="loader"></span>
                </div>
                <div class="text">
                    <a href="index.php">Back to Login</a>
                </div>
            </div>
        </form>
        <?php endif; ?>

    <script>
    // Password toggle visibility
    document.getElementById('password-eye').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const eyeIcon = document.getElementById('password-eye');
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

    document.getElementById('confirm-password-eye').addEventListener('click', function() {
        const passwordField = document.getElementById('confirm-password');
        const eyeIcon = document.getElementById('confirm-password-eye');
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