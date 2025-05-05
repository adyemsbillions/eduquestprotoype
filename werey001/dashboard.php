<?php
// Include the database connection
include("db_connection.php");


// Fetch the total number of users from the users table
$sql_user_count = "SELECT COUNT(*) AS total_users FROM users";
$result = $conn->query($sql_user_count);

// Fetch the number of users with completed profiles
$sql_completed_profiles = "SELECT COUNT(*) AS completed_profiles 
                           FROM users 
                           WHERE (profile_picture IS NOT NULL AND profile_picture != '')
                             AND (full_name IS NOT NULL AND full_name != '') 
                             AND (address IS NOT NULL AND address != '') 
                             AND (department IS NOT NULL AND department != '') 
                             AND (faculty IS NOT NULL AND faculty != '') 
                             AND (level IS NOT NULL AND level != '') 
                         
                             AND (stays_in_hostel IS NOT NULL AND stays_in_hostel != '') 
                             AND (about_me IS NOT NULL AND about_me != '') 
                             AND (interests IS NOT NULL AND interests != '') 
                             AND (gender IS NOT NULL AND gender != '')";

$result_completed_profiles = $conn->query($sql_completed_profiles);

// Check if the queries were successful
if ($result && $result_completed_profiles) {
    $user_count = $result->fetch_assoc()['total_users'];
    $completed_profiles = $result_completed_profiles->fetch_assoc()['completed_profiles'];
    $incomplete_profiles = $user_count - $completed_profiles;
} else {
    $completed_profiles = 0;
    $incomplete_profiles = 0;
    echo "Query failed: " . $conn->error; // Debug query failure
}

// Debug: Check Shimana's profile specifically
$debug_sql = "SELECT * FROM users WHERE email = 'Shimana@gmail.com'";
$debug_result = $conn->query($debug_sql);
$shimana_data = $debug_result->fetch_assoc();
?>
<?php
// Database connection settings
include('db_connection.php');
// You can specify which page(s) you want to show statistics for
// For example, to show views for `page1.php`:
$page = 'page1.php';  // Set this to the page you want to show statistics for

// Fetch the view count for that page
$sql = "SELECT views FROM page_views WHERE page = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $page);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the view count
    $row = $result->fetch_assoc();
    echo "Page '$page' has been viewed " . $row['views'] . " times.";
} else {
    echo "";
}

// Close prepared statement and connection
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en" class="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chief Admin || unimaid resources</title>

    <!-- Tailwind is included -->
    <link rel="stylesheet" href="css/main.css">

    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="mask-icon" href="images/safari-pinned-tab.svg" color="#00b4b6">


    <meta property="og:url" content="https://justboil.github.io/admin-one-tailwind/">
    <meta property="og:site_name" content="JustBoil.me">
    <meta property="og:title" content="Admin One HTML">
    <meta property="og:description" content="">
    <meta property="og:image" content="images/repository-preview-hi-res.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1920">
    <meta property="og:image:height" content="960">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="Admin One HTML">
    <meta property="twitter:description" content="">
    <meta property="twitter:image:src" content="https://justboil.me/images/one-tailwind/repository-preview-hi-res.png">
    <meta property="twitter:image:width" content="1920">
    <meta property="twitter:image:height" content="960">

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async="" src="https://www.googletagmanager.com/gtag/js?id=UA-130795909-1"></script>
    <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', 'UA-130795909-1');
    </script>

</head>

