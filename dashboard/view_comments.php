<?php
ob_start();
include("db_connection.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle reply submission via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_text'])) {
    $post_id = $_POST['post_id'] ?? null;
    $comment_id = $_POST['comment_id'] ?? null;
    $reply_text = trim($_POST['reply_text']) ?? '';

    if (!$post_id || !$comment_id || empty($reply_text)) {
        echo json_encode(["status" => "error", "message" => "Missing required data"]);
        exit();
    }

    // Check for duplicate submission (excluding user_id = 1)
    $sql_check_duplicate = "SELECT COUNT(*) FROM replies WHERE comment_id = ? AND user_id = ? AND reply_text = ? AND user_id != 1 AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
    $stmt_check = $conn->prepare($sql_check_duplicate);
    $stmt_check->bind_param("iis", $comment_id, $user_id, $reply_text);
    $stmt_check->execute();
    $duplicate_count = $stmt_check->get_result()->fetch_row()[0];
    if ($duplicate_count > 0) {
        echo json_encode(["status" => "error", "message" => "Duplicate reply detected"]);
        exit();
    }

    // Insert the reply
    $sql_insert_reply = "INSERT INTO replies (comment_id, post_id, user_id, reply_text, created_at) 
                         VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql_insert_reply);
    $stmt->bind_param("iiis", $comment_id, $post_id, $user_id, $reply_text);

    if ($stmt->execute()) {
        $reply_id = $stmt->insert_id;
        $sql_get_reply = "SELECT r.reply_text, u.username, r.created_at
                          FROM replies r
                          JOIN users u ON r.user_id = u.id
                          WHERE r.reply_id = ? AND r.user_id != 1";
        $stmt_reply = $conn->prepare($sql_get_reply);
        $stmt_reply->bind_param("i", $reply_id);
        $stmt_reply->execute();
        $reply = $stmt_reply->get_result()->fetch_assoc();

        if ($reply) {
            echo json_encode([
                "status" => "success",
                "reply_html" => '
                    <div class="reply">
                        <strong>' . htmlspecialchars($reply['username']) . '</strong> replied:
                        <p>' . nl2br(htmlspecialchars($reply['reply_text'])) . '</p>
                        <span class="reply-time">' . date('F j, Y, g:i a', strtotime($reply['created_at'])) . '</span>
                    </div>'
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Reply not found or excluded"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to submit reply: " . $stmt->error]);
    }
    exit();
}

// Validate and fetch post data
$post_id = isset($_GET['post_id']) && is_numeric($_GET['post_id']) ? $_GET['post_id'] : null;
if (!$post_id) {
    echo "Invalid post ID.";
    exit();
}

$sql_post = "SELECT p.post_content, p.created_at, u.username, p.media_url, p.media_type
             FROM posts p
             JOIN users u ON p.user_id = u.id 
             WHERE p.post_id = ? AND p.user_id != 1";
$stmt = $conn->prepare($sql_post);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result_post = $stmt->get_result();

if ($result_post->num_rows > 0) {
    $post = $result_post->fetch_assoc();
} else {
    echo "No post found or post is excluded.";
    exit();
}

// Fetch comments (excluding user_id = 1)
$sql_comments = "SELECT c.comment_id, c.comment_text, u.username, c.created_at
                 FROM comments c
                 JOIN users u ON c.user_id = u.id
                 WHERE c.post_id = ? AND c.user_id != 1 ORDER BY c.created_at DESC";
$stmt_comments = $conn->prepare($sql_comments);
$stmt_comments->bind_param("i", $post_id);
$stmt_comments->execute();
$result_comments = $stmt_comments->get_result();

