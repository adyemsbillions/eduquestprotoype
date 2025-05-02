<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You need to log in first.");
}

$groupId = $_GET['id']; // Get the group ID from the URL
$userId = $_SESSION['user_id']; // Get the logged-in user ID

// Database connection
$conn = new mysqli('localhost', 'unimaid9_unimaidresources', '#adyems123AD', 'unimaid9_unimaidresources');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch group information
$sqlGroup = "SELECT id, name, description, can_post FROM `groups` WHERE id = ?";
$stmtGroup = $conn->prepare($sqlGroup);
$stmtGroup->bind_param("i", $groupId);
$stmtGroup->execute();
$groupResult = $stmtGroup->get_result();
$group = $groupResult->fetch_assoc();

// Fetch the user's reps_status
$sqlUser = "SELECT reps_status FROM users WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$user = $userResult->fetch_assoc();
$isApproved = ($user['reps_status'] === 'approved');

// Fetch the number of members in the group
$sqlMembers = "SELECT COUNT(*) AS member_count FROM group_members WHERE group_id = ?";
$stmtMembers = $conn->prepare($sqlMembers);
$stmtMembers->bind_param("i", $groupId);
$stmtMembers->execute();
$membersResult = $stmtMembers->get_result();
$members = $membersResult->fetch_assoc();

// Fetch posts in the group with reply count
$sqlPosts = "SELECT p.id, p.post_text, p.image_url, p.created_at, u.username,
             (SELECT COUNT(*) FROM group_replies r WHERE r.post_id = p.id) AS reply_count
             FROM group_posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.group_id = ?
             ORDER BY p.created_at DESC";
$stmtPosts = $conn->prepare($sqlPosts);
$stmtPosts->bind_param("i", $groupId);
$stmtPosts->execute();
$postsResult = $stmtPosts->get_result();

// Handle toggle action for enabling/disabling posting
if ($isApproved && isset($_POST['toggle_posting'])) {
    $newCanPost = ($group['can_post'] == 1) ? 0 : 1;
    $sqlUpdate = "UPDATE `groups` SET can_post = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ii", $newCanPost, $groupId);
    if ($stmtUpdate->execute()) {
        header("Location: group.php?id=" . $groupId);
        exit();
    } else {
        echo "Error updating posting status: " . $stmtUpdate->error;
    }
}

// Handle AJAX post submission
if (isset($_POST['post_text']) && !isset($_POST['reply_text']) && !isset($_POST['toggle_posting']) && $group['can_post'] == 1) {
    $postText = trim($_POST['post_text']);
    $fileUrl = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileType = $_FILES['file']['type'];
        $uploadDir = 'uploads/';

        // Ensure upload directory exists and is writable
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!is_writable($uploadDir)) {
            echo json_encode(['success' => false, 'error' => 'Upload directory is not writable']);
            exit();
        }

        // Sanitize file name
        $fileName = preg_replace("/[^A-Za-z0-9\.\-]/", '_', $fileName);
        
        if ($fileType === 'application/pdf') {
            $fileUrl = $uploadDir . 'unimaidresources-' . $fileName;
        } else {
            $fileUrl = $uploadDir . uniqid() . '-' . $fileName;
        }

        // Validate file type and move
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($fileTmpPath, $fileUrl)) {
                // File moved successfully
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to move uploaded file',
                    'tmp_path' => $fileTmpPath,
                    'dest_path' => $fileUrl
                ]);
                exit();
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid file format. Allowed: jpg, png, gif, pdf',
                'fileType' => $fileType
            ]);
            exit();
        }
    } else if (!empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
        echo json_encode([
            'success' => false,
            'error' => 'File upload error: ' . $_FILES['file']['error']
        ]);
        exit();
    }

    $sqlInsertPost = "INSERT INTO group_posts (group_id, user_id, post_text, image_url) VALUES (?, ?, ?, ?)";
    $stmtInsertPost = $conn->prepare($sqlInsertPost);
    $stmtInsertPost->bind_param("iiss", $groupId, $userId, $postText, $fileUrl);
    if ($stmtInsertPost->execute()) {
        $postId = $stmtInsertPost->insert_id;
        $sqlFetchPost = "SELECT p.id, p.post_text, p.image_url, p.created_at, u.username 
                        FROM group_posts p 
                        JOIN users u ON p.user_id = u.id 
                        WHERE p.id = ?";
        $stmtFetchPost = $conn->prepare($sqlFetchPost);
        $stmtFetchPost->bind_param("i", $postId);
        $stmtFetchPost->execute();
        $postResult = $stmtFetchPost->get_result();
        $post = $postResult->fetch_assoc();
        echo json_encode([
            'success' => true,
            'post' => [
                'id' => $post['id'],
                'username' => htmlspecialchars($post['username']),
                'post_text' => nl2br(htmlspecialchars($post['post_text'])),
                'image_url' => $post['image_url'],
                'created_at' => $post['created_at']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmtInsertPost->error]);
    }
    exit();
}

