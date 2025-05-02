<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function generateReferralCode($conn, $length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        $check = $conn->query("SELECT id FROM users WHERE referral_code='$code'");
    } while ($check->num_rows > 0);
    return $code;
}

// Handle signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (preg_match('/\s/', $username)) {
        $signupError = "Username cannot contain spaces.";
    } else {
        $username = $conn->real_escape_string($username);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $signupError = "Invalid email format.";
        } else {
            $email = $conn->real_escape_string($email);
            $password = password_hash($password, PASSWORD_BCRYPT);

            $checkUsername = $conn->query("SELECT * FROM users WHERE username='$username'");
            if ($checkUsername->num_rows > 0) {
                $signupError = "Username already exists.";
            } else {
                $checkEmail = $conn->query("SELECT * FROM users WHERE email='$email'");
                if ($checkEmail->num_rows > 0) {
                    $signupError = "Email already exists.";
                } else {
                    $referral_code = generateReferralCode($conn);
                    $referred_by = null;
                    if (isset($_GET['ref'])) {
                        $ref = $conn->real_escape_string($_GET['ref']);
                        $ref_check = $conn->query("SELECT id FROM users WHERE referral_code='$ref'");
                        if ($ref_check->num_rows > 0) {
                            $referred_by = $ref_check->fetch_assoc()['id'];
                        }
                    }

                    $query = "INSERT INTO users (username, email, password, referral_code, referred_by) 
                              VALUES ('$username', '$email', '$password', '$referral_code', " . ($referred_by ? "'$referred_by'" : "NULL") . ")";
                    if ($conn->query($query) === TRUE) {
                        header("Location: index.php?signup=success");
                        exit();
                    } else {
                        $signupError = "Error: " . $conn->error;
                    }
                }
            }
        }
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $loginInput = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT id, username, email, password FROM users WHERE email='$loginInput' OR username='$loginInput'";
    $result = $conn->query($query);
    if ($result === false) {
        $loginError = "Error fetching user details: " . $conn->error;
    } elseif ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_picture'] = null;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login_time'] = time();

            // Determine login method
            $login_method = isset($_GET['ref']) ? 'referral_link' : 'email';

            // Update user activity stats
            $update_query = "UPDATE users SET 
                                last_login = NOW(), 
                                login_count = login_count + 1, 
                                login_method = '$login_method' 
                             WHERE id = " . $user['id'];
            if (!$conn->query($update_query)) {
                $loginError = "Error updating login stats: " . $conn->error;
            }

            // Redirect
            $redirect = isset($_SESSION['redirect_reel']) 
                ? "dashboard/reels.php?reel=" . $_SESSION['redirect_reel']
                : "dashboard/dashboard.php";
            unset($_SESSION['redirect_reel']);
            header("Location: $redirect");
            exit();
        } else {
            $loginError = "Invalid credentials.";
        }
    } else {
        $loginError = "No account found with that username or email.";
    }
}

