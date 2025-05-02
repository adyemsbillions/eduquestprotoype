<?php
include("db_connection.php");
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

if (isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $address = $_POST['address'];
    $department = $_POST['department'];
    $faculty = $_POST['faculty'];
    $level = $_POST['level'];
    $id_number = $_POST['id_number'];
    $phone_number = $_POST['phone_number'];
    $gender = $_POST['gender'];
    $stays_in_hostel = $_POST['stays_in_hostel'];
    $about_me = $_POST['about_me'];
    $interests = $_POST['interests'];
    $relationship_status = $_POST['relationship_status'];

    $sql = "UPDATE users SET 
                full_name = ?, 
                address = ?, 
                department = ?, 
                faculty = ?, 
                level = ?, 
                id_number = ?, 
                phone_number = ?, 
                gender = ?, 
                stays_in_hostel = ?, 
                about_me = ?, 
                interests = ?, 
                relationship_status = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssssssssssi", $full_name, $address, $department, $faculty, $level, $id_number, $phone_number, $gender, $stays_in_hostel, $about_me, $interests, $relationship_status, $user_id);

    $max_attempts = 2;
    $attempt = 0;
    $success = false;

    while ($attempt < $max_attempts && !$success) {
        try {
            if ($stmt->execute()) {
                $success = true;
                header("Location: profile.php");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1615) { // "Prepared statement needs to be re-prepared"
                $attempt++;
                if ($attempt < $max_attempts) {
                    $stmt->close();
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        die("Re-prepare failed: " . $conn->error);
                    }
                    $stmt->bind_param("ssssssssssssi", $full_name, $address, $department, $faculty, $level, $id_number, $phone_number, $gender, $stays_in_hostel, $about_me, $interests, $relationship_status, $user_id);
                } else {
                    die("Error after retries: " . $e->getMessage());
                }
            } else {
                die("Execution failed: " . $e->getMessage());
            }
        }
    }

    if (!$success) {
        echo "Error updating profile: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>