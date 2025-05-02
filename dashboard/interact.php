<?php
// interact.php
session_start();
header('Content-Type: application/json');

include('db_connection.php');

$response = ['success' => false];
$user_id = $_SESSION['user_id'] ?? 1;

$action = $_POST['action'] ?? '';
$reel_id = $_POST['reel_id'] ?? 0;

if ($reel_id) {
    switch ($action) {
        case 'like':
            // Check if user already liked
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM reel_likes WHERE reel_id = ? AND user_id = ?");
            $check_stmt->bind_param("ii", $reel_id, $user_id);
            $check_stmt->execute();
            $already_liked = $check_stmt->get_result()->fetch_row()[0] > 0;
            $check_stmt->close();

            if (!$already_liked) {
                $stmt = $conn->prepare("INSERT INTO reel_likes (reel_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $reel_id, $user_id);
                if ($stmt->execute()) {
                    $update_stmt = $conn->prepare("UPDATE reels SET likes = likes + 1 WHERE id = ?");
                    $update_stmt->bind_param("i", $reel_id);
                    $response['success'] = $update_stmt->execute();
                    $update_stmt->close();
                }
                $stmt->close();
            }
            break;

        case 'share':
            $stmt = $conn->prepare("UPDATE reels SET shares = shares + 1 WHERE id = ?");
            $stmt->bind_param("i", $reel_id);
            $response['success'] = $stmt->execute();
            $stmt->close();
            break;

        case 'view':
            $stmt = $conn->prepare("UPDATE reels SET views = views + 1 WHERE id = ?");
            $stmt->bind_param("i", $reel_id);
            $response['success'] = $stmt->execute();
            $stmt->close();
            break;

        case 'comment':
            $comment = $_POST['comment'] ?? '';
            if ($comment) {
                $stmt = $conn->prepare("INSERT INTO rcomments (reel_id, user_id, comment_text) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $reel_id, $user_id, $comment);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $stmt2 = $conn->prepare("UPDATE reels SET comments = comments + 1 WHERE id = ?");
                    $stmt2->bind_param("i", $reel_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
                $stmt->close();
            }
            break;
    }
}

echo json_encode($response);
$conn->close();
?>