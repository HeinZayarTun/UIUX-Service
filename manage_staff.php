<?php
require_once __DIR__ . '/includes/auth.php';
require_role('admin');
include __DIR__ . '/includes/header.php';

// create staff
$msg = '';
if (isset($_POST['create_staff'])){
    $name = trim($_POST['name']); $email = trim($_POST['email']); $password = $_POST['password'];
    if ($name && $email && $password){
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            // Note: Users default to 'active' status upon creation.
            $pdo->prepare('INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,?,?)')->execute([$name,$email,$hash,'staff','active']);
            $msg = 'Staff created.';
        } catch (Exception $e){ $msg = 'Error or email exists.'; }
    }
}

// ban user - FIXED to prevent banning users with the 'admin' role
if (isset($_POST['ban_user']) && isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    
    // Add 'AND role != "admin"' to the WHERE clause to prevent banning other admins
    $stmt = $pdo->prepare('UPDATE users SET status = "banned" WHERE id = ? AND role != "admin"');
    $stmt->execute([$userId]);
    
    // Check if a row was actually updated to give a precise message
    if ($stmt->rowCount() > 0) {
        $msg = 'User account banned.';
    } else {
        $msg = 'Failed to ban user. Cannot ban an Admin or the user is already banned.';
    }
}

// unban user
if (isset($_POST['unban_user']) && isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    $stmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = ?');
    $stmt->execute([$userId]);
    $msg = 'User account unbanned.';
}

// promote staff to admin - FIXED to check for 'active' status
if (isset($_POST['promote_admin']) && isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    // The query ensures the user is a 'staff' AND their 'status' is 'active' before promoting.
    $stmt = $pdo->prepare('UPDATE users SET role = "admin" WHERE id = ? AND role = "staff" AND status = "active"');
    $stmt->execute([$userId]);
    
    // Check if any rows were affected to give a more accurate message
    if ($stmt->rowCount() > 0) {
        $msg = 'Staff promoted to Admin.';
    } else {
        // This covers cases where the user is already admin OR is banned.
        $msg = 'Promotion failed. User may be banned or already an Admin.';
    }
}

// Get only staff and admins
// Make sure to select the 'status' column for the ban/unban logic to work correctly
$users = $pdo->query("SELECT id, name, email, role, status FROM users WHERE role IN ('admin', 'staff') ORDER BY role, name")->fetchAll();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Staff & Admin - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/styles.css">
    <style>
        body { background-color: #f7f3e8; font-family: 'Poppins', sans-serif; color: #333; }
        .card { background-color: #d8eafc; border-radius: 1rem; border: none; padding: 1.5rem; }
        .card-title { font-family: 'Playfair Display', serif; font-weight: 700; }
    </style>
</head>
<body>

<div class="container my-5">
    <h1 class="display-5 fw-bold" style="font-family: 'Playfair Display', serif;">Manage Staff & Admin</h1>
    <p class="lead text-muted">Manage all registered accounts and roles on the platform.</p>

    <?php if($msg): ?>
        <div class="alert alert-info mt-4" role="alert">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mt-4 mb-4">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-person-plus-fill me-2"></i>Create New Staff Account</h5>
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <input name="name" class="form-control rounded-pill" placeholder="Name" required>
                </div>
                <div class="col-md-4">
                    <input name="email" type="email" class="form-control rounded-pill" placeholder="Email" required>
                </div>
                <div class="col-md-4">
                    <input name="password" type="password" class="form-control rounded-pill" placeholder="Password" required>
                </div>
                <div class="col-12">
                    <button name="create_staff" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-person-plus me-2"></i>Create Staff
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title"><i class="bi bi-people-fill me-2"></i>Staff & Admin List</h5>
                <a href="admin_dashboard.php" class="btn btn-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
                
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-primary">Admin</span>
                                    <?php elseif ($u['role'] === 'staff'): ?>
                                        <span class="badge bg-info">Staff</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Customer</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'banned'): ?>
                                        <span class="badge bg-danger">Banned</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <?php 
                                        // Condition to disable ban/unban button for all Admins and the currently logged-in user
                                        $is_current_user = ($u['id'] === $_SESSION['user']['id']);
                                        $is_another_admin = ($u['role'] === 'admin' && !$is_current_user);
                                        $can_perform_action = !$is_current_user && ($u['role'] !== 'admin');
                                        ?>

                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="text-muted small">Admin role protected</span>
                                        <?php elseif ($u['id'] !== $_SESSION['user']['id']): ?>
                                            <?php if ($u['status'] === 'active'): ?>
                                                <button type="submit" name="ban_user" class="btn btn-warning btn-sm rounded-pill">
                                                    <i class="bi bi-person-x-fill me-1"></i>Ban
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="unban_user" class="btn btn-success btn-sm rounded-pill">
                                                    <i class="bi bi-person-check-fill me-1"></i>Unban
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($u['role'] === 'staff'): ?>
                                            <button type="submit" name="promote_admin" class="btn btn-info btn-sm rounded-pill ms-2" <?php echo ($u['status'] === 'banned') ? 'disabled' : ''; ?>>
                                                <i class="bi bi-arrow-up-circle-fill me-1"></i>Promote to Admin
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include __DIR__ . '/includes/footer.php'; ?>