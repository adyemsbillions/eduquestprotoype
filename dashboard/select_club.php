<?php
include 'db_connection.php';
session_start();

// Check if the user has already selected a club
if (isset($_SESSION['user_id']) && isset($_SESSION['first_time']) && $_SESSION['first_time'] == false) {
    header("Location: football.php");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $club = $_POST['club'];
    $username = $_SESSION['username']; // Assuming username is set in session

    // Save club to the users table
    $stmt = $conn->prepare("UPDATE users SET club = ?, first_time = 0 WHERE username = ?");
    $stmt->execute([$club, $username]);

    $_SESSION['first_time'] = false;  // Mark as not first time anymore
    header("Location: football.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Club</title>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        h1 {
            color: #6a1b9a; /* Purple heading */
            margin-bottom: 20px;
            font-size: 24px;
        }
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 2px solid #6a1b9a; /* Purple border */
            border-radius: 5px;
            font-size: 16px;
            color: #333;
            background-color: #f9f9f9;
        }
        select:focus {
            outline: none;
            border-color: #4a148c; /* Darker purple on focus */
        }
        button {
            background-color: #6a1b9a; /* Purple button */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #4a148c; /* Darker purple on hover */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome! Select Your Football Club</h1>
        <form method="POST">
<select name="club" required>
    <option value="">Choose your club</option>
    <option value="Manchester United">Manchester United</option>
    <option value="Real Madrid">Real Madrid</option>
    <option value="Barcelona">Barcelona</option>
    <option value="Liverpool">Liverpool</option>
    <option value="Chelsea">Chelsea</option>
    <option value="Manchester City">Manchester City</option>
    <option value="Bayern Munich">Bayern Munich</option>
    <option value="Paris Saint-Germain">Paris Saint-Germain</option>
    <option value="Juventus">Juventus</option>
    <option value="Arsenal">Arsenal</option>
    <option value="Tottenham Hotspur">Tottenham Hotspur</option>
    <option value="Atlético Madrid">Atlético Madrid</option>
    <option value="Borussia Dortmund">Borussia Dortmund</option>
    <option value="Inter Milan">Inter Milan</option>
    <option value="AC Milan">AC Milan</option>
    <option value="Napoli">Napoli</option>
    <option value="Sevilla">Sevilla</option>
    <option value="Ajax">Ajax</option>
    <option value="Porto">Porto</option>
    <option value="Benfica">Benfica</option>
    <option value="AS Roma">AS Roma</option>
    <option value="Lazio">Lazio</option>
    <option value="Valencia">Valencia</option>
    <option value="Villarreal">Villarreal</option>
    <option value="RB Leipzig">RB Leipzig</option>
    <option value="Leicester City">Leicester City</option>
    <option value="Everton">Everton</option>
    <option value="West Ham United">West Ham United</option>
    <option value="Fenerbahçe">Fenerbahçe</option>
    <option value="Galatasaray">Galatasaray</option>
</select>
            <button type="submit">Submit</button>
        </form>
    </div>
</body>
</html>
<?php
$conn->close();
?>