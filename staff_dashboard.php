<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is staff
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}

$staff_id = $_SESSION['user']['id'];
$message = '';
$message_type = '';

// Handle the "Mark as Completed" action with BLOB file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_completed'])) {
    $request_id = $_POST['request_id'] ?? null;
    
    // --- FILE VALIDATION AND ERROR HANDLING (Helps debug php.ini limits) ---
    if (!isset($_FILES['completed_file']) || $_FILES['completed_file']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['completed_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'Error: The file is too large. Please upload a file within the allowed size limit.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'Error: Please upload a completed project file.';
                break;
            default:
                $message = 'Error uploading file. Code: ' . $error_code;
                break;
        }
        $message_type = 'danger';
        header("Location: staff_dashboard.php?message=" . urlencode($message) . "&type=" . $message_type);
        exit;
    }

    // --- FILE PROCESSING ---
    $file_name = basename($_FILES['completed_file']['name']);
    $file_type = $_FILES['completed_file']['type'];
    $file_tmp = $_FILES['completed_file']['tmp_name'];
    
    // Read the file content for BLOB storage
    $file_data = file_get_contents($file_tmp);
    
    if ($file_data === false || empty($request_id)) {
        $message = 'Invalid request or failed to read file content.';
        $message_type = 'danger';
        header("Location: staff_dashboard.php?message=" . urlencode($message) . "&type=" . $message_type);
        exit;
    }

    // --- DATABASE INSERTION ---
    try {
        // Updated query to insert into the three BLOB columns
        $stmt = $pdo->prepare("
            UPDATE project_requests 
            SET 
                status = 'completed', 
                completion_date = NOW(), 
                completed_file_name = ?, 
                completed_file_type = ?,
                completed_file_data = ? 
            WHERE id = ? AND staff_id = ? AND status = 'approved'
        ");
        
        // Use bindParam for BLOB data (PARAM_LOB is essential)
        $stmt->bindParam(1, $file_name);
        $stmt->bindParam(2, $file_type);
        $stmt->bindParam(3, $file_data, PDO::PARAM_LOB); 
        $stmt->bindParam(4, $request_id, PDO::PARAM_INT);
        $stmt->bindParam(5, $staff_id, PDO::PARAM_INT);
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $message = 'Project successfully marked as completed and file uploaded!';
            $message_type = 'success';
        } else {
            $message = 'Project status unchanged. It may already be completed or not assigned to you.';
            $message_type = 'warning';
        }
        
    } catch (PDOException $e) {
        error_log('BLOB insert error: ' . $e->getMessage());
        $message = 'A critical database error occurred. Details logged.';
        $message_type = 'danger';
    }

    header("Location: staff_dashboard.php?message=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

// Fetch assigned projects (status: 'approved')
try {
    $stmt = $pdo->prepare("
        SELECT pr.*, u.name AS user_name
        FROM project_requests pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.staff_id = ? AND pr.status = 'approved'
        ORDER BY pr.assigned_deadline ASC
    ");
    $stmt->execute([$staff_id]);
    $assigned_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch completed projects (excluding BLOB data)
    $stmt = $pdo->prepare("
        SELECT pr.id, pr.project_idea, pr.completion_date, u.name AS user_name
        FROM project_requests pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.staff_id = ? AND pr.status = 'completed'
        ORDER BY pr.completion_date DESC
    ");
    $stmt->execute([$staff_id]);
    $completed_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $assigned_projects = [];
    $completed_projects = [];
    $error_message = 'An error occurred while fetching your projects.';
    error_log('Error fetching staff projects: ' . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Dashboard - UIUX Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="CSS/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        body { background-color: #f7f3e8; font-family: 'Poppins', sans-serif; color: #333; }
        .card { background-color: #d8eafc; border-radius: 1rem; border: none; padding: 1.5rem; }

        /* Star Rating Styling (Kept for the service submission form) */
        .rating-stars {
            display: inline-flex;
            align-items: center;
            font-size: 1.6rem;
            cursor: pointer;
            user-select: none;
        }
        .rating-stars i {
            color: #e4e5e9;
            transition: color 0.2s ease;
        }
        .rating-stars i:hover {
            transform: scale(1.2);
        }
    </style>
</head>
<body>

<?php include './includes/header.php'; ?>
<?php include 'addBtn.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="display-5 fw-bold mb-3">Staff Dashboard</h1>
            <p class="lead text-muted">Welcome, <?= htmlspecialchars($_SESSION['user']['name']); ?>! Here are your projects.</p>

            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_GET['type'] ?? 'info'); ?> mt-4" role="alert">
                    <?= htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger mt-4" role="alert">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4 mb-5">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-4">Assigned Projects</h5>
                    <div class="table-responsive">
                        <?php if (count($assigned_projects) > 0): ?>
                            <table class="table table-striped table-hover mt-3">
                                <thead>
                                    <tr>
                                        <th>Project Idea</th>
                                        <th>Client Name</th>
                                        <th>Assigned Deadline</th>
                                        <th>Color</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_projects as $project): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($project['project_idea'], 0, 75)) . '...' ?></td>
                                            <td><?= htmlspecialchars($project['user_name']) ?></td>
                                            <td><?= htmlspecialchars($project['assigned_deadline']) ?></td>
                                            <td><span style="display:inline-block;width:20px;height:20px;border:1px solid #ccc;background-color:<?= htmlspecialchars($project['selected_color']) ?>;"></span></td>
                                            <td>
                                                <form action="staff_dashboard.php" method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="request_id" value="<?= $project['id'] ?>">
                                                    <div class="input-group">
                                                        <input type="file" name="completed_file" class="form-control form-control-sm" required>
                                                        <button type="submit" name="mark_completed" class="btn btn-success btn-sm">Complete</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                You have no active projects assigned to you.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 rounded-4 mb-5">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-4">Completed Projects</h5>
                    <div class="table-responsive">
                        <?php if (count($completed_projects) > 0): ?>
                            <table class="table table-striped table-hover mt-3">
                                <thead>
                                    <tr>
                                        <th>Project Idea</th>
                                        <th>Client Name</th>
                                        <th>Completion Date</th>
                                        <th>Download</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_projects as $project): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($project['project_idea'], 0, 75)) . '...' ?></td>
                                            <td><?= htmlspecialchars($project['user_name']) ?></td>
                                            <td><?= htmlspecialchars($project['completion_date']) ?></td>
                                            <td>
                                                <a href="download_complete.php?request_id=<?= $project['id'] ?>" class="btn btn-info btn-sm text-dark">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                You have not completed any projects yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-4"><i class="bi bi-person-fill-gear me-2"></i>Submit a New Professional Service</h5>
                    <p class="text-muted small mb-4">Fill out the details below to propose a new service offering. Your submission will require Admin approval before going live on the public site.</p>
                    
                    <form action="add_service.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label fw-bold">Service Title</label>
                                <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Advanced Figma UI/UX Design">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label fw-bold">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" selected disabled>Select Category</option>
                                    <option value="UI/UX Design">UI/UX Design</option>
                                    <option value="Front-end Development">Front-end Development</option>
                                    <option value="Backend Integration">Backend Integration</option>
                                    <option value="Consulting">Consulting</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Detailed Service Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Describe what the client receives, key features, and deliverables."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="image" class="form-label fw-bold">Featured Image (Optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i>Submit for Admin Approval
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php include 'includes/footer.php'; ?>
