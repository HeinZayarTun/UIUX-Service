<?php
require '../config/db.php';
require '../includes/auth.php';
session_start();

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM content WHERE id=?");
    $stmt->execute([$id]);
}
header("Location: manage_content.php");
exit;
