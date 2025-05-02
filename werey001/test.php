<?php
// db_connection.php - Make sure this file contains your database connection details.
include("db_connection.php");

// Fetch the total number of users from the users table
$sql_user_count = "SELECT COUNT(*) AS total_users FROM users";
$result = $conn->query($sql_user_count);

// Fetch the number of users with completed profiles
$sql_completed_profiles = "SELECT COUNT(*) AS completed_profiles 
                           FROM users 
                           WHERE profile_picture != '' 
                             AND full_name != '' 
                             AND address != '' 
                             AND department != '' 
                             AND faculty != '' 
                             AND level != '' 
                             AND about_me != '' 
                             AND interests != '' 
                             AND gender != ''";
$result_completed_profiles = $conn->query($sql_completed_profiles);

// Check if the query was successful
if ($result && $result_completed_profiles) {
    // Fetch the results
    $user_count = $result->fetch_assoc()['total_users'];
    $completed_profiles = $result_completed_profiles->fetch_assoc()['completed_profiles'];

    // Calculate the number of incomplete profiles
    $incomplete_profiles = $user_count - $completed_profiles;
} else {
    $completed_profiles = 0;
    $incomplete_profiles = 0;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Completion Chart</title>
    <!-- Include Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .chart-container {
            width: 60%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #6a1b9a;
        }
    </style>
</head>
<body>

<div class="chart-container">
    <h2>Profile Completion Chart</h2>
    <canvas id="profileChart"></canvas>
</div>

<script>
// Data for the chart
const ctx = document.getElementById('profileChart').getContext('2d');
const profileChart = new Chart(ctx, {
    type: 'pie', // You can change this to 'bar', 'line', etc.
    data: {
        labels: ['Completed Profiles', 'Incomplete Profiles'],
        datasets: [{
            label: 'Profile Completion',
            data: [<?php echo $completed_profiles; ?>, <?php echo $incomplete_profiles; ?>],
            backgroundColor: ['#4caf50', '#ff5722'],
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.label + ': ' + tooltipItem.raw + ' users';
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