// Session timeout check - only redirect if expired
$timeout = 5 * 60 * 60; // 5 hours in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) >= $timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?session_expired=1");
    exit();
}
// No redirect here - let the page load normally if not logged in or session is still valid
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Login and Registration | UNIMAID Resources</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="unimaid, university resources, academic tools, campus updates, student interaction, news, unimaid resources, umstad online, University of Maiduguri, UNIMAID PORTAL, Unimaid courses, UNIMAID Portal Admission, Unimaid courses and fees, Unimaid school fees">
    <meta name="description" content="Unimaid Resources brings ease to students by connecting them with essential academic tools, campus updates, and legitimate, up-to-date news, along with features like groups, chatting, posting, social interaction, and more, all designed to enhance their university experience.">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #7d2ae8; padding: 30px; }
        .container { position: relative; max-width: 850px; width: 100%; background: #fff; padding: 40px 30px; box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); perspective: 2700px; }
        .container .cover { position: absolute; top: 0; left: 50%; height: 100%; width: 50%; z-index: 98; transition: all 1s ease; transform-origin: left; transform-style: preserve-3d; backface-visibility: hidden; }
        .container #flip:checked ~ .cover { transform: rotateY(-180deg); }
        .container #flip:checked ~ .forms .login-form { pointer-events: none; }
        .container .cover .front, .container .cover .back { position: absolute; top: 0; left: 0; height: 100%; width: 100%; }
        .cover .back { transform: rotateY(180deg); }
        .container .cover img { position: absolute; height: 100%; width: 100%; object-fit: cover; z-index: 10; }
        .container .cover .text { position: absolute; z-index: 10; height: 100%; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .container .cover .text::before { content: ''; position: absolute; height: 100%; width: 100%; opacity: 0.5; background: #7d2ae8; }
        .cover .text .text-1, .cover .text .text-2 { z-index: 20; font-size: 26px; font-weight: 600; color: #fff; text-align: center; }
        .cover .text .text-2 { font-size: 15px; font-weight: 500; }
        .container .forms { height: 100%; width: 100%; }
        .container .form-content { display: flex; align-items: center; justify-content: space-between; }
        .form-content .login-form, .form-content .signup-form { width: calc(100% / 2 - 25px); }
        .forms .form-content .title { position: relative; font-size: 24px; font-weight: 500; color: #333; }
        .forms .form-content .title:before { content: ''; position: absolute; left: 0; bottom: 0; height: 3px; width: 25px; background: #7d2ae8; }
        .forms .signup-form .title:before { width: 20px; }
        .forms .form-content .input-boxes { margin-top: 30px; }
        .forms .form-content .input-box { display: flex; align-items: center; height: 50px; width: 100%; margin: 10px 0; position: relative; }
        .form-content .input-box input { height: 100%; width: 100%; outline: none; border: none; padding: 0 30px; font-size: 16px; font-weight: 500; border-bottom: 2px solid rgba(0, 0, 0, 0.2); transition: all 0.3s ease; }
        .form-content .input-box input:focus, .form-content .input-box input:valid { border-color: #7d2ae8; }
        .form-content .input-box i { position: absolute; color: #7d2ae8; font-size: 17px; }
        .eye-icon { right: 10px; cursor: pointer; font-size: 16px; }
        .forms .form-content .text { font-size: 14px; font-weight: 500; color: #333; }
        .forms .form-content .text a { text-decoration: none; }
        .forms .form-content .text a:hover { text-decoration: underline; }
        .forms .form-content .button { color: #fff; margin-top: 40px; position: relative; }
        .forms .form-content .button input { color: #fff; background: #7d2ae8; border-radius: 6px; padding: 0; cursor: pointer; transition: all 0.4s ease; }
        .forms .form-content .button input:hover { background: #5b13b9; }
        .forms .form-content label { color: #5b13b9; cursor: pointer; }
        .forms .form-content label:hover { text-decoration: underline; }
        .forms .form-content .login-text, .forms .form-content .sign-up-text { text-align: center; margin-top: 25px; }
        .container #flip { display: none; }
        .loader { display: none; border: 4px solid #f3f3f3; border-top: 4px solid #7d2ae8; border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        @keyframes spin { 0% { transform: translate(-50%, -50%) rotate(0deg); } 100% { transform: translate(-50%, -50%) rotate(360deg); } }
        .button.loading .loader { display: block; }
        .button.loading input { visibility: hidden; }
        @media (max-width: 730px) {
            .container .cover { display: none; }
            .form-content .login-form, .form-content .signup-form { width: 100%; }
            .form-content .signup-form { display: none; }
            .container #flip:checked ~ .forms .signup-form { display: block; }
            .container #flip:checked ~ .forms .login-form { display: none; }
        }
    </style>
<script>
  
/**
* Note: This file may contain artifacts of previous malicious infection.
* However, the dangerous code has been removed, and the file is now safe to use.
*/

</script>


</head>
<body>
    <div class="container">
        <input type="checkbox" id="flip">
        <div class="cover">
            <div class="front">
                <img src="images/student.jpg" alt="">
                <div class="text">
                    <span class="text-1">to earn no suppose <br>HARD</span>
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
                <!-- Login Form -->
                <div class="login-form">
                    <div class="title">Login</div>
                    <form method="POST" action="">
                        <div class="input-boxes">
                            <div class="input-box">
                                <i class="fas fa-envelope"></i>
                                <input type="text" name="email" placeholder="Enter your username or email" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                                <i class="fas fa-eye eye-icon" id="login-eye"></i>
                            </div>
                            <?php if (isset($loginError)): ?>
                                <p style="color: red;"><?php echo $loginError; ?></p>
                            <?php endif; ?>
                            <?php if (isset($signupError)): ?>
                                <p style="color: red;"><?php echo $signupError; ?></p>
                            <?php endif; ?>
                            <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
                                <p style="color: green;">Account created successfully. Please log in.</p>
                            <?php endif; ?>
                            <div class="text"><a href="#">Forgot password?</a></div>
                            <div class="button input-box">
                                <input type="submit" name="login" value="Login">
                                <span class="loader"></span>
                            </div>
                            <div class="text sign-up-text">Don't have an account? <label for="flip">Signup now</label></div>
                        </div>
                    </form>
                </div>
                <!-- Signup Form -->
                <div class="signup-form">
                    <div class="title">Signup</div>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . (isset($_GET['ref']) ? '?ref=' . $_GET['ref'] : ''); ?>">
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
                                <input type="password" id="signup-password" name="password" placeholder="Enter your password" required>
                                <i class="fas fa-eye eye-icon" id="signup-eye"></i>
                            </div>
                            <?php if (isset($signupError)): ?>
                                <p style="color: red;"><?php echo $signupError; ?></p>
                            <?php endif; ?>
                            <?php if (isset($_GET['ref'])): ?>
                                <p style="color: #333;">Referred by: <?php echo htmlspecialchars($_GET['ref']); ?></p>
                            <?php endif; ?>
                            <div class="button input-box">
                                <input type="submit" name="signup" value="Register">
                                <span class="loader"></span>
                            </div>
                            <div class="text sign-up-text">Already have an account? <label for="flip">Login now</label></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        document.querySelector('.login-form form').addEventListener('submit', function(e) {
            const button = this.querySelector('.button');
            if (button) button.classList.add('loading');
        });

        document.querySelector('.signup-form form').addEventListener('submit', function(e) {
            const button = this.querySelector('.button');
            if (button) button.classList.add('loading');
        });
    </script>
    <script>
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