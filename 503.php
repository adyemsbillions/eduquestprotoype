<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <title>Temporary Pause - Maintenance</title>
    <!-- Link to Font Awesome for icons -->
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
            color: #f39c12;
            font-size: 30px;
            margin-bottom: 15px;
        }
        .message {
            color: #555;
            font-size: 18px;
            margin-bottom: 25px;
            font-weight: 400;
        }
        .countdown {
            font-size: 20px;
            color: #ff5722;
            margin-top: 20px;
        }
        .btn-contact {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 18px;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin-top: 20px;
            display: inline-block;
        }
        .btn-contact:hover {
            background-color: #2980b9;
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
                font-size: 26px;
            }
            .message {
                font-size: 16px;
            }
            .btn-contact {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Friendly Message Area -->
        <h1><i class="fas fa-cogs"></i> We're Just Getting Things Ready!</h1>
        <div class="message">
            <p><i class="fas fa-tools"></i> Our system is undergoing some temporary maintenance. We’re working hard to improve your experience. Hang tight – we’ll be back shortly!</p>
        </div>

        <!-- Countdown Timer -->
        <div class="countdown">
            <p>Redirecting to your dashboard in <span id="timer">10</span> seconds...</p>
        </div>

        <!-- Contact Admin Button -->
        <a href="adminlink.php" class="btn-contact">
            <i class="fas fa-envelope"></i> Contact Admin
        </a>
    </div>

    <script>
        // Countdown functionality
        let countdown = 10;
        const timerElement = document.getElementById('timer');
        
        setInterval(function() {
            countdown--;
            timerElement.textContent = countdown;

            if (countdown === 0) {
                // Redirect to dashboard.php after countdown ends
                window.location.href = 'dashboard/dashboard.php';
            }
        }, 1000);
    </script>

</body>
</html>
