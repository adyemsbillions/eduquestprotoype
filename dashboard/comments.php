<?php
include 'db_connection.php';
session_start();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $response = ['success' => false, 'message' => '', 'data' => null];

    // Handle comment/reply posting
    if (isset($_POST['comment_content']) || (isset($_POST['reply_content']) && isset($_POST['parent_id']))) {
        $content = $_POST['comment_content'] ?? $_POST['reply_content'];
        $user_id = $_SESSION['user_id']; // Assuming user is logged in
        $post_id = $_POST['post_id'];
        $parent_id = $_POST['parent_id'] ?? null;

        $image_path = null;
        $file_key = isset($_POST['comment_content']) ? 'comment_image' : 'reply_image';
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
            $image_name = $_FILES[$file_key]['name'];
            $image_tmp = $_FILES[$file_key]['tmp_name'];
            $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
            $image_path = 'uploads/replies/' . uniqid() . '.' . $image_ext;
            move_uploaded_file($image_tmp, $image_path);
        }

        $stmt = $conn->prepare("INSERT INTO fcomment (content, user_id, post_id, parent_id, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiss", $content, $user_id, $post_id, $parent_id, $image_path);
        if ($stmt->execute()) {
            $comment_id = $conn->insert_id;
            $response['success'] = true;
            $response['data'] = [
                'comment_id' => $comment_id,
                'content' => $content,
                'image_path' => $image_path,
                'username' => $_SESSION['username'], // Assuming username is stored in session
                'timestamp' => date('Y-m-d H:i:s'),
                'parent_id' => $parent_id
            ];
        } else {
            $response['message'] = 'Failed to post.';
        }
        echo json_encode($response);
        exit;
    }
}

// Ensure the post ID is set for initial page load
if (!isset($_GET['post_id'])) {
    die('Post ID is required');
}

$post_id = $_GET['post_id'];

// Query to fetch post details
$query = "SELECT fpost.content AS post_content, fpost.timestamp AS post_timestamp, 
                 fpost.image_path AS post_image, users.username, users.club
          FROM fpost 
          JOIN users ON fpost.user_id = users.id
          WHERE fpost.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post_result = $stmt->get_result();
$post = $post_result->fetch_assoc();

