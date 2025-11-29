<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function require_role($role) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
        header('Location: login.php');
        exit;
    }
}

function require_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function is_admin() { return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'; }
function is_staff() { return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'staff'; }
function is_customer() { return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer'; }

function refresh_user() {
    global $pdo;
    if (isset($_SESSION['user'])) {
        $stmt = $pdo->prepare('SELECT id,name,email,role,status,created_at FROM users WHERE id=?');
        $stmt->execute([$_SESSION['user']['id']]);
        $u = $stmt->fetch();
        if ($u) $_SESSION['user'] = $u;
    }
}
?>