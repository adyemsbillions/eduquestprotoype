<?php
if (isset($_GET['url']) && isset($_GET['title'])) {
    $file_url = urldecode($_GET['url']);
    $file_name = urldecode($_GET['title']);

    // Convert Google Drive URL to direct download link
    if (strpos($file_url, 'drive.google.com') !== false) {
        $file_url = preg_replace('/.*[^-\w]([-\w]{25,}[^-\w]?.*?)(?:\/[^\/]*)?$/', 'https://drive.google.com/uc?export=download&id=$1', $file_url);
    }

    // Set headers to force download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    header('Location: ' . $file_url);
    exit;
} else {
    die("Invalid download request");
}
?>