<?php
require 'config/db.php';
// Fetch all content to display on the public page
$stmt = $pdo->query("SELECT * FROM content ORDER BY created_at DESC");
$contents = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <h2>Recent Services</h2>
    <div class="row">
        <?php foreach ($contents as $c): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if (isset($c['image_data'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($c['image_data']) ?>" class="card-img-top" alt="<?= htmlspecialchars($c['title']) ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($c['title']) ?></h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'addBtn.php'; ?>

<?php include 'includes/footer.php'; ?>