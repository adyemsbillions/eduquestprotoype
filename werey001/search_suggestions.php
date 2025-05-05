<?php
include('db_connection.php');
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