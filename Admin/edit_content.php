<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$content = null;
$page_title = 'Add New Content';
$created_by = $_SESSION['user']['id'];

if (isset($_GET['id'])) {
    $page_title = 'Edit Content';
    // Fetch content including the image_data column
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $content = $stmt->fetch();
    if (!$content) {
        header("Location: manage_content.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $image_data = null;

    // Check if a new image was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_data = file_get_contents($_FILES['image']['tmp_name']);
    } elseif ($content) {
        // Keep the existing image data if no new image was uploaded during an edit
        $image_data = $content['image_data'];
    }

    if ($content) { // Update existing content
        // Update the content, including the image_data
        $stmt = $pdo->prepare("UPDATE content SET title = ?, description = ?, image_data = ? WHERE id = ?");
        $stmt->bindParam(1, $title);
        $stmt->bindParam(2, $description);
        $stmt->bindParam(3, $image_data, PDO::PARAM_LOB);
        $stmt->bindParam(4, $content['id']);
        $stmt->execute();

    } else { // Insert new content
        // Insert new content with the binary image data
        $stmt = $pdo->prepare("INSERT INTO content (title, description, image_data, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bindParam(1, $title);
        $stmt->bindParam(2, $description);
        $stmt->bindParam(3, $image_data, PDO::PARAM_LOB);
        $stmt->bindParam(4, $created_by);
        $stmt->execute();
    }

    header("Location: ../manage_content.php");
    exit;
}

?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/styles.css">
<div class="container mt-4">
    <h2><?= $page_title ?></h2>
    <form action="edit_content.php<?= $content ? '?id=' . $content['id'] : '' ?>" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="<?= $content ? htmlspecialchars($content['title']) : '' ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="5" required><?= $content ? htmlspecialchars($content['description']) : '' ?></textarea>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Image</label>
            <input type="file" class="form-control" id="image" name="image">
            <?php if ($content && $content['image_data']): ?>
                <div class="mt-2">
                    <p>Current Image:</p>
                    <img src="data:image/jpeg;base64,<?= base64_encode($content['image_data']) ?>" alt="Current image" style="width: 150px; height: auto;">
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success"><?= $content ? 'Update Content' : 'Add Content' ?></button>
        <a href="../manage_content.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>