// Handle AJAX reply submission
if (isset($_POST['reply_text']) && isset($_POST['post_id'])) {
    $postId = $_POST['post_id'];
    $replyText = trim($_POST['reply_text']);

    $sqlInsertReply = "INSERT INTO group_replies (post_id, user_id, reply_text) VALUES (?, ?, ?)";
    $stmtInsertReply = $conn->prepare($sqlInsertReply);
    $stmtInsertReply->bind_param("iis", $postId, $userId, $replyText);
    if ($stmtInsertReply->execute()) {
        $replyId = $stmtInsertReply->insert_id;
        $sqlFetchReply = "SELECT r.reply_text, r.created_at, u.username 
                         FROM group_replies r 
                         JOIN users u ON r.user_id = u.id 
                         WHERE r.id = ?";
        $stmtFetchReply = $conn->prepare($sqlFetchReply);
        $stmtFetchReply->bind_param("i", $replyId);
        $stmtFetchReply->execute();
        $replyResult = $stmtFetchReply->get_result();
        $reply = $replyResult->fetch_assoc();
        echo json_encode([
            'success' => true,
            'reply' => [
                'username' => htmlspecialchars($reply['username']),
                'reply_text' => nl2br(htmlspecialchars($reply['reply_text'])),
                'created_at' => $reply['created_at']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmtInsertReply->error]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group: <?php echo htmlspecialchars($group['name']); ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { width: 80%; margin: 0 auto; padding: 20px; background-color: #fff; }
        h1 { font-size: 24px; color: #333; }
        .group-info { background-color: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .group-info p { font-size: 16px; margin: 10px 0; }
        .members-count { font-weight: bold; font-size: 18px; }
        .posting-status { font-size: 16px; color: <?php echo $group['can_post'] ? 'green' : 'red'; ?>; font-weight: bold; }
        .toggle-form { margin-top: 10px; }
        .toggle-button { background-color: <?php echo $group['can_post'] ? '#dc3545' : '#28a745'; ?>; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; }
        .toggle-button:hover { background-color: <?php echo $group['can_post'] ? '#c82333' : '#218838'; ?>; }
        .post-form textarea { width: 100%; height: 100px; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; box-sizing: border-box; }
        .post-form input[type="file"] { margin-bottom: 10px; }
        .post-form input[type="submit"] { background-color: purple; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .post-form input[type="submit"]:hover { background-color: darkpurple; }
        .post { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; background-color: #fff; position: relative; overflow: hidden; }
        .post img { max-width: 100%; height: auto; border-radius: 5px; margin-top: 10px; }
        .post p { font-size: 14px; color: #333; }
        .post .username { font-weight: bold; color: purple; }
        .post .created-at { font-size: 12px; color: #777; }
        .post .reply-count { font-size: 12px; color: #555; margin-top: 5px; }
        .reply-form { display: none; padding: 10px; background-color: #f9f9f9; border-top: 1px solid #ddd; }
        .reply-form textarea { width: 100%; height: 60px; padding: 5px; border: 1px solid #ccc; border-radius: 5px; }
        .reply-form button { background-color: purple; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
        .reply-form button:hover { background-color: darkpurple; }
        .replies { margin-top: 10px; }
        .reply { padding: 5px; border-bottom: 1px solid #eee; }
        .reply.hidden { display: none; }
        .reply .username { font-size: 12px; }
        .reply .created-at { font-size: 10px; }
        .see-more, .see-less { color: purple; cursor: pointer; font-size: 12px; margin-top: 5px; display: none; }
        .see-more:hover, .see-less:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="container">
        <div class="group-info">
            <h1>Group: <?php echo htmlspecialchars($group['name']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($group['description'])); ?></p>
            <p class="members-count">Members: <?php echo $members['member_count']; ?></p>
            <p class="posting-status">Posting: <?php echo $group['can_post'] ? 'Enabled' : 'Disabled'; ?></p>

            <?php if ($isApproved): ?>
                <form method="POST" class="toggle-form">
                    <input type="hidden" name="toggle_posting" value="1">
                    <input type="submit" class="toggle-button" value="<?php echo $group['can_post'] ? 'Disable Posting' : 'Enable Posting'; ?>">
                </form>
            <?php endif; ?>
        </div>

        <?php if ($group['can_post'] == 1): ?>
            <h2>Post a Message</h2>
            <form action="group.php?id=<?php echo $groupId; ?>" method="POST" enctype="multipart/form-data" class="post-form">
                <textarea name="post_text" placeholder="Write your message here" required></textarea><br>
                <input type="file" name="file" accept="image/jpeg,image/png,image/gif,application/pdf"><br>
                <input type="submit" value="Post">
            </form>
        <?php endif; ?>

        <h2>Group Posts</h2>
        <?php
        if ($postsResult->num_rows > 0) {
            while ($post = $postsResult->fetch_assoc()) {
                echo "<div class='post' data-post-id='" . $post['id'] . "'>";
                echo "<p class='username'>" . htmlspecialchars($post['username']) . ":</p>";
                if ($post['image_url']) {
                    if (strpos($post['image_url'], '.pdf') !== false) {
                        echo "<p><a href='download.php?file=" . urlencode($post['image_url']) . "'>Download PDF</a></p>";
                    } else {
                        echo "<img src='" . htmlspecialchars($post['image_url']) . "' alt='Post Image'>";
                    }
                }
                echo "<p>" . nl2br(htmlspecialchars($post['post_text'])) . "</p>";
                echo "<p class='created-at'>Posted on: " . $post['created_at'] . "</p>";
                echo "<p class='reply-count'>Replies: " . $post['reply_count'] . "</p>";

                // Reply form
                echo "<div class='reply-form'>";
                echo "<textarea placeholder='Type your reply...' required></textarea>";
                echo "<button>Reply</button>";
                echo "</div>";

                // Replies container
                echo "<div class='replies'>";
                $sqlReplies = "SELECT r.reply_text, r.created_at, u.username 
                              FROM group_replies r 
                              JOIN users u ON r.user_id = u.id 
                              WHERE r.post_id = ? 
                              ORDER BY r.created_at ASC";
                $stmtReplies = $conn->prepare($sqlReplies);
                $stmtReplies->bind_param("i", $post['id']);
                $stmtReplies->execute();
                $repliesResult = $stmtReplies->get_result();
                $replyCount = 0;
                while ($reply = $repliesResult->fetch_assoc()) {
                    $replyCount++;
                    $hiddenClass = ($replyCount > 3) ? 'hidden' : '';
                    echo "<div class='reply $hiddenClass'>";
                    echo "<p class='username'>" . htmlspecialchars($reply['username']) . ":</p>";
                    echo "<p>" . nl2br(htmlspecialchars($reply['reply_text'])) . "</p>";
                    echo "<p class='created-at'>" . $reply['created_at'] . "</p>";
                    echo "</div>";
                }
                if ($replyCount > 3) {
                    echo "<span class='see-more'>See More (" . ($replyCount - 3) . " more)</span>";
                    echo "<span class='see-less' style='display: none;'>See Less</span>";
                }
                echo "</div>";

                echo "</div>";
            }
        } else {
            echo "<p>No posts yet in this group.</p>";
        }
        ?>

    </div>

    <script>
        $(document).ready(function() {
            // Toggle reply form on post click
            $('.post').on('click', function(e) {
                if (!$(e.target).closest('.reply-form, .replies, .see-more, .see-less').length) {
                    $(this).find('.reply-form').slideToggle();
                }
            });

            // Handle AJAX post submission
            $('.post-form').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                
                // Debug form data
                for (var pair of formData.entries()) {
                    console.log(pair[0]+ ': '+ pair[1]); 
                }

                $.ajax({
                    url: 'group.php?id=<?php echo $groupId; ?>',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    cache: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Server response:', response);
                        if (response.success) {
                            var postHtml = `
                                <div class="post" data-post-id="${response.post.id}">
                                    <p class="username">${response.post.username}:</p>
                                    ${response.post.image_url ? (response.post.image_url.endsWith('.pdf') ? 
                                        `<p><a href="download.php?file=${encodeURIComponent(response.post.image_url)}">Download PDF</a></p>` : 
                                        `<img src="${response.post.image_url}" alt="Post Image">`) : ''}
                                    <p>${response.post.post_text}</p>
                                    <p class="created-at">Posted on: ${response.post.created_at}</p>
                                    <p class="reply-count">Replies: 0</p>
                                    <div class="reply-form" style="display: none;">
                                        <textarea placeholder="Type your reply..." required></textarea>
                                        <button>Reply</button>
                                    </div>
                                    <div class="replies"></div>
                                </div>`;
                            $('.container').find('h2:contains("Group Posts")').after(postHtml);
                            $('.post-form textarea').val('');
                            $('.post-form input[type="file"]').val('');
                        } else {
                            alert('Error: ' + response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', status, error);
                        alert('Failed to submit post: ' + error);
                    }
                });
            });

            // Handle reply submission via AJAX
            $(document).on('click', '.reply-form button', function(e) {
                e.preventDefault();
                var $post = $(this).closest('.post');
                var postId = $post.data('post-id');
                var replyText = $post.find('.reply-form textarea').val().trim();

                if (replyText === '') return;

                $.ajax({
                    url: 'group.php?id=<?php echo $groupId; ?>',
                    type: 'POST',
                    data: {
                        post_id: postId,
                        reply_text: replyText
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            var $replies = $post.find('.replies');
                            var replyHtml = `
                                <div class="reply">
                                    <p class="username">${response.reply.username}:</p>
                                    <p>${response.reply.reply_text}</p>
                                    <p class="created-at">${response.reply.created_at}</p>
                                </div>`;
                            $replies.append(replyHtml);
                            var replyCount = $replies.find('.reply').length;
                            $post.find('.reply-count').text('Replies: ' + replyCount);
                            $post.find('.reply-form textarea').val('');
                            $post.find('.reply-form').slideUp();

                            // Update folding logic
                            updateRepliesVisibility($replies, replyCount);
                        } else {
                            alert('Error: ' + response.error);
                        }
                    },
                    error: function() {
                        alert('Failed to submit reply.');
                    }
                });
            });

            // Function to manage reply visibility
            function updateRepliesVisibility($replies, replyCount) {
                if (replyCount > 3) {
                    $replies.find('.reply').slice(3).hide().addClass('hidden');
                    var $seeMore = $replies.find('.see-more');
                    var $seeLess = $replies.find('.see-less');
                    if ($seeMore.length === 0) {
                        $replies.append(`<span class="see-more">See More (${replyCount - 3} more)</span>`);
                        $replies.append(`<span class="see-less" style="display: none;">See Less</span>`);
                    } else {
                        $seeMore.text(`See More (${replyCount - 3} more)`).show();
                        $seeLess.hide();
                    }
                } else {
                    $replies.find('.see-more, .see-less').hide();
                }
            }

            // Initialize reply visibility on page load
            $('.replies').each(function() {
                var $replies = $(this);
                var replyCount = $replies.find('.reply').length;
                updateRepliesVisibility($replies, replyCount);
            });

            // Handle "See More" click
            $(document).on('click', '.see-more', function() {
                var $replies = $(this).closest('.replies');
                $replies.find('.reply.hidden').slideDown(function() {
                    $(this).removeClass('hidden');
                });
                $(this).hide();
                $replies.find('.see-less').show();
            });

            // Handle "See Less" click
            $(document).on('click', '.see-less', function() {
                var $replies = $(this).closest('.replies');
                $replies.find('.reply').slice(3).slideUp(function() {
                    $(this).addClass('hidden');
                });
                $(this).hide();
                $replies.find('.see-more').show();
            });
        });
    </script>

</body>
</html>

<?php
$conn->close();
?>