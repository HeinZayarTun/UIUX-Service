<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include './config/db.php';


// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch pending services
$sql = "SELECT s.id, s.title, s.description, u.name AS staff_name FROM services s JOIN users u ON s.uploaded_by_staff_id = u.id WHERE s.status = 'pending'";
$stmt = $pdo->query($sql);
$pending_services = $stmt->fetchAll();

// Fetch all users and staff for management
$users_sql = "SELECT * FROM users WHERE role = 'user'";
$staff_sql = "SELECT * FROM users WHERE role IN ('staff', 'admin')";
$users = $pdo->query($users_sql)->fetchAll();
$staff_members = $pdo->query($staff_sql)->fetchAll();

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - UIUX Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <style>
        body { background-color: #f7f3e8; font-family: 'Poppins', sans-serif; color: #333; }
        .card { background-color: #d8eafc; border-radius: 1rem; border: none; padding: 1.5rem; }
        .card-title { font-family: 'Playfair Display', serif; font-weight: 700; }
        .btn-success:hover { color: var(--bg-light); } 

    </style>
</head>
<body>
<?php include './includes/header.php'; ?>

<div class="container my-5">
    <h1 class="display-5 fw-bold" style="font-family: 'Playfair Display', serif;">Admin Dashboard</h1>
    <p class="lead text-muted">Welcome, <?= htmlspecialchars($_SESSION['user']['name']); ?></p>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row mt-4 g-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
    <h5 class="card-title"><i class="bi bi-file-earmark-check me-2"></i>Services Awaiting Approval</h5>
    <ul class="list-group list-group-flush mt-3">
        <?php if (count($pending_services) > 0): ?>
            <?php foreach ($pending_services as $service): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                    
                    <div class="py-2">
                        <strong class="text-break"><?= htmlspecialchars($service['title']); ?></strong>
                        <div class="text-muted small">Submitted by <?= htmlspecialchars($service['staff_name']); ?></div>
                    </div>
                    
                    <div class="d-flex flex-column flex-md-row gap-2 py-2"> 
                        <a href="./Admin/process_approval.php?id=<?= $service['id'] ?>&action=approve" class="btn btn-success btn-sm rounded-pill">
                            <i class="bi bi-check-lg"></i> Approve
                        </a>
                        <a href="./Admin/process_approval.php?id=<?= $service['id'] ?>&action=reject" class="btn btn-danger btn-sm rounded-pill">
                            <i class="bi bi-x-lg"></i> Reject
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li class="list-group-item text-muted">No services are currently awaiting approval.</li>
        <?php endif; ?>
    </ul>
</div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <h5 class="card-title"><i class="bi bi-person-circle me-2"></i>User Management</h5>
                <p class="mt-3">Manage and ban users.</p>
                <a href="./manage_user.php" class="btn btn-success  btn-sm mt-3">
                    <i class="bi bi-person-x-fill me-1"></i>Manage Users
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <h5 class="card-title"><i class="bi bi-people-fill me-2"></i>Staff Management</h5>
                <p class="mt-3">Manage and assign roles to staff members.</p>
                <a href="./manage_staff.php" class="btn btn-success  btn-sm mt-3">
                    <i class="bi bi-person-gear me-1"></i>Manage Staff
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm chat-card">
                <h5 class="card-title"><i class="bi bi-chat-dots-fill me-2"></i>Chat</h5>
                <p class="mt-3">View messages from users and staff.</p>
                <a href="./message.php" class="btn btn-success  btn-sm mt-3">
                    <i class="bi bi-chat-text-fill me-1"></i>Go to Chat
                </a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <h5 class="card-title"><i class="bi bi-file-earmark-text-fill me-2"></i>Manage Content</h5>
                <p class="mt-3">Manage and upload for home page.</p>
                <a href="./manage_content.php" class="btn btn-success btn-sm mt-3">
                    <i class="bi bi-plus-lg me-1"></i>Post
                </a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <h5 class="card-title"><i class="bi bi-file-earmark-text-fill me-2"></i>Manage Content</h5>
                <p class="mt-3">Manage User Project</p>
                <a href="./manage_requests.php" class="btn btn-success btn-sm mt-3">
                    <i class="bi bi-plus-lg me-1"></i>Manage Project
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>