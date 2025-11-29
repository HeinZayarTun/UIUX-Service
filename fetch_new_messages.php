<?php
// fetch_new_messages.php
require 'config/db.php'; 
// Ensure session is started for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Safely get user IDs from session and GET parameters
$userId = (int)($_SESSION['user']['id'] ?? 0);
$selectedUserId = (int)($_GET['chat_with'] ?? 0);
$lastMessageId = (int)($_GET['last_id'] ?? 0);

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$newMessages = [];
$incomingMessagesFromOthers = null;

// --- 1. Fetch NEW messages for the currently open chat ---
if ($selectedUserId) {
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.message, m.created_at, u.name as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ? 
          AND m.sender_id = ? 
          AND m.id > ?
        ORDER BY m.id ASC
    ");
    // We only fetch messages SENT TO the current user FROM the selected user
    $stmt->execute([$userId, $selectedUserId, $lastMessageId]);
    $newMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($lastMessageId > 0) {
    $otherStmt = $pdo->prepare("
        SELECT m.sender_id, u.name as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ? 
          AND m.sender_id != ? 
          AND m.id > ?
        ORDER BY m.id DESC
        LIMIT 1
    ");
    $otherStmt->execute([$userId, $selectedUserId, $lastMessageId]);
    $incomingMessagesFromOthers = $otherStmt->fetch(PDO::FETCH_ASSOC);
}

$data = [
    'success' => true,
    'newMessages' => $newMessages,
    'messagesFromOthers' => $incomingMessagesFromOthers,
];

echo json_encode($data);
?>