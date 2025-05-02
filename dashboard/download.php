<?php
// This is the download.php script that will handle file downloads

// Ensure the file parameter is set and valid
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filePath = urldecode($_GET['file']); // Decode the file path

    // Ensure the file exists
    if (file_exists($filePath)) {
        // Set the headers to force the download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, no-store, must-revalidate'); // Prevent caching
        header('Pragma: no-cache');
        header('Expires: 0');

        // Clear the output buffer to avoid any previous content
        ob_clean();
        flush();

        // Read the file and send it to the browser
        readfile($filePath);
        exit;
    } else {
        // If the file does not exist, show an error message
        die("Error: The file does not exist.");
    }
} else {
    // If no file is provided, show an error message
    die("Error: No file specified.");
}
?>
