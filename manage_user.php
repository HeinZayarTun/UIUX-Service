<?php
require 'config/db.php';
require 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = '';

// Handle Ban/Unban action
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    if ($_POST['action'] === 'ban') {
        $stmt = $pdo->prepare("UPDATE users SET status='inactive' WHERE id=?");
        $stmt->execute([$userId]);
        $msg = 'User has been banned successfully.';
    } elseif ($_POST['action'] === 'unban') {
        $stmt = $pdo->prepare("UPDATE users SET status='active' WHERE id=?");
        $stmt->execute([$userId]);
        $msg = 'User has been unbanned successfully.';
    }
}

// Fetch only "user" role
$stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE role = 'customer' ORDER BY id DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/styles.css">
    <style>
        body { background-color: #f7f3e8; font-family: 'Poppins', sans-serif; color: #333; }
        .card { background-color: #d8eafc; border-radius: 1rem; border: none; padding: 1.5rem; }
        .card-title { font-family: 'Playfair Display', serif; font-weight: 700; }
        .btn-custom-orange { background-color: #ff9933; border-color: #ff9933; color: #fff; border-radius: 2rem; padding: 0.5rem 1.5rem; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <h1 class="display-5 fw-bold" style="font-family: 'Playfair Display', serif;">Manage Users</h1>
    <p class="lead text-muted">Manage all registered customer accounts on the platform.</p>

    <?php if($msg): ?>
        <div class="alert alert-success mt-4" role="alert">
            <?= $msg; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title"><i class="bi bi-person-lines-fill me-2"></i>Customer List</h5>
                <a href="admin_dashboard.php" class="btn btn-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Banned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <button type="submit" name="action" value="ban" class="btn btn-warning btn-sm rounded-pill">
                                                    <i class="bi bi-person-x-fill me-1"></i>Ban
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="action" value="unban" class="btn btn-success btn-sm rounded-pill">
                                                    <i class="bi bi-person-check-fill me-1"></i>Unban
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'includes/footer.php'; ?>