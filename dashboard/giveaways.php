<?php
session_start();
include('db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get referral count
$referral_query = "SELECT COUNT(*) as referral_count FROM users WHERE referred_by = $user_id";
$referral_result = mysqli_query($conn, $referral_query);
$referral_data = mysqli_fetch_assoc($referral_result);
$referral_count = $referral_data['referral_count'];

// Define allowed users
$allowed_users = [26, 3];

// Handle giveaway deletion (only for allowed users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_giveaway']) && in_array($user_id, $allowed_users)) {
    $giveaway_id = mysqli_real_escape_string($conn, $_POST['giveaway_id']);
    $delete_query = "DELETE FROM giveaways WHERE id = $giveaway_id AND user_id = $user_id";
    mysqli_query($conn, $delete_query);
}

// Only proceed if user has 5 or more referrals
if ($referral_count >= 5) {
    // Handle giveaway submission (only for allowed users)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_giveaway']) && in_array($user_id, $allowed_users)) {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/giveaways/';
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_type, $allowed_types) && in_array($file_ext, $allowed_exts) && $_FILES['image']['size'] <= 5000000) { // 5MB limit
                $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
                $image_path = $upload_dir . $image_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    // File uploaded successfully
                } else {
                    $image_path = null; // Reset if upload fails
                }
            }
        }

        $query = "INSERT INTO giveaways (user_id, title, description, image_path, created_at, views) 
                  VALUES ($user_id, '$title', '$description', " . ($image_path ? "'$image_path'" : "NULL") . ", NOW(), 0)";
        mysqli_query($conn, $query);
    }

    // Fetch user's giveaways
    $query = "SELECT * FROM giveaways WHERE user_id = $user_id ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giveaway Dashboard - Unimaid Resources</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background: #f4f4f4; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h1 { color: #7d2ae8; font-size: 28px; margin-bottom: 20px; text-align: center; }
        
        .referral-info { text-align: center; margin-bottom: 20px; color: #7d2ae8; font-size: 18px; }
        .locked-message { text-align: center; padding: 40px; color: #7d2ae8; }
        .locked-message i { font-size: 40px; margin-bottom: 20px; }
        
        .giveaways-list h2 { color: #7d2ae8; font-size: 22px; margin: 30px 0 15px; text-align: center; }
        .giveaway-card { background: #f8f0ff; border-radius: 8px; padding: 20px; margin-bottom: 20px; position: relative; }
        .giveaway-card h3 { color: #7d2ae8; margin-bottom: 10px; }
        .giveaway-card p { margin-bottom: 10px; line-height: 1.6; }
        .giveaway-image { max-width: 100%; border-radius: 5px; margin: 10px 0; }
        .views { color: #666; font-size: 14px; }
        .views i { margin-right: 5px; }
        .no-giveaways { text-align: center; color: #7d2ae8; font-style: italic; padding: 20px; }
        .delete-btn { position: absolute; top: 20px; right: 20px; background: #ff4444; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; transition: background 0.3s; }
        .delete-btn:hover { background: #cc0000; }
        
        @media (max-width: 600px) {
            .container { padding: 15px; }
            h1 { font-size: 24px; }
            .giveaway-card { padding: 15px; }
            .locked-message { padding: 20px; }
            .delete-btn { padding: 6px 12px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Your Giveaway Dashboard</h1>
        
        <div class="referral-info">
            <i class="fas fa-users"></i> Your Referrals: <?php echo $referral_count; ?>
        </div>

        <?php if ($referral_count < 5) { ?>
            <div class="locked-message">
                <i class="fas fa-lock"></i>
                <h2>Feature Locked</h2>
                <p>You need at least 5 referrals to unlock the giveaway feature.<br>
                Invite more friends to get started!</p>
            </div>
        <?php } else { ?>
            <?php if (in_array($user_id, $allowed_users)) { ?>
                <div class="giveaway-form" style="margin-bottom: 40px;">
                    <form method="POST" enctype="multipart/form-data">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; color: #7d2ae8; font-weight: 600;" for="title">Giveaway Title</label>
                            <input style="width: 100%; padding: 10px; border: 2px solid #7d2ae8; border-radius: 5px; font-size: 16px;" type="text" id="title" name="title" required maxlength="100" placeholder="Enter giveaway title">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; color: #7d2ae8; font-weight: 600;" for="description">Description</label>
                            <textarea style="width: 100%; padding: 10px; border: 2px solid #7d2ae8; border-radius: 5px; font-size: 16px; min-height: 100px; resize: vertical;" id="description" name="description" required placeholder="Describe your giveaway"></textarea>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; color: #7d2ae8; font-weight: 600;" for="image">Upload Image (Optional)</label>
                            <input style="width: 100%;" type="file" id="image" name="image" accept="image/*">
                        </div>
                        <button style="background: #7d2ae8; color: #fff; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px;" type="submit" name="submit_giveaway">Post Giveaway</button>
                    </form>
                </div>
            <?php } ?>

            <div class="giveaways-list">
                <h2>Your Posted Giveaways</h2>
                <?php if (mysqli_num_rows($result) > 0) { ?>
                    <?php while ($giveaway = mysqli_fetch_assoc($result)) { ?>
                        <div class="giveaway-card">
                            <h3><?php echo htmlspecialchars($giveaway['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($giveaway['description'])); ?></p>
                            <?php if ($giveaway['image_path']) { ?>
                                <img src="<?php echo htmlspecialchars($giveaway['image_path']); ?>" alt="Giveaway Image" class="giveaway-image">
                            <?php } ?>
                            <div class="views">
                                <i class="fas fa-eye"></i> <?php echo $giveaway['views']; ?> views
                                <br>
                                <small>Posted on: <?php echo date('M d, Y', strtotime($giveaway['created_at'])); ?></small>
                            </div>
                            <?php if (in_array($user_id, $allowed_users)) { ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this giveaway?');">
                                    <input type="hidden" name="giveaway_id" value="<?php echo $giveaway['id']; ?>">
                                    <button type="submit" name="delete_giveaway" class="delete-btn">Delete</button>
                                </form>
                            <?php } ?>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p class="no-giveaways">You haven't posted any giveaways yet.</p>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</body>
</html>