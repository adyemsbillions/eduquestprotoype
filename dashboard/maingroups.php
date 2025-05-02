<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Page</title>
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Container styling */
        .container {
            text-align: center;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px; /* Make container responsive */
        }

        /* Title styling */
        h1 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.8rem;
        }

        /* Button container styling */
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Button styling */
        .group-button {
            background-color: purple;
            color: white;
            padding: 12px 24px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none; /* Remove underline */
            display: block;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        /* Button hover effect */
        .group-button:hover {
            background-color: #7a3f8f;
        }

        /* Mobile responsive styling */
        @media (max-width: 480px) {
            h1 {
                font-size: 1.5rem;
            }

            .group-button {
                font-size: 14px;
                padding: 10px 20px;
            }
        }

    </style>
</head>
<body>
        <div class="back-button-container">
    <a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>
</div>

<style>
    .back-button-container {
        position: fixed;
        bottom: 30px;
        left: 30px;
        z-index: 1000;
    }

    .back-button {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: purple; /* Uses your --primary color: #6a1b9a */
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .back-button:hover {
        background: purple; /* Uses your --primary-dark: #4a148c */
        transform: scale(1.1);
    }
</style>
    <div class="container">
    <div class="container">
        <h1>Welcome to Your Groups</h1>
        <div class="button-container">
            <!-- Buttons with links -->
            <a href="my_groups.php" class="group-button">My Groups</a>
            <a href="view_groups.php" class="group-button">Join a group</a>
            <a href="create_groups.php" class="group-button">Create a Groups</a>
        </div>
    </div>
</body>
</html>
