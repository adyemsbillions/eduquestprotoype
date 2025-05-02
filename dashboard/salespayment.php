<?php
include('db_connection.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details including sales_status and last approval date
$sql_user = "SELECT username, sales_status, updated_at FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_receipt']) && $user['sales_status'] != 1) {
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['receipt']['type'];
        if (!in_array($file_type, $allowed_types)) {
            $error = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
        } else {
            $file_name = 'receipt_' . $user_id . '_' . uniqid() . '.' . pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $upload_dir = 'uploads/receipts/';
            $file_path = $upload_dir . $file_name;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $file_path)) {
                $sql_request = "INSERT INTO sales_requests (user_id, receipt_path) VALUES (?, ?)";
                $stmt_request = $conn->prepare($sql_request);
                $stmt_request->bind_param("is", $user_id, $file_path);
                if ($stmt_request->execute()) {
                    $success = "Receipt submitted successfully. Awaiting admin approval.";
                } else {
                    $error = "Failed to submit request.";
                }
            } else {
                $error = "Failed to upload receipt.";
            }
        }
    } else {
        $error = "Please upload a receipt.";
    }
}

// Handle marketplace post submission (only if approved)
$marketplace_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post']) && $user['sales_status'] == 1) {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    $image_paths = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = "uploads/marketplace_images/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['images']['name'] as $key => $image_name) {
            $target_file = $upload_dir . basename($image_name);
            $image_info = getimagesize($_FILES['images']['tmp_name'][$key]);
            if ($image_info !== false) {
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_file)) {
                    $image_paths[] = $target_file;
                }
            } else {
                $marketplace_message = "<p style='color: red;'>Invalid file type. Please upload only image files (JPG, PNG, GIF).</p>";
                break;
            }
        }
    }

    $image_paths_json = json_encode($image_paths);
    $sql = "INSERT INTO marketplace_posts (user_id, item_name, description, price, images, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issds", $user_id, $item_name, $description, $price, $image_paths_json);
    if ($stmt->execute()) {
        $marketplace_message = "<p style='color: green;'>Your post has been submitted and is awaiting admin approval.</p>";
    } else {
        $marketplace_message = "<p style='color: red;'>Failed to submit your post. Please try again later.</p>";
    }
}

// Fetch user's latest request status
$sql_request = "SELECT request_status FROM sales_requests WHERE user_id = ? ORDER BY requested_at DESC LIMIT 1";
$stmt_request = $conn->prepare($sql_request);
$stmt_request->bind_param("i", $user_id);
$stmt_request->execute();
$request_result = $stmt_request->get_result();
$request_status = $request_result->num_rows > 0 ? $request_result->fetch_assoc()['request_status'] : null;

// Calculate countdown (30 days from last update if approved)
$days_left = null;
if ($user['sales_status'] == 1 && $user['updated_at']) {
    $approval_date = new DateTime($user['updated_at']);
    $expiry_date = clone $approval_date;
    $expiry_date->modify('+30 days');
    $now = new DateTime();
    $interval = $now->diff($expiry_date);
    $days_left = $interval->invert ? 0 : $interval->days;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Payment</title>
    <style>
        :root {
            --primary: #6a1b9a;
            --primary-dark: #4a148c;
            --secondary: #e3e3e3;
            --text: #333;
            --white: #fff;
            --light-bg: #f8f9fa;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: var(--light-bg);
            color: var(--text);
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 12px var(--shadow);
        }

        h1 {
            font-size: 28px;
            color: var(--primary);
            text-align: center;
            margin-bottom: 20px;
        }

        .payment-details {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .payment-details p {
            margin: 5px 0;
            font-size: 16px;
        }

        .upload-form {
            margin-top: 20px;
        }

        .upload-form input[type="file"] {
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid var(--secondary);
            border-radius: 5px;
            width: 100%;
        }

        .upload-form button, .marketplace-form button {
            background: var(--primary);
            color: var(--white);
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .upload-form button:hover, .marketplace-form button:hover {
            background: var(--primary-dark);
        }

        .status {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .status.pending { background: #fff3cd; color: #856404; }
        .status.approved { background: #d4edda; color: #155724; }
        .status.rejected { background: #f8d7da; color: #721c24; }
        .status.suspended { background: #e2e3e5; color: #383d41; }
        .status.cant-sell { background: #e2e3e5; color: #383d41; }

        .countdown {
            margin-top: 10px;
            font-size: 16px;
            text-align: center;
            color: #555;
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }

        .marketplace-form .form-group {
            margin-bottom: 15px;
        }

        .marketplace-form .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .marketplace-form .form-group input, 
        .marketplace-form .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .marketplace-form .form-group input[type="file"] {
            padding: 5px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
                width: 100%;
            }

            h1 {
                font-size: 24px;
            }

            .payment-details p, .countdown {
                font-size: 14px;
            }

            .upload-form button, .marketplace-form button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sales Payment Request</h1>

        <div class="status <?php echo $user['sales_status'] == 0 ? 'cant-sell' : ($user['sales_status'] == 1 ? 'approved' : 'suspended'); ?>">
            <p><strong>Your Sales Status:</strong> 
                <?php 
                echo $user['sales_status'] == 0 ? "Can't Sell" : ($user['sales_status'] == 1 ? "Can Sell" : "Suspended"); 
                if ($request_status) {
                    echo " (Request: " . ucfirst($request_status) . ")";
                }
                ?>
            </p>
        </div>

        <?php if ($user['sales_status'] == 1 && $days_left !== null): ?>
            <div class="countdown">
                <p><strong>Approval Expires In:</strong> <?php echo $days_left; ?> days</p>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($user['sales_status'] != 1): ?>
            <div class="payment-details">
                <p><strong>Account Name:</strong> Eigege Gods'time Enifu</p>
                <p><strong>Account Number:</strong>9014041436</p>
                <p><strong>Bank Name:</strong> Opay</p>
            </div>

            <form class="upload-form" method="POST" enctype="multipart/form-data">
                <input type="file" name="receipt" accept="image/*" required>
                <button type="submit" name="submit_receipt">Submit Receipt</button>
            </form>
        <?php else: ?>
            <form class="marketplace-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="item_name">Item Name</label>
                    <input type="text" id="item_name" name="item_name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Price ($)</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="images">Upload Images (Multiple allowed)</label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple>
                </div>

                <div class="form-group">
                    <button type="submit" name="submit_post">Submit Post</button>
                </div>
            </form>

            <?php if ($marketplace_message): ?>
                <div class="message"><?php echo $marketplace_message; ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>