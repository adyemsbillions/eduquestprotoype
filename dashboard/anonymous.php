<?php
// Assuming you've included your database connection file
require_once 'db_connection.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');

    if (isset($_POST['content'])) {
        // Handle new post submission
        $content = $_POST['content'];
        $imagePath = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = $_FILES['image'];
            $imageExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $imageName = uniqid() . '.' . $imageExtension;
            $uploadDir = 'uploads/';
            $uploadPath = $uploadDir . $imageName;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
                $imagePath = $uploadPath;
            } else {
                echo json_encode(['success' => false, 'error' => 'Error uploading image']);
                exit;
            }
        }

        $sql = "INSERT INTO anonymous_posts (content, image_path) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $content, $imagePath);

        if ($stmt->execute()) {
            $postId = $stmt->insert_id;
            echo json_encode([
                'success' => true,
                'post' => [
                    'id' => $postId,
                    'content' => htmlspecialchars($content),
                    'image_path' => $imagePath,
                    'created_at' => date('Y-m-d H:i:s'),
                    'comment_count' => 0
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error submitting post']);
        }
        exit;
    } elseif (isset($_POST['comment']) && isset($_POST['post_id'])) {
        // Handle comment submission
        $comment = $_POST['comment'];
        $postId = $_POST['post_id'];

        $sql = "INSERT INTO post_comments (post_id, comment) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $postId, $comment);

        if ($stmt->execute()) {
            $commentId = $stmt->insert_id;
            echo json_encode([
                'success' => true,
                'comment' => [
                    'id' => $commentId,
                    'post_id' => $postId,
                    'comment' => htmlspecialchars($comment),
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error submitting comment']);
        }
        exit;
    }
}

// Fetch all posts with comment counts
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM post_comments c WHERE c.post_id = p.id) AS comment_count 
        FROM anonymous_posts p 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anonymous Post</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fc;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            resize: none;
            margin-bottom: 20px;
            font-size: 16px;
            box-sizing: border-box;
        }

        textarea[name="content"] {
            height: 150px;
        }

        textarea[name="comment"] {
            height: 80px;
        }

        input[type="file"] {
            margin-bottom: 20px;
        }

        button {
            padding: 10px 20px;
            border: none;
            background-color: purple;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background-color: #4B0082;
        }

        .post {
            background-color: #fff;
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .post img {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
        }

        .anonymous-label {
            font-style: italic;
            color: #999;
            font-size: 14px;
            margin-top: 10px;
        }

        .alert {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }

        .comment-section {
            margin-top: 15px;
        }

        .comment-form {
            display: none;
            margin-top: 10px;
        }

        .comment {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .comment.hidden {
            display: none;
        }

        .show-more, .show-less {
            color: purple;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }

        .show-more.hidden, .show-less.hidden {
            display: none;
        }

        .show-more:hover, .show-less:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Post Anonymously</h2>
    <center><h6><i>End-End Encrypted</i></h6></center>

    <!-- Anonymous Post Form -->
    <form id="postForm" method="POST" enctype="multipart/form-data">
        <textarea name="content" placeholder="Write your anonymous post here..." required></textarea>
        <input type="file" name="image" accept="image/*">
        <button type="submit">Submit Post</button>
    </form>

    <div class="alert" id="successAlert">Post submitted successfully!</div>

    <!-- Display all posts -->
    <div id="postsContainer">
    <?php
    if ($result->num_rows > 0) {
        while ($post = $result->fetch_assoc()) {
            echo '<div class="post" data-post-id="' . $post['id'] . '">';
            echo '<p>' . htmlspecialchars($post['content']) . '</p>';
            if ($post['image_path']) {
                echo '<img src="' . htmlspecialchars($post['image_path']) . '" alt="Post Image">';
            }
            echo '<p class="anonymous-label">Unimaid Resources Anonymous • Comments: <span class="comment-count">' . $post['comment_count'] . '</span></p>';

            // Comment form
            echo '<div class="comment-form">';
            echo '<form class="commentForm" method="POST">';
            echo '<input type="hidden" name="post_id" value="' . $post['id'] . '">';
            echo '<textarea name="comment" placeholder="Add a comment..." required></textarea>';
            echo '<button type="submit">Post Comment</button>';
            echo '</form>';
            echo '</div>';

            // Comments section
            echo '<div class="comment-section">';
            $commentSql = "SELECT * FROM post_comments WHERE post_id = ? ORDER BY created_at DESC";
            $stmt = $conn->prepare($commentSql);
            $stmt->bind_param("i", $post['id']);
            $stmt->execute();
            $comments = $stmt->get_result();
            $commentCount = 0;

            while ($comment = $comments->fetch_assoc()) {
                $commentCount++;
                $hiddenClass = ($commentCount > 3) ? 'hidden' : '';
                echo '<div class="comment ' . $hiddenClass . '">';
                echo '<p>' . htmlspecialchars($comment['comment']) . '</p>';
                echo '<p class="anonymous-label">' . $comment['created_at'] . '</p>';
                echo '</div>';
            }

            echo '<span class="show-more' . ($commentCount > 3 ? '' : ' hidden') . '">Show More (' . ($commentCount - 3) . ' more)</span>';
            echo '<span class="show-less hidden">Show Less</span>';
            echo '</div>';

            echo '</div>';
        }
    } else {
        echo '<p>No posts available.</p>';
    }
    ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Toggle comment form visibility on post click
        $('.post').on('click', function(e) {
            if (!$(e.target).closest('.comment-form, .comment-section, .show-more, .show-less').length) {
                $(this).find('.comment-form').slideToggle();
            }
        });

        // Handle post submission via AJAX
        $('#postForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var postHtml = `
                            <div class="post" data-post-id="${response.post.id}">
                                <p>${response.post.content}</p>
                                ${response.post.image_path ? `<img src="${response.post.image_path}" alt="Post Image">` : ''}
                                <p class="anonymous-label">Unimaid Resources Anonymous • Comments: <span class="comment-count">0</span></p>
                                <div class="comment-form" style="display: none;">
                                    <form class="commentForm" method="POST">
                                        <input type="hidden" name="post_id" value="${response.post.id}">
                                        <textarea name="comment" placeholder="Add a comment..." required></textarea>
                                        <button type="submit">Post Comment</button>
                                    </form>
                                </div>
                                <div class="comment-section">
                                    <span class="show-more hidden">Show More</span>
                                    <span class="show-less hidden">Show Less</span>
                                </div>
                            </div>`;
                        $('#postsContainer').prepend(postHtml);
                        $('#postForm')[0].reset();
                        $('#successAlert').fadeIn().delay(3000).fadeOut();
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Failed to submit post: ' + error);
                }
            });
        });

        // Handle comment submission via AJAX
        $(document).on('submit', '.commentForm', function(e) {
            e.preventDefault();
            var $form = $(this);
            var formData = new FormData(this);

            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var $post = $form.closest('.post');
                        var $commentSection = $post.find('.comment-section');
                        var commentHtml = `
                            <div class="comment">
                                <p>${response.comment.comment}</p>
                                <p class="anonymous-label">${response.comment.created_at}</p>
                            </div>`;
                        $commentSection.prepend(commentHtml);
                        var commentCount = $commentSection.find('.comment').length;
                        $post.find('.comment-count').text(commentCount);
                        $form[0].reset();
                        $form.slideUp();

                        // Update visibility
                        updateCommentVisibility($commentSection, commentCount);
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Failed to submit comment: ' + error);
                }
            });
        });

        // Function to update comment visibility
        function updateCommentVisibility($commentSection, commentCount) {
            var $comments = $commentSection.find('.comment');
            var $showMore = $commentSection.find('.show-more');
            var $showLess = $commentSection.find('.show-less');

            if (commentCount > 3) {
                $comments.slice(3).addClass('hidden').hide();
                $showMore.removeClass('hidden').text(`Show More (${commentCount - 3} more)`);
                $showLess.addClass('hidden').hide();
            } else {
                $showMore.addClass('hidden').hide();
                $showLess.addClass('hidden').hide();
            }
        }

        // Initialize comment visibility on page load
        $('.comment-section').each(function() {
            var $commentSection = $(this);
            var commentCount = $commentSection.find('.comment').length;
            updateCommentVisibility($commentSection, commentCount);
        });

        // Handle "Show More" click
        $(document).on('click', '.show-more', function() {
            var $commentSection = $(this).closest('.comment-section');
            $commentSection.find('.comment.hidden').slideDown(function() {
                $(this).removeClass('hidden');
            });
            $(this).addClass('hidden').hide();
            $commentSection.find('.show-less').removeClass('hidden').show();
        });

        // Handle "Show Less" click
        $(document).on('click', '.show-less', function() {
            var $commentSection = $(this).closest('.comment-section');
            $commentSection.find('.comment').slice(3).slideUp(function() {
                $(this).addClass('hidden');
            });
            $(this).addClass('hidden').hide();
            $commentSection.find('.show-more').removeClass('hidden').show();
        });
    });
</script>

</body>
</html>

<?php
$conn->close();
?>