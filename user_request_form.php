<?php
require_once 'config/db.php';
require_once 'includes/auth.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in or not a 'user'
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$message = '';
$message_type = '';

// Handle New Project Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_project'])) {
    $project_idea = trim($_POST['project_idea']);
    $selected_color = $_POST['selected_color'] ?? '#007bff';
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    if (empty($project_idea)) {
        $message = 'Please provide a project idea.';
        $message_type = 'danger';
    } else {
        if (!empty($deadline)) {
            $today = new DateTime();
            $deadline_dt = new DateTime($deadline);
            $today->setTime(0, 0, 0);

            if ($deadline_dt < $today) {
                $message = 'The desired deadline must be a future date.';
                $message_type = 'danger';
            }
        }
    }

    if ($message_type !== 'danger') {
        try {
            $sql = "INSERT INTO project_requests (user_id, project_idea, selected_color, deadline) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $project_idea, $selected_color, $deadline]);

            $message = 'Your project idea has been submitted successfully and is awaiting admin approval!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Database Error: ' . $e->getMessage();
            $message_type = 'danger';
            error_log('Form submission error: ' . $e->getMessage());
        }
    }
}

// Fetch user's projects from the database
try {
    $stmt = $pdo->prepare("
        SELECT pr.*, s.name as staff_name 
        FROM project_requests pr
        LEFT JOIN users s ON pr.staff_id = s.id
        WHERE pr.user_id = ?
        ORDER BY pr.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $user_projects = [];
    $message = 'An error occurred while fetching your projects.';
    $message_type = 'danger';
    error_log('Error fetching user projects: ' . $e->getMessage());
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Project Requests - UIUX Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f7f3e8; font-family: 'Poppins', sans-serif; color: #333; }
        .card { background-color: #eaf7ea; border-radius: 1rem; border: none; padding: 1.5rem; }
    </style>
</head>
<body>
<?php include './includes/header.php'; ?>

<div class="container my-5">
    <h1 class="display-5 fw-bold mb-3">My Project Requests</h1>
    <p class="lead text-muted">Welcome, <?= htmlspecialchars($_SESSION['user']['name']); ?>! Submit and track your projects.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type; ?>"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 mb-5">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-4">Submit a New Project Idea</h5>
            <form action="user_requests.php" method="POST">
                <div class="mb-3">
                    <label for="projectIdea" class="form-label">Project Idea</label>
                    <textarea class="form-control" id="projectIdea" name="project_idea" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="selectedColor" class="form-label">Select a Primary Color</label>
                    <input type="color" class="form-control form-control-color" id="selectedColor" name="selected_color" value="#007bff">
                </div>
                <div class="mb-3">
                    <label for="deadline" class="form-label">Desired Deadline</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" min="<?= date('Y-m-d'); ?>">
                </div>
                <button type="submit" name="submit_project" class="btn btn-primary rounded-pill px-4 mt-2">Submit Project</button>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-4">Your Project History</h5>
            <div class="table-responsive">
                <?php if (count($user_projects) > 0): ?>
                    <table class="table table-striped table-hover mt-3">
                        <thead>
                            <tr>
                                <th>Project Idea</th>
                                <th>Status</th>
                                <th>Assigned Staff</th>
                                <th>Assigned Deadline</th>
                                <th>Created Date</th>
                                <th>Completed File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_projects as $project): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($project['project_idea'], 0, 50)) . '...' ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php
                                            if ($project['status'] === 'pending') echo 'bg-warning text-dark';
                                            if ($project['status'] === 'approved') echo 'bg-success';
                                            if ($project['status'] === 'completed') echo 'bg-info text-dark';
                                            if ($project['status'] === 'rejected') echo 'bg-danger';
                                            ?>">
                                            <?= htmlspecialchars(ucfirst($project['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= $project['staff_name'] ? htmlspecialchars($project['staff_name']) : 'N/A' ?></td>
                                    <td><?= $project['assigned_deadline'] ? htmlspecialchars($project['assigned_deadline']) : 'N/A' ?></td>
                                    <td><?= htmlspecialchars($project['created_at']) ?></td>
                                    <td>
                                        <?php if ($project['status'] === 'completed' && $project['completed_file_path']): ?>
                                            <a href="<?= htmlspecialchars($project['completed_file_path']); ?>" target="_blank" class="btn btn-sm btn-info text-dark">Download</a>
                                        <?php else: ?>
                                            Awaiting
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">You have not submitted any projects yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'includes/footer.php'; ?>