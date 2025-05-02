<?php
// Database connection
$conn = new mysqli("localhost", "unimaid9_unimaidresources", "#adyems123AD", "unimaid9_unimaidresources");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debugging session
echo "<!-- Debug: Session user_id = " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . " -->";

// Fetching posts with a scoring algorithm
$sql_posts = "
    SELECT 
        mp.post_id, 
        mp.item_name, 
        mp.description, 
        mp.price, 
        mp.images, 
        mp.views, 
        mp.user_id AS seller_id, 
        u.username,
        (
            -- Views component: 5.0 * log(views + 1) to reward engagement
            (5.0 * LN(mp.views + 1)) +
            -- Low-view boost: 10.0 if views < 10, else 0
            (CASE WHEN mp.views < 10 THEN 10.0 ELSE 0.0 END) +
            -- Age factor: 20.0 / (hours since posted + 1) to favor newer posts
            (20.0 / (TIMESTAMPDIFF(HOUR, mp.created_at, NOW()) + 1))
        ) AS score
    FROM marketplace_posts mp 
    JOIN users u ON mp.user_id = u.id 
    WHERE mp.status = 'approved' 
    ORDER BY score DESC";
$result_posts = $conn->query($sql_posts);

// Increment view count for each post
while ($post = $result_posts->fetch_assoc()) {
    $post_id = $post['post_id'];
    // Increment views every time the post is displayed
    $update_views_sql = "UPDATE marketplace_posts SET views = views + 1 WHERE post_id = ?";
    $stmt = $conn->prepare($update_views_sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();
    // Store post data for display
    $posts[] = $post;
}
// Reset result pointer to loop through posts again for display
$result_posts->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; color: #333; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); box-sizing: border-box; }
        h2 { text-align: center; margin-bottom: 20px; }
        .search-container { text-align: center; margin-bottom: 20px; box-sizing: border-box; }
        .post { display: flex; flex-direction: column; margin-bottom: 20px; padding: 20px; background-color: #fafafa; border: 1px solid #ddd; border-radius: 8px; }
        .post img { max-width: 100%; height: auto; margin: 10px 0; }
        .post h3 { margin: 0; font-size: 24px; color: #333; }
        .post p { margin: 5px 0; font-size: 16px; color: #666; }
        .post p.views { margin: 5px 0; font-size: 16px; color: #666; }
        .search-box { padding: 8px; width: 300px; font-size: 16px; border-radius: 4px; border: 1px solid #ddd; box-sizing: border-box; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
        .image-grid img { width: 100%; height: auto; border-radius: 4px; cursor: pointer; transition: transform 0.3s ease; }
        .image-grid img:hover { transform: scale(1.05); }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.8); overflow: auto; }
        .modal-content { position: relative; margin: auto; padding: 20px; width: 80%; max-width: 600px; background-color: #fff; }
        .modal img { width: 100%; height: auto; }
        .close { position: absolute; top: 10px; right: 10px; color: #aaa; font-size: 30px; font-weight: bold; cursor: pointer; }
        .close:hover, .close:focus { color: #000; text-decoration: none; cursor: pointer; }
        .popup { display: none; position: fixed; z-index: 2000; left: 50%; top: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); text-align: center; width: 300px; }
        .popup p { margin: 0 0 15px; font-size: 16px; color: #333; }
        .popup a { display: inline-block; background-color: #6a1b9a; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; margin-right: 10px; }
        .popup a:hover { background-color: #5a0d8a; }
        .popup button { background-color: #ccc; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
        .popup button:hover { background-color: #bbb; }
        @media (max-width: 768px) { 
            .image-grid { display: block; } 
            .image-grid img { display: none; max-width: 100%; height: auto; } 
            .image-grid img:first-child { display: block; } 
            .post .show-more { display: block; margin-top: 10px; text-align: center; color: #6a1b9a; cursor: pointer; } 
            .show-more.active + .image-grid img { display: block; } 
        }
        input { box-sizing: border-box; }
        .contact-seller-btn { background-color: #6a1b9a; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px; text-decoration: none; display: inline-block; }
        .contact-seller-btn:hover { background-color: #5a0d8a; }
        .posted-by { display: flex; align-items: center; }
        .back-button-container {
            position: fixed;
            bottom: 30px;
            left: 30px;
            z-index: 1000;
        }
        .back-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #6a1b9a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background: #4a148c;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <!--<div class="back-button-container">-->
    <!--    <a href="dashboard.php" class="back-button" aria-label="Back to dashboard">←</a>-->
    <!--</div>-->

    <div class="container">
        <h2>Marketplace</h2>

        <div class="search-container">
            <input type="text" id="search-box" class="search-box" placeholder="Search items..." onkeyup="filterPosts()">
        </div>

        <div id="marketplace-posts">
            <?php while ($post = $result_posts->fetch_assoc()): ?>
                <div class="post">
                    <h3><?php echo htmlspecialchars($post['item_name']); ?></h3>
                    <p><?php echo htmlspecialchars($post['description']); ?></p>
                    <p><strong>Price:</strong> ₦<?php echo number_format($post['price'], 2); ?></p>
                    <p class="views"><strong>Views:</strong> <?php echo number_format($post['views']); ?></p>
                    <div class="posted-by">
                        <p><strong>Posted by:</strong> <?php echo htmlspecialchars($post['username']); ?></p>
                        <!-- Debugging seller_id -->
                        <?php echo "<!-- Debug: Seller ID = " . $post['seller_id'] . ", User ID = " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . " -->"; ?>
                        <!-- Contact Seller button -->
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <p style="margin-left: 10px;">Please <a href="login.php">log in</a> to contact the seller.</p>
                        <?php elseif ($_SESSION['user_id'] != $post['seller_id']): ?>
                            <a href="saleschat.php?seller_id=<?php echo $post['seller_id']; ?>&post_id=<?php echo $post['post_id']; ?>" class="contact-seller-btn">Contact Seller</a>
                        <?php else: ?>
                            <p style="margin-left: 10px;">(You are the seller)</p>
                        <?php endif; ?>
                    </div>

                    <!-- Display images for the post in grid -->
                    <div class="image-grid">
                        <?php
                        $images = json_decode($post['images'], true);
                        if ($images) {
                            foreach ($images as $index => $image_path) {
                                echo '<img src="' . htmlspecialchars($image_path) . '" alt="Item Image" onclick="openModal(\'' . htmlspecialchars($image_path) . '\')" class="image-' . $index . '">';
                            }
                        }
                        ?>
                    </div>

                    <!-- Show More button to reveal additional images -->
                    <div class="show-more" onclick="toggleImages(this)">Show More</div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Modal for image -->
    <div id="modal" class="modal">
        <span class="close" onclick="closeModal()">×</span>
        <div class="modal-content">
            <img id="modal-image" src="" alt="Large image">
        </div>
    </div>

    <!-- Popup for selling prompt -->
    <div id="sellPopup" class="popup">
        <p>Do you have something to sell? Click here</p>
        <a href="salespayment.php">Click Here</a>
        <button onclick="closePopup()">Dismiss</button>
    </div>

    <script>
        function filterPosts() {
            var input, filter, posts, post, itemName, i;
            input = document.getElementById('search-box');
            filter = input.value.toLowerCase();
            posts = document.getElementById('marketplace-posts').getElementsByClassName('post');

            for (i = 0; i < posts.length; i++) {
                post = posts[i];
                itemName = post.getElementsByTagName('h3')[0];
                if (itemName.innerText.toLowerCase().indexOf(filter) > -1) {
                    post.style.display = "";
                } else {
                    post.style.display = "none";
                }
            }
        }

        function openModal(imagePath) {
            var modal = document.getElementById("modal");
            var modalImage = document.getElementById("modal-image");
            modal.style.display = "block";
            modalImage.src = imagePath;
        }

        function closeModal() {
            var modal = document.getElementById("modal");
            modal.style.display = "none";
        }

        function toggleImages(button) {
            var post = button.closest('.post');
            var images = post.querySelectorAll('.image-grid img');
            button.classList.toggle('active');
            if (button.classList.contains('active')) {
                button.innerHTML = 'Show Less';
                images.forEach(function(img) {
                    img.style.display = 'block';
                });
            } else {
                button.innerHTML = 'Show More';
                images.forEach(function(img, index) {
                    if (index !== 0) {
                        img.style.display = 'none';
                    }
                });
            }
        }

        // Popup logic
        var popup = document.getElementById("sellPopup");

        function showPopup() {
            popup.style.display = "block";
        }

        function closePopup() {
            popup.style.display = "none";
        }

        setTimeout(showPopup, 3000);
        setInterval(showPopup, 600000);

        window.onclick = function(event) {
            if (event.target == popup) {
                closePopup();
            }
        };
    </script>
</body>
</html>