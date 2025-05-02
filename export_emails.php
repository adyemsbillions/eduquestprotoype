<?php
require 'db_connection.php'; // Make sure this uses mysqli

try {
    $result = $conn->query("SELECT email FROM users");

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $filepath = 'C:/Users/adyem/Downloads/emails.csv';
    $file = fopen($filepath, 'w');

    while ($row = $result->fetch_assoc()) {
        fputcsv($file, [$row['email']]);
    }

    fclose($file);

    echo "✅ Emails exported successfully to: $filepath";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
