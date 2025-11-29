<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle content creation/upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_content'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    $image_data = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Read the image file content into a variable
        $image_data = file_get_contents($_FILES['image']['tmp_name']);
    }

    // Insert into the 'image_data' column instead of 'image_path'
    $stmt = $pdo->prepare("INSERT INTO content (title, description, image_data) VALUES (?, ?, ?)");
    $stmt->bindParam(1, $title);
    $stmt->bindParam(2, $description);
    $stmt->bindParam(3, $image_data, PDO::PARAM_LOB); // Use PARAM_LOB for binary data
    $stmt->execute();
    
    header("Location: manage_content.php?message=" . urlencode("Content added successfully."));
    exit;
}

// Handle content deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_content'])) {
    $content_id = $_POST['content_id'];
    
    // No need to delete a file from the server
    $stmt = $pdo->prepare("DELETE FROM content WHERE id = ?");
    $stmt->execute([$content_id]);
    
    header("Location: manage_content.php?message=" . urlencode("Content deleted successfully."));
    exit;
}

// Fetch all content for display
$stmt = $pdo->query("SELECT * FROM content ORDER BY created_at DESC");
$contents = $stmt->fetchAll();

include 'includes/header.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Content</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/styles.css">
    <style>
        body { background-color: #f7f3e8; font-family: 'Poppins', sans-serif; color: #333; }
        .main-card { background-color: #d8eafc; border-radius: 1rem; border: none; padding: 1.5rem; }
        .card-title-header { font-family: 'Playfair Display', serif; font-weight: 700; }
        .rounded-pill { padding: 0.5rem 1.2rem; }
        .content-card {
            background-color: #ffffff;
            border-radius: 1rem;
            margin-bottom: 1rem;
            padding: 1.25rem;
            border: 1px solid #c9e2f6;
            display: flex;
            flex-direction: column;
        }
        .content-card img {
            max-width: 100px;
            height: auto;
            border-radius: 8px;
            margin-right: 1rem;
        }
        @media (min-width: 768px) {
            .content-card {
                flex-direction: row;
                align-items: center;
            }
        }
        .actions-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
            width: 100%;
        }
        @media (min-width: 768px) {
            .actions-buttons {
                flex-direction: row;
                margin-top: 0;
                width: auto;
            }
        }
        .content-details {
            flex-grow: 1;
        }
    </style>
</head>
<body>
<div class="container my-5">
    <h1 class="display-5 fw-bold" style="font-family: 'Playfair Display', serif;">Manage Content</h1>
    <p class="lead text-muted">Create, edit, and delete homepage content.</p>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success mt-4" role="alert">
            <?= htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <div class="main-card shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title-header"><i class="bi bi-card-text me-2"></i>Content List</h5>
                <a href="./Admin/edit_content.php" class="btn btn-success btn-sm rounded-pill">
                    <i class="bi bi-plus-lg me-1"></i>Add New Content
                </a>
                <a href="admin_dashboard.php" class="btn btn-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
            
            <?php if (count($contents) > 0): ?>
                <?php foreach ($contents as $c): ?>
                    <div class="content-card shadow-sm">
                        <?php if ($c['image_data']): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($c['image_data']) ?>" alt="<?= htmlspecialchars($c['title']) ?>">
                        <?php endif; ?>
                        <div class="content-details me-md-auto">
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($c['title']) ?></h6>
                            <p class="text-muted small mb-0"><?= htmlspecialchars($c['description']) ?></p>
                        </div>
                        <div class="actions-buttons mt-3 mt-md-0">
                            <a href="./Admin/edit_content.php?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm rounded-pill">
                                <i class="bi bi-pencil-fill me-1"></i>Edit
                            </a>
                            <button type="button" class="btn btn-danger btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $c['id'] ?>">
                                <i class="bi bi-trash-fill me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center mt-3">No content found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #fff; border: 1px solid #d8eafc; border-radius: 1rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
            <div class="modal-header" style="border-bottom: 1px solid #e9ecef;">
                <h5 class="modal-title" id="deleteModalLabel" style="color: #4a5568; font-family: 'Playfair Display', serif;">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color: #6c757d;">
                Are you sure you want to delete this content? This action cannot be undone.
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e9ecef;">
                <button type="button" class="btn btn-sm rounded-pill" data-bs-dismiss="modal" style="background-color: #f1f5f9; color: #4a5568; border: none; font-weight: 500;">
                    Cancel
                </button>
                <form id="deleteForm" action="manage_content.php" method="POST" class="d-inline">
                    <input type="hidden" name="content_id" id="contentIdToDelete">
                    <button type="submit" name="delete_content" class="btn btn-sm rounded-pill" style="background-color: #e53e3e; color: #fff; border: none; font-weight: 500;">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript to pass the content ID to the modal's hidden input field
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var contentId = button.getAttribute('data-id'); // Extract info from data-id attribute
        var modalInput = deleteModal.querySelector('#contentIdToDelete');
        modalInput.value = contentId;
    });
</script>
</body>
</html>
<?php include 'includes/footer.php'; ?>