<body>

    <div id="app">

        <nav id="navbar-main" class="navbar is-fixed-top">
            <div class="navbar-brand">
                <a class="navbar-item mobile-aside-button">
                    <span class="icon"><i class="mdi mdi-forwardburger mdi-24px"></i></span>
                </a>
                <div class="navbar-item">
                    <div class="control"><input placeholder="Search everywhere..." class="input"></div>
                </div>
            </div>
            <div class="navbar-brand is-right">
                <a class="navbar-item --jb-navbar-menu-toggle" data-target="navbar-menu">
                    <span class="icon"><i class="mdi mdi-dots-vertical mdi-24px"></i></span>
                </a>
            </div>
            <div class="navbar-menu" id="navbar-menu">
                <div class="navbar-end">
                    <div class="navbar-item dropdown has-divider">
                        <a class="navbar-link">
                            <span class="icon"><i class="mdi mdi-menu"></i></span>
                            <span>Sample Menu</span>
                            <span class="icon">
                                <i class="mdi mdi-chevron-down"></i>
                            </span>
                        </a>
                        <div class="navbar-dropdown">
                            <a href="verification_requests.php" class="navbar-item">
                                <span class="icon"><i class="mdi mdi-account"></i></span>
                                <span>blue Verification</span>
                            </a>
                            <a class="navbar-item" href="black_verification.php">
                                <span class="icon"><i class="mdi mdi-settings"></i></span>
                                <span>Back verification</span>
                            </a>
                            <a class="navbar-item" href="pink_verification.php">
                                <span class="icon"><i class="mdi mdi-email"></i></span>
                                <span>Pink verification</span>
                            </a>
                            <hr class="navbar-divider">
                            <a class="navbar-item" href="logout.php">
                                <span class="icon"><i class="mdi mdi-logout"></i></span>
                                <span>Log Out</span>
                            </a>
                        </div>
                    </div>
                    <div class="navbar-item dropdown has-divider has-user-avatar">
                        <a class="navbar-link">
                            <div class="user-avatar">
                                <img src="images/john-doe.svg" alt="John Doe" class="rounded-full">
                            </div>
                            <div class="is-user-name"><span>Unimaid Resources</span></div>
                            <span class="icon"><i class="mdi mdi-chevron-down"></i></span>
                        </a>
                        <div class="navbar-dropdown">
                            <a href="usermessages.php" class="navbar-item">
                                <span class="icon"><i class="mdi mdi-account"></i></span>
                                <span>User messages</span>
                            </a>
                            <a class="navbar-item">
                                <span class="icon"><i class="mdi mdi-settings"></i></span>
                                <span>Complains</span>
                            </a>
                            <a class="navbar-item" href="admin_approve.php">
                                <span class="icon"><i class="mdi mdi-email"></i></span>
                                <span>Approve WCW</span>
                            </a>
                            <hr class="navbar-divider">
                            <a class="navbar-item" href="logout.php">
                                <span class="icon"><i class="mdi mdi-logout"></i></span>
                                <span>Log Out</span>
                            </a>
                        </div>
                    </div>
                    <a href="https://wa.me/9139293270" class="navbar-item has-divider desktop-icon-only">
                        <span class="icon"><i class="mdi mdi-help-circle-outline"></i></span>
                        <span>About</span>
                    </a>
                    <a href="https://wa.me/9139293270" class="navbar-item has-divider desktop-icon-only">
                        <span class="icon"><i class="mdi mdi-github-circle"></i></span>
                        <span>GitHub</span>
                    </a>
                    <a title="Log out" class="navbar-item desktop-icon-only" href="logout.php">
                        <span class="icon"><i class="mdi mdi-logout"></i></span>
                        <span>Log out</span>
                    </a>
                </div>
            </div>
        </nav>

        <aside class="aside is-placed-left is-expanded">
            <div class="aside-tools">

            </div>
            <div class="menu is-menu-main">
                <p class="menu-label">General</p>
                <ul class="menu-list">
                    <li class="active">
                        <a href="index.html">
                            <span class="icon"><i class="mdi mdi-desktop-mac"></i></span>
                            <span class="menu-item-label">Dashboard</span>
                        </a>
                    </li>
                </ul>
                <p class="menu-label">Admin approvals</p>
                <ul class="menu-list">
                    <li class="--set-active-tables-html">
                        <a href="verify_user.php">
                            <span class="icon"><i class="mdi mdi-table"></i></span>
                            <span class="menu-item-label">Verify users</span>
                        </a>
                    </li>
                    <li class="--set-active-forms-html">
                        <a href="admin_sales_approvals.php">
                            <span class="icon"><i class="mdi mdi-square-edit-outline"></i></span>
                            <span class="menu-item-label">sales approvals</span>
                        </a>
                    </li>
                    <li class="--set-active-profile-html">
                        <a href="approve_mkt.php">
                            <span class="icon"><i class="mdi mdi-account-circle"></i></span>
                            <span class="menu-item-label">approval mkt post</span>
                        </a>
                    </li>
                    <li class="--set-active-profile-html">
                        <a href="make_reps.php">
                            <span class="icon"><i class="mdi mdi-account-circle"></i></span>
                            <span class="menu-item-label">Make class reps</span>
                        </a>
                    </li>
                    <li>
                        <a href="delete_post.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">Manage posts</span>
                        </a>
                    </li>
                    <li>
                        <a href=" https://quiz.unimaidresources.com.ng/upload_csv.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">POST MULTIPLE CSV QUIZ</span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown">
                            <span class="icon"><i class="mdi mdi-view-list"></i></span>
                            <span class="menu-item-label">Submenus</span>
                            <span class="icon"><i class="mdi mdi-plus"></i></span>
                        </a>
                        <ul>
                            <li>
                                <a href="manage_events.php">
                                    <span>Manage Events</span>
                                </a>
                            </li>



                            <li>
                                <a href="/dashboard/delete_mkt.php">
                                    <span>delete mkt post</span>
                                </a>
                            </li>

                        </ul>
                    </li>
                </ul>
                <p class="menu-label">More..</p>
                <ul class="menu-list">

                    <li>
                        <a href="loan_requests.php" class="has-icon">
                            <span class="icon"><i class="mdi mdi-help-circle"></i></span>
                            <span class="menu-item-label">Loan request</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="has-icon">
                            <span class="icon"><i class="mdi mdi-github-circle"></i></span>
                            <span class="menu-item-label">Manage users</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <section class="is-title-bar">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-6 md:space-y-0">
                <ul>
                    <li>Admin</li>
                    <li>Dashboard</li>
                </ul>

            </div>
        </section>



        <section class="section main-section">
            <div class="grid gap-6 grid-cols-1 md:grid-cols-3 mb-6">
                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center justify-between">
                            <div class="widget-label">
                                <h3>
                                    Total Students
                                </h3>

                                <h2><?php echo $user_count * 20; ?>
                                </h2>
                            </div>
                            <span class="icon widget-icon text-green-500"><i
                                    class="mdi mdi-account-multiple mdi-48px"></i></span>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center justify-between">
                            <div class="widget-label">
                                <h3>
                                    Profile Completed
                                </h3>
                                <h2><?php echo $completed_profiles  * 20; ?></h2>
                            </div>
                            <span class="icon widget-icon text-blue-500"><i
                                    class="mdi mdi-cart-outline mdi-48px"></i></span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center justify-between">
                            <div class="widget-label">
                                <h3>
                                    Incomplete profile
                                </h3>
                                <h2><?php echo $incomplete_profiles * 20; ?></h2>
                            </div>
                            <span class="icon widget-icon text-red-500"><i class="mdi mdi-finance mdi-48px"></i></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-6">
                <header class="card-header">
                    <p class="card-header-title">
                        <span class="icon"><i class="mdi mdi-finance"></i></span>
                        Performance
                    </p>
                    <a href="#" class="card-header-icon">
                        <span class="icon"><i class="mdi mdi-reload"></i></span>
                    </a>
                </header>
                <div class="card-content">
                    <div class="chart-area">
                        <div class="h-full">

                            <!-- In your HTML section -->
                            <div class="card mb-6">
                                <header class="card-header">
                                    <p class="card-header-title">
                                        <span class="icon"><i class="mdi mdi-finance"></i></span>
                                        Profile Statistics
                                    </p>
                                    <a href="#" class="card-header-icon">
                                        <span class="icon"><i class="mdi mdi-reload"></i></span>
                                    </a>
                                </header>
                                <div class="card-content">
                                    <div class="chart-area" style="height: 300px;">
                                        <canvas id="profileChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- At the bottom of your file, replace the chart scripts with these -->
                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const ctx = document.getElementById('profileChart').getContext('2d');
                                const profileChart = new Chart(ctx, {
                                    type: 'bar', // This creates vertical columns
                                    data: {
                                        labels: ['Total Students', 'Completed Profiles',
                                            'Incomplete Profiles'
                                        ],
                                        datasets: [{
                                            label: 'User Statistics',
                                            data: [
                                                <?php echo $user_count; ?>,
                                                <?php echo $completed_profiles; ?>,
                                                <?php echo $incomplete_profiles; ?>
                                            ],
                                            backgroundColor: [
                                                'rgba(54, 162, 235, 0.8)', // Blue for total students
                                                'rgba(75, 192, 192, 0.8)', // Green for completed
                                                'rgba(255, 99, 132, 0.8)' // Red for incomplete
                                            ],
                                            borderColor: [
                                                'rgba(54, 162, 235, 1)',
                                                'rgba(75, 192, 192, 1)',
                                                'rgba(255, 99, 132, 1)'
                                            ],
                                            borderWidth: 1,
                                            barThickness: 40, // Controls the width of the columns
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Number of Users'
                                                },
                                                ticks: {
                                                    stepSize: 1 // Ensures whole numbers on y-axis
                                                }
                                            },
                                            x: {
                                                title: {
                                                    display: true,
                                                    text: 'Categories'
                                                }
                                            }
                                        },
                                        plugins: {
                                            legend: {
                                                display: false // Hide legend since we have one dataset
                                            },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(tooltipItem) {
                                                        return tooltipItem.label + ': ' +
                                                            tooltipItem.raw + ' users';
                                                    }
                                                }
                                            },
                                            title: {
                                                display: true,
                                                text: 'User Profile Statistics',
                                                font: {
                                                    size: 16
                                                }
                                            }
                                        }
                                    }
                                });
                            });
                            </script>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Database connection (adjust credentials as needed)
            include('db_connection.php');

            // Query to count new users in the last 24 hours
            $sql = "SELECT COUNT(*) as new_users 
        FROM users 
        WHERE created_at >= NOW() - INTERVAL 1 DAY";
            $result = $conn->query($sql);

            $new_users_count = 0;
            if ($result && $row = $result->fetch_assoc()) {
                $new_users_count = $row['new_users'];
            }

            $conn->close();
            ?>

            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Notification Example</title>
                <!-- Assuming Material Design Icons (MDI) is included -->
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font/css/materialdesignicons.min.css">
                <style>
                .notification {
                    padding: 15px;
                    border-radius: 8px;
                    margin: 10px;
                    font-family: 'Arial', sans-serif;
                }

                .notification.blue {
                    background-color: #e7f0fd;
                    color: #1e40af;
                }

                .flex {
                    display: flex;
                }

                .flex-col {
                    flex-direction: column;
                }

                .md\:flex-row {

                    /* Media query for medium screens and up */
                    @media (min-width: 768px) {
                        flex-direction: row;
                    }
                }

                .items-center {
                    align-items: center;
                }

                .justify-between {
                    justify-content: space-between;
                }

                .space-y-3>*+* {
                    margin-top: 12px;
                    /* 3 * 4px base unit */
                }

                .md\:space-y-0 {
                    @media (min-width: 768px) {
                        .space-y-3>*+* {
                            margin-top: 0;
                        }
                    }
                }

                .icon {
                    margin-right: 8px;
                    font-size: 20px;
                    vertical-align: middle;
                }

                b {
                    font-weight: 600;
                }

                .button {
                    padding: 6px 12px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 14px;
                }

                .small {
                    padding: 4px 8px;
                }

                .textual {
                    background: none;
                    color: #1e40af;
                    text-decoration: underline;
                }

                .textual:hover {
                    color: #1e3a8a;
                }
                </style>
            </head>

            <body>
                <?php if ($new_users_count > 0): ?>
                <div class="notification blue">
                    <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
                        <div>
                            <span class="icon"><i class="mdi mdi-buffer"></i></span>
                            <b>You have <?php echo $new_users_count; ?> new
                                <?php echo $new_users_count === 1 ? 'user' : 'users'; ?> in the last 24hrs</b>
                        </div>
                        <button type="button" class="button small textual --jb-notification-dismiss">Dismiss</button>
                    </div>
                </div>
                <?php endif; ?>
            </body>

            </html>

            <div class="card has-table">
                <header class="card-header">
                    <p class="card-header-title">
                        <span class="icon"><i class="mdi mdi-account-multiple"></i></span>
                        Clients
                    </p>
                    <a href="#" class="card-header-icon">
                        <span class="icon"><i class="mdi mdi-reload"></i></span>
                    </a>
                </header>
                <ul>
                    <li>
                        <a href="delete_fpost.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">Delete Football Post</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_approve.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">Approve wcw</span>
                        </a>
                    </li>
                    <li>
                        <a href="/dashboard/upload_slide.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">Upload Slider</span>
                        </a>
                    </li>
                    <li>
                        <a href="group_settings.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">Group Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_popups.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">Send Popup Message</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_feed_rights.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">news upload rights</span>
                        </a>
                    </li>
                    <li>
                        <a href="users_statistics.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">Users Statistics</span>
                        </a>
                    </li>


                    <li>
                        <a href="admin_reels.php">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            <span class="menu-item-label">Manage Reels</span>
                        </a>
                    </li>
                </ul>
                <style>
                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                th,
                td {
                    padding: 10px;
                    text-align: left;
                }

                .image-cell .image {
                    display: inline-block;
                }

                .icon {
                    font-size: 20px;
                }

                .actions-cell .buttons {
                    display: flex;
                    gap: 5px;
                }

                .button {
                    padding: 5px 10px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                }

                .small {
                    font-size: 12px;
                }

                .green {
                    background: #28a745;
                    color: white;
                }

                .red {
                    background: #dc3545;
                    color: white;
                }

                .nowrap {
                    white-space: nowrap;
                }

                .table-pagination {
                    margin-top: 10px;
                }

                .flex {
                    display: flex;
                }

                .items-center {
                    align-items: center;
                }

                .justify-between {
                    justify-content: space-between;
                }
                </style>








        </section>

        <footer class="footer">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0">
                <div class="flex items-center justify-start space-x-3">
                    <div>
                        © 2025, unimaid resources
                    </div>
                    <div>
                        <p> By: <a href="https://unimaidresources.com.ng" target="_blank">Eduquest</a></p>
                    </div>
                    <a href="https://github.com/justboil/admin-one-tailwind" style="height: 20px">
                        <img src="https://img.shields.io/github/v/release/justboil/admin-one-tailwind?color=%23999">
                    </a>
                </div>

            </div>
        </footer>

        <div id="sample-modal" class="modal">
            <div class="modal-background --jb-modal-close"></div>
            <div class="modal-card">
                <header class="modal-card-head">
                    <p class="modal-card-title">Sample modal</p>
                </header>
                <section class="modal-card-body">
                    <p>Lorem ipsum dolor sit amet <b>adipiscing elit</b></p>
                    <p>This is sample modal</p>
                </section>
                <footer class="modal-card-foot">
                    <button class="button --jb-modal-close">Cancel</button>
                    <button class="button red --jb-modal-close">Confirm</button>
                </footer>
            </div>
        </div>

        <div id="sample-modal-2" class="modal">
            <div class="modal-background --jb-modal-close"></div>
            <div class="modal-card">
                <header class="modal-card-head">
                    <p class="modal-card-title">Sample modal</p>
                </header>
                <section class="modal-card-body">
                    <p>Lorem ipsum dolor sit amet <b>adipiscing elit</b></p>
                    <p>This is sample modal</p>
                </section>
                <footer class="modal-card-foot">
                    <button class="button --jb-modal-close">Cancel</button>
                    <button class="button blue --jb-modal-close">Confirm</button>
                </footer>
            </div>
        </div>

    </div>

    <!-- Scripts below are for demo only -->
    <script type="text/javascript" src="js/main.min.js"></script>

    <script type="text/javascript" src="js/Chart.min.js"></script>
    <script type="text/javascript" src="js/chart.sample.min.js"></script>


    <script>
    ! function(f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function() {
            n.callMethod ?
                n.callMethod.apply(n, arguments) : n.queue.push(arguments)
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s)
    }(window, document, 'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '658339141622648');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=658339141622648&ev=PageView&noscript=1" /></noscript>

    <!-- Icons below are for demo only. Feel free to use any icon pack. Docs: https://bulma.io/documentation/elements/icon/ -->
    <link rel="stylesheet" href="css/materialdesignicons.min.css">



</body>

</html>