// Count comments (excluding user_id = 1)
$sql_comment_count = "SELECT COUNT(*) AS comment_count FROM comments WHERE post_id = ? AND user_id != 1";
$stmt_comment_count = $conn->prepare($sql_comment_count);
$stmt_comment_count->bind_param("i", $post_id);
$stmt_comment_count->execute();
$comment_count = $stmt_comment_count->get_result()->fetch_assoc()['comment_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Comments</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background: linear-gradient(to bottom, #f9f9f9, #e9ecef); color: #333; line-height: 1.6; padding: 20px; min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); padding: 25px; }
        h2 { font-size: 26px; color: #6a1b9a; margin-bottom: 20px; text-align: center; }
        .post-content { font-size: 18px; color: #444; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #6a1b9a; }
        .post-meta { font-size: 14px; color: #666; margin-bottom: 20px; }
        .post-media img { max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 20px; }
        .post-media video { max-width: 100%; border-radius: 8px; margin-bottom: 20px; }
        h3 { font-size: 20px; color: #333; margin-bottom: 15px; }
        .comment { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 5px solid #6a1b9a; transition: background 0.2s ease; }
        .comment:hover { background: #f1f3f5; }
        .comment strong { color: #6a1b9a; font-size: 16px; }
        .comment p { margin: 5px 0; color: #444; }
        .comment small { color: #888; font-size: 12px; }
        .replies { margin-top: 10px; padding-left: 20px; }
        .reply { background: #f1f3f5; padding: 10px; margin-bottom: 10px; border-radius: 6px; border-left: 3px solid #8e24aa; }
        .reply strong { color: #6a1b9a; font-size: 14px; }
        .reply p { color: #555; font-size: 14px; margin: 5px 0; }
        .reply-time { font-size: 12px; color: #888; }
        .reply-form { margin-top: 10px; position: relative; }
        textarea { width: 100%; padding: 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 5px; background: #fff; resize: vertical; transition: border-color 0.3s ease; }
        textarea:focus { border-color: #6a1b9a; outline: none; }
        button { padding: 8px 15px; background: #6a1b9a; color: #fff; font-size: 14px; border: none; border-radius: 5px; cursor: pointer; margin-top: 8px; transition: background 0.3s ease; }
        button:hover { background: #8e24aa; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .loading { font-size: 12px; color: #6a1b9a; margin-top: 5px; display: none; }
    </style>
</head>
<body>
<div class="container">
    <h2>Post by <?php echo htmlspecialchars($post['username']); ?></h2>
    <div class="post-content"><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></div>
    <p class="post-meta"><strong>Posted on:</strong> <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></p>

    <?php if (!empty($post['media_url'])): ?>
        <div class="post-media">
            <?php if ($post['media_type'] == 'image'): ?>
                <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Image">
            <?php elseif ($post['media_type'] == 'video'): ?>
                <video controls>
                    <source src="<?php echo htmlspecialchars($post['media_url']); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <h3>Comments (<?php echo $comment_count; ?>)</h3>
    <?php if ($result_comments->num_rows > 0): ?>
        <?php while ($comment = $result_comments->fetch_assoc()): ?>
            <div class="comment">
                <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                <small><?php echo date('F j, Y, g:i a', strtotime($comment['created_at'])); ?></small>

                <div class="replies" id="replies-<?php echo $comment['comment_id']; ?>">
                    <?php
                    $sql_replies = "SELECT r.reply_text, u.username, r.created_at
                                    FROM replies r
                                    JOIN users u ON r.user_id = u.id
                                    WHERE r.comment_id = ? AND r.user_id != 1 ORDER BY r.created_at DESC";
                    $stmt_replies = $conn->prepare($sql_replies);
                    $stmt_replies->bind_param("i", $comment['comment_id']);
                    $stmt_replies->execute();
                    $result_replies = $stmt_replies->get_result();

                    while ($reply = $result_replies->fetch_assoc()): ?>
                        <div class="reply">
                            <strong><?php echo htmlspecialchars($reply['username']); ?></strong>
                            <p><?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?></p>
                            <span class="reply-time"><?php echo date('F j, Y, g:i a', strtotime($reply['created_at'])); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>

                <form method="POST" class="reply-form" data-comment-id="<?php echo $comment['comment_id']; ?>" data-post-id="<?php echo $post_id; ?>">
                    <textarea name="reply_text" placeholder="Add a reply..." required></textarea>
                    <button type="submit">Reply</button>
                    <div class="loading">Posting reply...</div>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No comments yet.</p>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    let isSubmitting = false;

    $(".reply-form").on("submit", function(e) {
        e.preventDefault();

        if (isSubmitting) return;

        var $form = $(this);
        var commentId = $form.data("comment-id");
        var postId = $form.data("post-id");
        var replyText = $form.find("textarea").val().trim();

        if (!replyText) return;

        isSubmitting = true;
        var $button = $form.find("button");
        var $loading = $form.find(".loading");
        $button.prop("disabled", true);
        $loading.show();

        $.ajax({
            url: window.location.href,
            type: "POST",
            data: {
                post_id: postId,
                comment_id: commentId,
                reply_text: replyText
            },
            dataType: "json",
            success: function(response) {
                if (response.status === "success") {
                    $("#replies-" + commentId).prepend(response.reply_html);
                    $form.find("textarea").val("");
                } else {
                    alert(response.message || "Failed to submit reply");
                }
                $button.prop("disabled", false);
                $loading.hide();
                isSubmitting = false;
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert("An error occurred: " + (xhr.responseJSON?.message || "Unknown error"));
                $button.prop("disabled", false);
                $loading.hide();
                isSubmitting = false;
            }
        });
    });
});
</script>
</body>
</html>