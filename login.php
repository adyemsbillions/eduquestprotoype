<?php
// Database connection
include('db_connection.php');
// Handle signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if email already exists
    $checkEmail = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($checkEmail->num_rows > 0) {
        $signupError = "Email already exists.";
    } else {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $profilePic = $_FILES['profile_picture'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $uploadDir = 'uploads/profile_pictures/';

            // Validate file type
            if (!in_array($profilePic['type'], $allowedTypes)) {
                $signupError = "Invalid file type. Please upload a JPG, PNG, or GIF image.";
            } else {
                // Generate a unique file name to avoid conflicts
                $fileName = uniqid() . '_' . basename($profilePic['name']);
                $filePath = $uploadDir . $fileName;

                // Move the uploaded file to the specified directory
                if (move_uploaded_file($profilePic['tmp_name'], $filePath)) {
                    // Store the image path in the database
                    $query = "INSERT INTO users (username, email, password, profile_picture) VALUES ('$username', '$email', '$password', '$filePath')";
                    if ($conn->query($query) === TRUE) {
                        $signupSuccess = "Account created successfully.";
                    } else {
                        $signupError = "Error: " . $conn->error;
                    }
                } else {
                    $signupError = "Error uploading the profile picture.";
                }
            }
        } else {
            // If no profile picture is uploaded, just save an empty value
            $query = "INSERT INTO users (username, email, password, profile_picture) VALUES ('$username', '$email', '$password', '')";
            if ($conn->query($query) === TRUE) {
                $signupSuccess = "Account created successfully.";
            } else {
                $signupError = "Error: " . $conn->error;
            }
        }
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Start session and store user info
            session_start();
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            $_SESSION['user_id'] = $user['id'];  // Store user ID to identify them
            // Redirect to dashboard after successful login
            header("Location: checker.php");
            exit(); // Don't forget to exit after the redirect
        } else {
            $loginError = "Invalid credentials.";
        }
    } else {
        $loginError = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title>Login and Registration Form | UNIMAID Connect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    /* Google Font Link */
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
                    <span class="text-1">Education no support <br>HARD</span>
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
                                <input type="text" name="email" placeholder="Enter your email " required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" placeholder="Enter your password" required>
                            </div>
                            <?php if (isset($loginError)): ?>
                            <p style="color: red;"><?php echo $loginError; ?></p>
                            <?php endif; ?>
                            <div class="text"><a href="#">Forgot password?</a></div>
                            <div class="button input-box">
                                <input type="submit" name="login" value="Submit">
                            </div>
                            <div class="text sign-up-text">Don't have an account? <label for="flip">Signup now</label>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Signup Form -->
                <div class="signup-form">
                    <div class="title">Signup</div>
                    <form method="POST" enctype="multipart/form-data" action="">
                        <div class="input-boxes">
                            <div class="input-box">
                                <i class="fas fa-user"></i>
                                <input type="text" name="username" placeholder="Enter your username" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-envelope"></i>
                                <input type="text" name="email" placeholder="Enter your email" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" placeholder="Enter your password" required>
                            </div>
                            <div class="input-box">
                                <i class="fas fa-image"></i>
                                <input type="file" name="profile_picture" accept="image/*">
                            </div>
                            <?php if (isset($signupError)): ?>
                            <p style="color: red;"><?php echo $signupError; ?></p>
                            <?php endif; ?>
                            <?php if (isset($signupSuccess)): ?>
                            <p style="color: green;"><?php echo $signupSuccess; ?></p>
                            <?php endif; ?>
                            <div class="button input-box">
                                <input type="submit" name="signup" value="Submit">
                            </div>
                            <div class="text sign-up-text">Already have an account? <label for="flip">Login now</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>