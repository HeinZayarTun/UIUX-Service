<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $service_id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $new_status = 'approved';
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
    } else {
        header('Location: ../admin_dashboard.php?error=Invalid action.');
        exit;
    }

    $sql = "UPDATE services SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$new_status, $service_id])) {
        if ($action === 'approve') {
            header('Location: ../admin_dashboard.php?message=Service approved successfully.');
        } elseif ($action === 'reject') {
            header('Location: ../admin_dashboard.php?message=Service rejected successfully.');
        }
    } else {
        header('Location: ../admin_dashboard.php?error=Failed to update service status.');
    }
    exit;
} else {
    header('Location: ../admin_dashboard.php?error=Missing parameters.');
    exit;
}
?>