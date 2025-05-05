<?php
// Database connection details
include('db_connection.php');

// Fetch user details from the users table (all columns)
$sql_users = "SELECT * FROM users";
$result_users = $conn->query($sql_users);

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - UnimaidConnect</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f8f9fa;
        margin-top: 20px;
    }

    h1 {
        text-align: center;
        margin-bottom: 30px;
        color: #007bff;
    }

    .table-container {
        max-width: 90%;
        margin: 0 auto;
        margin-bottom: 40px;
    }

    .table-container .table {
        width: 100%;
        margin-bottom: 20px;
    }

    /* Styling for table headers */
    th {
        background-color: black !important;
        /* Strong black background for the header */
        color: white !important;
        /* White text color */
        text-align: center;
        /* Center-align text */
    }

    td {
        text-align: center;
        /* Center-align text for table cells */
    }

    .table th,
    .table td {
        padding: 12px;
        border: 1px solid #dee2e6;
    }

    .footer {
        text-align: center;
        margin-top: 40px;
        color: #6c757d;
    }

    .profile-img {
        cursor: pointer;
        width: 50px;
        height: 50px;
        object-fit: cover;
    }

    .modal-body img {
        width: 100%;
        max-height: 500px;
        object-fit: contain;
    }
    </style>
</head>

<body>

    <div class="container">
        <h1>User Details - Unimaid Resources</h1>

        <!-- User Details Section -->

        <?php if ($result_users->num_rows > 0): ?>
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Profile Picture</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $result_users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>
                            <?php if (!empty($user['profile_picture'])): ?>
                            <!-- Profile Picture -->
                            <img src="/dashboard/uploads/profile_pictures/<?php echo basename($user['profile_picture']); ?>"
                                alt="Profile Picture" class="profile-img" data-bs-toggle="modal"
                                data-bs-target="#profileModal<?php echo $user['id']; ?>">
                            <?php else: ?>
                            <span>No Picture</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- View More Button -->
                            <button class="btn btn-info" data-bs-toggle="modal"
                                data-bs-target="#userModal<?php echo $user['id']; ?>">View More</button>
                        </td>
                    </tr>

                    <!-- Modal for User Details -->
                    <div class="modal fade" id="userModal<?php echo $user['id']; ?>" tabindex="-1"
                        aria-labelledby="userModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="userModalLabel<?php echo $user['id']; ?>">User Details -
                                        <?php echo $user['username']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Full Name:</strong> <?php echo $user['full_name']; ?></p>
                                    <p><strong>Gender:</strong> <?php echo $user['gender']; ?></p>
                                    <p><strong>Department:</strong> <?php echo $user['department']; ?></p>
                                    <p><strong>Faculty:</strong> <?php echo $user['faculty']; ?></p>
                                    <p><strong>Level:</strong> <?php echo $user['level']; ?></p>
                                    <p><strong>Phone Number:</strong> <?php echo $user['phone_number']; ?></p>
                                    <p><strong>Address:</strong> <?php echo $user['address']; ?></p>
                                    <p><strong>Stays in Hostel:</strong> <?php echo $user['stays_in_hostel']; ?></p>
                                    <p><strong>About Me:</strong> <?php echo $user['about_me']; ?></p>
                                    <p><strong>Interests:</strong> <?php echo $user['interests']; ?></p>
                                    <p><strong>Relationship Status:</strong> <?php echo $user['relationship_status']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for Profile Picture -->
                    <div class="modal fade" id="profileModal<?php echo $user['id']; ?>" tabindex="-1"
                        aria-labelledby="profileModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="profileModalLabel<?php echo $user['id']; ?>">Profile
                                        Picture</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Full size image in the modal -->
                                    <img src="/dashboard/uploads/profile_pictures/<?php echo basename($user['profile_picture']); ?>"
                                        alt="Profile Picture">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-center text-danger">No user details found.</p>
        <?php endif; ?>

        <div class="footer">
            <p>&copy; 2025 UnimaidConnect. All rights reserved.</p>
        </div>
    </div>

    <!-- Bootstrap JS (Optional, for added responsiveness) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>