<?php
require 'config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$stmt = $pdo->query("SELECT * FROM content ORDER BY created_at DESC");
$contents = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h2>Our Services & Content</h2>
    <div class="row">
        <?php foreach ($contents as $c): ?>
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5><?= htmlspecialchars($c['title']) ?></h5>
                    <p><?= htmlspecialchars($c['description']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