// Function to fetch comments and replies recursively
function fetchComments($conn, $post_id, $parent_id = null) {
    $query = "SELECT fcomment.id AS comment_id, fcomment.content AS comment_content, 
                     fcomment.timestamp AS comment_timestamp, fcomment.image_path AS comment_image, 
                     fcomment.parent_id, users.username
              FROM fcomment
              JOIN users ON fcomment.user_id = users.id
              WHERE fcomment.post_id = ? AND " . ($parent_id === null ? "fcomment.parent_id IS NULL" : "fcomment.parent_id = ?") . "
              ORDER BY fcomment.timestamp ASC";
    $stmt = $conn->prepare($query);
    if ($parent_id === null) {
        $stmt->bind_param("i", $post_id);
    } else {
        $stmt->bind_param("ii", $post_id, $parent_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $row['replies'] = fetchComments($conn, $post_id, $row['comment_id']);
        $comments[] = $row;
    }
    return $comments;
}

$comments = fetchComments($conn, $post_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Comments</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    /* Reset basic styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Body styling */
    body {
        font-family: 'Segoe UI', 'Arial', sans-serif;
        background: linear-gradient(135deg, #f3e8ff 0%, #e2d1ff 100%);
        color: #2d3748;
        line-height: 1.6;
        min-height: 100vh;
    }

    /* Main container */
    .container {
        width: 90%;
        max-width: 1200px;
        margin: 2rem auto;
        padding: 1.5rem;
        background: #ffffff;
        border-radius: 1rem;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
    }

    /* Header styling */
    h1 {
        text-align: center;
        font-size: clamp(1.5rem, 5vw, 2rem);
        color: #6b48ff;
        margin-bottom: 1.5rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Post styling with Flexbox */
    .post {
        width: 100%;
        background: #ffffff;
        padding: 1.5rem;
        border-radius: 1rem;
        box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.06);
        margin-bottom: 2rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .post:hover {
        transform: translateY(-0.25rem);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
    }

    .post-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .post h2 {
        font-size: clamp(1.25rem, 4vw, 1.5rem);
        color: #6b48ff;
        font-weight: 600;
    }

    .post .username {
        font-weight: 700;
        font-size: clamp(1rem, 3vw, 1.125rem);
        color: #2d3748;
    }

    .post .timestamp {
        font-size: clamp(0.75rem, 2.5vw, 0.9rem);
        color: #718096;
    }

    .post .content {
        font-size: clamp(0.875rem, 3vw, 1rem);
        color: #4a5568;
    }

    .post img {
        width: 100%;
        height: auto;
        border-radius: 0.75rem;
        border: 1px solid #e9d8fd;
    }

    /* Comment form styling (no flexbox) */
    .comment-form {
        width: 100%;
        margin-top: 1.5rem;
        background: #faf5ff;
        padding: 1.25rem;
        border-radius: 0.75rem;
    }

    .comment-form textarea {
        width: 100%;
        padding: 0.875rem;
        font-size: clamp(0.875rem, 2.5vw, 0.9375rem);
        border-radius: 0.5rem;
        border: 1px solid #e9d8fd;
        resize: vertical;
        margin-bottom: 0.75rem;
        background: #fff;
        transition: border-color 0.3s ease;
    }

    .comment-form textarea:focus {
        outline: none;
        border-color: #6b48ff;
        box-shadow: 0 0 0.3125rem rgba(107, 72, 255, 0.3);
    }

    .comment-form input[type="file"] {
        width: 100%;
        margin-bottom: 0.75rem;
        font-size: clamp(0.75rem, 2vw, 0.875rem);
    }

    .comment-form button {
        width: 100%;
        background: #6b48ff;
        color: #fff;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-size: clamp(0.875rem, 2.5vw, 1rem);
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
    }

    .comment-form button:hover {
        background: #5533d6;
        transform: translateY(-0.125rem);
    }

    /* Comment styling (no flexbox) */
    .comment {
        width: 100%;
        margin-top: 1.5rem;
        padding: 1.25rem;
        background: #ffffff;
        border-radius: 0.75rem;
        box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.06);
        transition: transform 0.2s ease;
    }

    .comment:hover {
        transform: translateY(-0.1875rem);
    }

    .comment .username {
        font-weight: 600;
        color: #6b48ff;
        font-size: clamp(0.875rem, 2.5vw, 1rem);
    }

    .comment .timestamp {
        font-size: clamp(0.75rem, 2vw, 0.9rem);
        color: #718096;
        margin-top: 0.25rem;
    }

    .comment .content {
        margin-top: 0.75rem;
        font-size: clamp(0.875rem, 2.5vw, 0.9375rem);
        color: #4a5568;
    }

    .comment img {
        width: 100%;
        max-width: 100%;
        height: auto;
        margin-top: 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid #e9d8fd;
    }

    /* Reply form styling (no flexbox) */
    .reply-form {
        width: 100%;
        margin-top: 1rem;
        background: #faf5ff;
        padding: 1rem;
        border-radius: 0.75rem;
    }

    .reply-form textarea {
        width: 100%;
        padding: 0.75rem;
        font-size: clamp(0.75rem, 2vw, 0.875rem);
        border-radius: 0.5rem;
        border: 1px solid #e9d8fd;
        resize: vertical;
        margin-bottom: 0.75rem;
        background: #fff;
        transition: border-color 0.3s ease;
    }

    .reply-form textarea:focus {
        outline: none;
        border-color: #6b48ff;
        box-shadow: 0 0 0.3125rem rgba(107, 72, 255, 0.3);
    }

    .reply-form input[type="file"] {
        width: 100%;
        margin-bottom: 0.75rem;
        font-size: clamp(0.75rem, 2vw, 0.875rem);
    }

    .reply-form button {
        width: 100%;
        background: #6b48ff;
        color: #fff;
        border: none;
        padding: 0.625rem 1.25rem;
        border-radius: 0.5rem;
        font-size: clamp(0.75rem, 2vw, 0.875rem);
        font-weight: 500;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
    }

    .reply-form button:hover {
        background: #5533d6;
        transform: translateY(-0.125rem);
    }

    /* Reply styling (no flexbox) */
    .reply {
        width: 100%;
        margin-top: 1rem;
        margin-left: clamp(1rem, 5vw, 2.5rem);
        padding: 1rem;
        background: #faf5ff;
        border-radius: 0.75rem;
        box-shadow: 0 0.125rem 0.625rem rgba(0, 0, 0, 0.05);
        border-left: 0.1875rem solid #6b48ff;
    }

    .reply .username {
        font-weight: 600;
        color: #2d3748;
        font-size: clamp(0.75rem, 2vw, 0.875rem);
    }

    .reply .timestamp {
        font-size: clamp(0.625rem, 1.5vw, 0.85rem);
        color: #718096;
        margin-top: 0.25rem;
    }

    .reply .content {
        margin-top: 0.625rem;
        font-size: clamp(0.75rem, 2vw, 0.875rem);
        color: #4a5568;
    }

    .reply img {
        width: 100%;
        max-width: 100%;
        height: auto;
        margin-top: 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid #e9d8fd;
    }

    /* Media Queries for responsiveness */
    @media (max-width: 1024px) {
        .container {
            width: 95%;
            padding: 1.25rem;
        }

        .post, .comment, .reply {
            padding: 1rem;
        }

        .reply {
            margin-left: clamp(0.75rem, 4vw, 2rem);
        }
    }

    @media (max-width: 768px) {
        .container {
            width: 98%;
            margin: 1rem auto;
            padding: 1rem;
        }

        .post h2 {
            font-size: clamp(1.125rem, 3.5vw, 1.375rem);
        }

        .comment-form, .reply-form {
            padding: 0.875rem;
        }

        .comment-form button, .reply-form button {
            padding: 0.625rem 1rem;
        }

        .reply {
            margin-left: clamp(0.5rem, 3vw, 1.25rem);
        }

        .post-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 480px) {
        .container {
            width: 100%;
            margin: 0.5rem auto;
            padding: 0.75rem;
        }

        h1 {
            font-size: clamp(1.25rem, 4vw, 1.75rem);
        }

        .post, .comment, .reply {
            padding: 0.75rem;
        }

        .comment-form textarea, .reply-form textarea {
            padding: 0.625rem;
        }

        .comment-form button, .reply-form button {
            padding: 0.5rem 0.75rem;
        }

        .reply {
            margin-left: 0.5rem;
        }
    }
    </style>
</head>
<body>

<div class="container">
    <h1>Comments on Post</h1>

    <!-- Displaying the post -->
    <div class="post">
        <div class="post-header">
            <h2><?php echo htmlspecialchars($post['username']); ?> - <?php echo htmlspecialchars($post['club']); ?></h2>
            <span class="timestamp"><?php echo $post['post_timestamp']; ?></span>
        </div>
        <p class="content"><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>
        <?php if ($post['post_image']): ?>
            <img src="<?php echo htmlspecialchars($post['post_image']); ?>" alt="Post Image">
        <?php endif; ?>
    </div>

    <!-- Comment Form -->
    <form id="comment-form" enctype="multipart/form-data" class="comment-form">
        <textarea name="comment_content" placeholder="Write your comment..." required></textarea>
        <input type="file" name="comment_image" accept="image/*">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
        <button type="submit">Post Comment</button>
    </form>

    <!-- Displaying Comments -->
    <div class="comments">
        <?php
        function renderComments($comments, $post_id) {
            foreach ($comments as $comment) {
                echo '<div class="comment" data-comment-id="' . $comment['comment_id'] . '">';
                echo '<h3 class="username">' . htmlspecialchars($comment['username']) . '</h3>';
                echo '<span class="timestamp">' . $comment['comment_timestamp'] . '</span>';
                echo '<p class="content">' . nl2br(htmlspecialchars($comment['comment_content'])) . '</p>';
                if ($comment['comment_image']) {
                    echo '<img src="' . htmlspecialchars($comment['comment_image']) . '" alt="Comment Image">';
                }
                echo '<form class="reply-form" enctype="multipart/form-data">';
                echo '<textarea name="reply_content" placeholder="Write your reply..." required></textarea>';
                echo '<input type="file" name="reply_image" accept="image/*">';
                echo '<input type="hidden" name="parent_id" value="' . $comment['comment_id'] . '">';
                echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
                echo '<button type="submit">Reply</button>';
                echo '</form>';

                if (!empty($comment['replies'])) {
                    echo '<div class="replies">';
                    renderComments($comment['replies'], $post_id);
                    echo '</div>';
                }
                echo '</div>';
            }
        }
        renderComments($comments, $post_id);
        ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle comment form submission
    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                var res = JSON.parse(response);
                if (res.success) {
                    var commentHtml = `
                        <div class="comment" data-comment-id="${res.data.comment_id}">
                            <h3 class="username">${res.data.username}</h3>
                            <span class="timestamp">${res.data.timestamp}</span>
                            <p class="content">${res.data.content.replace(/\n/g, '<br>')}</p>
                            ${res.data.image_path ? `<img src="${res.data.image_path}" alt="Comment Image">` : ''}
                            <form class="reply-form" enctype="multipart/form-data">
                                <textarea name="reply_content" placeholder="Write your reply..." required></textarea>
                                <input type="file" name="reply_image" accept="image/*">
                                <input type="hidden" name="parent_id" value="${res.data.comment_id}">
                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                <button type="submit">Reply</button>
                            </form>
                        </div>
                    `;
                    $('.comments').prepend(commentHtml);
                    $('#comment-form')[0].reset();
                } else {
                    alert(res.message || 'Error posting comment.');
                }
            },
            error: function() {
                alert('An error occurred while posting the comment.');
            }
        });
    });

    // Handle reply form submission (delegate event for dynamically added forms)
    $(document).on('submit', '.reply-form', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $parent = $(this).closest('.comment, .reply');
        var parentId = $parent.data('comment-id');

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                var res = JSON.parse(response);
                if (res.success) {
                    var replyHtml = `
                        <div class="reply" data-comment-id="${res.data.comment_id}">
                            <h4 class="username">${res.data.username}</h4>
                            <span class="timestamp">${res.data.timestamp}</span>
                            <p class="content">${res.data.content.replace(/\n/g, '<br>')}</p>
                            ${res.data.image_path ? `<img src="${res.data.image_path}" alt="Reply Image">` : ''}
                            <form class="reply-form" enctype="multipart/form-data">
                                <textarea name="reply_content" placeholder="Write your reply..." required></textarea>
                                <input type="file" name="reply_image" accept="image/*">
                                <input type="hidden" name="parent_id" value="${res.data.comment_id}">
                                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                <button type="submit">Reply</button>
                            </form>
                        </div>
                    `;
                    if ($parent.hasClass('comment')) {
                        var $replies = $parent.find('.replies');
                        if ($replies.length === 0) {
                            $parent.append('<div class="replies"></div>');
                            $replies = $parent.find('.replies');
                        }
                        $replies.append(replyHtml);
                    } else {
                        $parent.after(replyHtml);
                    }
                    $parent.find('.reply-form')[0].reset();
                } else {
                    alert(res.message || 'Error posting reply.');
                }
            },
            error: function() {
                alert('An error occurred while posting the reply.');
            }
        });
    });
});
</script>

</body>
</html>