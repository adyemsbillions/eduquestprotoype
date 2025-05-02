<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'unimaidconnect');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);

    $query = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            echo "<p style='color: green;'>Login successful! Welcome, " . $user['username'] . ".</p>";
        } else {
            $loginError = "Invalid password.";
        }
    } else {
        $loginError = "No user found with this email.";
    }
}

// Signup Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if email already exists
    $checkEmail = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($checkEmail->num_rows > 0) {
        $signupError = "Email already exists.";
    } else {
        $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
        if ($conn->query($query) === TRUE) {
            $signupSuccess = "Account created successfully.";
        } else {
            $signupError = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <title>Login & Signup</title>
</head>
<body>
    <div class="login container grid" id="loginAccessRegister">
        <!-- Login Form -->
        <div class="login__access">
            <h1 class="login__title">Log in to your account.</h1>
            <?php if (isset($loginError)) echo "<p style='color: red;'>$loginError</p>"; ?>
            <div class="login__area">
                <form method="POST" class="login__form">
                    <div class="login__content grid">
                        <div class="login__box">
                            <input type="email" name="email" required placeholder=" " class="login__input">
                            <label for="email" class="login__label">Email</label>
                            <i class="ri-mail-fill login__icon"></i>
                        </div>
                        <div class="login__box">
                            <input type="password" name="password" required placeholder=" " class="login__input">
                            <label for="password" class="login__label">Password</label>
                            <i class="ri-eye-off-fill login__icon login__password"></i>
                        </div>
                    </div>
                    <button type="submit" name="login" class="login__button">Login</button>
                </form>
            </div>
        </div>

        <!-- Signup Form -->
        <div class="login__register">
            <h1 class="login__title">Create new account.</h1>
            <?php if (isset($signupError)) echo "<p style='color: red;'>$signupError</p>"; ?>
            <?php if (isset($signupSuccess)) echo "<p style='color: green;'>$signupSuccess</p>"; ?>
            <div class="login__area">
                <form method="POST" class="login__form">
                    <div class="login__content grid">
                        <div class="login__box">
                            <input type="text" name="username" required placeholder=" " class="login__input">
                            <label for="username" class="login__label">Username</label>
                            <i class="ri-id-card-fill login__icon"></i>
                        </div>
                        <div class="login__box">
                            <input type="email" name="email" required placeholder=" " class="login__input">
                            <label for="email" class="login__label">Email</label>
                            <i class="ri-mail-fill login__icon"></i>
                        </div>
                        <div class="login__box">
                            <input type="password" name="password" required placeholder=" " class="login__input">
                            <label for="password" class="login__label">Password</label>
                            <i class="ri-eye-off-fill login__icon login__password"></i>
                        </div>
                    </div>
                    <button type="submit" name="signup" class="login__button">Create account</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
