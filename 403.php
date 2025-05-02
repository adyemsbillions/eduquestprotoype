<?php
// 403.php - This file will show a 403 error message and redirect to dashboard.php after 5 seconds
header("HTTP/1.1 403 Forbidden");
header("Status: 403 Forbidden");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            color: #333;
            text-align: center;
            padding: 50px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 48px;
            color: #e74c3c;
        }

        p {
            font-size: 18px;
            margin-top: 10px;
        }

        .countdown {
            font-size: 24px;
            margin-top: 20px;
            font-weight: bold;
        }

        .redirect-msg {
            margin-top: 20px;
            font-size: 16px;
            color: #777;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Forbidden page</h1>
    <p>You don't have permission to access this page.</p>
    <div class="countdown" id="countdown">Redirecting in 5 seconds...</div>
    <p class="redirect-msg">If you are not redirected, <a href="dashboard.php">click here</a>.</p>
</div>

<script>
    // Countdown timer
    var countdownElement = document.getElementById("countdown");
    var countdownTime = 5; // Time in seconds

    var countdownInterval = setInterval(function() {
        countdownElement.textContent = "Redirecting in " + countdownTime + " seconds...";
        countdownTime--;
        if (countdownTime < 0) {
            clearInterval(countdownInterval);
            window.location.href = "dashboard.php"; // Redirect to dashboard.php after countdown
        }
    }, 1000);
</script>

</body>
</html>
