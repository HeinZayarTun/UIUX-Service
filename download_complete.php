<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['request_id'])) {
    http_response_code(400);
    exit('Missing request ID.');
}

$request_id = (int)$_GET['request_id'];

try {
    // Select the necessary BLOB columns, user IDs, and status
    $stmt = $pdo->prepare("
        SELECT completed_file_name, completed_file_type, completed_file_data, user_id, staff_id, status
        FROM project_requests WHERE id = ?
    ");
    $stmt->execute([$request_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if file exists, is completed, and has data
    if (!$file || $file['status'] !== 'completed' || empty($file['completed_file_data'])) {
        http_response_code(404);
        exit('File not found or not completed.');
    }

    $user = $_SESSION['user'] ?? ['role' => 'guest', 'id' => 0];

    // Access control: Admin, assigned Staff, or original Customer
    $allowed = (
        $user['role'] === 'admin' ||
        ($user['role'] === 'staff' && $user['id'] == $file['staff_id']) ||
        ($user['role'] === 'customer' && $user['id'] == $file['user_id'])
    );

    if (!$allowed) {
        http_response_code(403);
        exit('You are not authorized to download this file.');
    }

    // Set headers for file download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $file['completed_file_type']);
    header('Content-Disposition: attachment; filename="' . $file['completed_file_name'] . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . strlen($file['completed_file_data']));

    // Output the file data
    echo $file['completed_file_data'];
    exit;

} catch (PDOException $e) {
    error_log('Download error: ' . $e->getMessage());
    http_response_code(500);
    exit('Server error.');
}
?>