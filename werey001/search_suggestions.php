<?php
$conn = new mysqli('localhost', 'unimaid9_unimaidresources', '#adyems123AD', 'unimaid9_unimaidresources');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['search_suggest'])) {
    $suggest = $_GET['search_suggest'];
    $stmt = $conn->prepare("SELECT username FROM users WHERE username LIKE ? LIMIT 5");
    $searchParam = "%$suggest%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['username'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($suggestions);
    
    $stmt->close();
    $conn->close();
    exit();
}
?>