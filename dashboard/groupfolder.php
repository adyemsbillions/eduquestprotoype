<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groups Dashboard</title>
    <style>
        /* Reset margin and padding */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: #ffffff;
            color: #333;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            margin-bottom: 30px;
            font-size: 2em;
            color: #6a1b9a; /* Subtle purple shade for the title */
        }

        .button-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .button-container a {
            text-decoration: none;
            padding: 15px 0;
            font-size: 1.2em;
            font-weight: bold;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            display: block;
            text-align: center;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .create-group {
            background-color: #6a1b9a; /* Purple button */
        }

        .my-groups {
            background-color: #8e24aa; /* Lighter purple button */
        }

        .join-groups {
            background-color: #ab47bc; /* Even lighter purple button */
        }

        .button-container a:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        .button-container a:active {
            transform: scale(1);
            opacity: 1;
        }

        @media (min-width: 768px) {
            .button-container {
                flex-direction: row;
            }

            .button-container a {
                width: auto;
                flex: 1;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Groups Dash</h1>
    <div class="button-container">
        <a href="create_groups.php" class="create-group">Create Group</a>
        <a href="my_groups.php" class="my-groups">My Groups</a>
        <a href="view_groups.php" class="join-groups">Join Groups</a>
    </div>
</div>

</body>
</html>
