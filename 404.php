<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - 404</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            box-sizing: border-box;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease-in-out;
        }
        .container h1 {
            color: #e74c3c;
            font-size: 50px;
            margin-bottom: 15px;
        }
        .message {
            color: #555;
            font-size: 18px;
            margin-bottom: 25px;
            font-weight: 400;
        }
        .btn-home, .btn-login {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 18px;
            border-radius: 5px;
            transition: background-color 0.3s;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn-home:hover, .btn-login:hover {
            background-color: #2980b9;
        }
        .countdown {
            font-size: 20px;
            color: #ff5722;
            margin-top: 20px;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Make the page responsive for smaller devices */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            .container h1 {
                font-size: 40px;
            }
            .message {
                font-size: 16px;
            }
            .btn-home, .btn-login {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Error Message Area -->
        <h1><i class="fas fa-exclamation-triangle"></i> 404</h1>
        <div class="message">
            <p><i class="fas fa-book-reader"></i> Oops UNIMAIDIAN! The page you're looking for doesn't exist. Perhaps it was removed or you typed the wrong URL. Let's get back to learning!</p>
        </div>

        <!-- Countdown Timer -->
        <div class="countdown">
            <p>Redirecting to your dashboard in <span id="timer">10</span> seconds...</p>
        </div>

        <!-- Back to Home Button (Redirect after countdown) -->
        <a href="dashboard.php" id="homeButton" class="btn-home">
            <i class="fas fa-home"></i> Go to Dashboard
        </a>

        <!-- Back to Login Button -->
        <a href="index.php" id="loginButton" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>
    </div>

    <script>
        // Countdown functionality
        let countdown = 10;
        const timerElement = document.getElementById('timer');
        const redirectButton = document.getElementById('homeButton');

        // Countdown logic
        setInterval(function() {
            countdown--;
            timerElement.textContent = countdown;

            if (countdown === 0) {
                // Redirect after countdown finishes
                window.location.href = '/dashboard/dashboard.php';
            }
        }, 1000);
    </script>

</body>
</html>
