<?php
require_once 'config/db.php';
require_once 'includes/auth.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow 'admin' access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// PHP Date for the MIN attribute in the modal
$today = date('Y-m-d'); 

$message = '';
$message_type = '';

// Fetch staff list for assignment dropdown
try {
    $staff_stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'staff'");
    $staff_members = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error gracefully
    $staff_members = [];
    error_log('Error fetching staff: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;

    // Reject request
    if (isset($_POST['reject_request']) && $request_id) {
        try {
            $stmt = $pdo->prepare("UPDATE project_requests SET status = 'rejected', staff_id = NULL, assigned_deadline = NULL WHERE id = ?");
            $stmt->execute([$request_id]);
            $message = 'Request successfully rejected.';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error rejecting request: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }

    // Assign project
    if (isset($_POST['assign_project']) && $request_id) {
        $staff_id = $_POST['staff_id'] ?? null;
        $assigned_deadline = $_POST['assigned_deadline'] ?? null;

        if (empty($staff_id) || empty($assigned_deadline)) {
            $message = 'Please select a staff member and a deadline.';
            $message_type = 'danger';
        } else {
            
            try {
                $stmt = $pdo->prepare("UPDATE project_requests SET status = 'approved', staff_id = ?, assigned_deadline = ? WHERE id = ?");
                $stmt->execute([$staff_id, $assigned_deadline, $request_id]);
                $message = 'Project successfully assigned and approved.';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Error assigning project: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

 
    header("Location: manage_requests.php?message=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT pr.*, u.name AS user_name, s.name AS staff_name
        FROM project_requests pr
        JOIN users u ON pr.user_id = u.id
        LEFT JOIN users s ON pr.staff_id = s.id
        ORDER BY pr.created_at DESC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requests = [];
    error_log('Error fetching requests: ' . $e->getMessage());
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Project Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        /* Ensures that even with table-responsive, the buttons are stacked nicely on mobile */
        @media (max-width: 767.98px) {
            /* Hides less critical columns on extra-small/small screens */
            .table-responsive .d-none.d-md-table-cell {
                display: none !important;
            }
            /* Adjust button size and margin for better fit in the actions column */
            .table tbody td:last-child button {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
                margin-right: 0.2rem !important;
                margin-bottom: 0.2rem;
            }
        }
    </style>
</head>

<body>
    <?php include './includes/header.php'; ?>

    <div class="container my-5">
        <h1 class="display-5 fw-bold mb-3">Manage Project Requests</h1>
        <p class="lead text-muted">Review, reject, and assign project ideas to staff members.</p>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_GET['type'] ?? 'info') ?> mt-4" role="alert">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h5><i class="bi bi-person-fill me-2"></i>All Project Requests</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mt-3">

                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Idea</th>
                                <th class="d-none d-md-table-cell">Color</th>
                                <th class="d-none d-md-table-cell">Requested Deadline</th>
                                <th>Status</th>
                                <th class="d-none d-md-table-cell">Assigned Staff</th>
                                <th class="d-none d-md-table-cell">Assigned Deadline</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($requests): ?>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request['user_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($request['project_idea'], 0, 30)) . '...' ?></td> 
                                        
                                        <td class="d-none d-md-table-cell">
                                            <span style="display:inline-block;width:20px;height:20px;border:1px solid #ccc;background-color:<?= htmlspecialchars($request['selected_color']) ?>"></span>
                                        </td>
                                        
                                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($request['deadline'] ?: 'N/A') ?></td>
                                        
                                        <td>
                                            <span class="badge 
                                            <?= $request['status'] === 'pending' ? 'bg-warning text-dark' : '' ?>
                                            <?= $request['status'] === 'approved' ? 'bg-success' : '' ?>
                                            <?= $request['status'] === 'completed' ? 'bg-info text-dark' : '' ?>
                                            <?= $request['status'] === 'rejected' ? 'bg-danger' : '' ?>">
                                                <?= htmlspecialchars(ucfirst($request['status'])) ?>
                                            </span>
                                        </td>
                                        
                                        <td class="d-none d-md-table-cell"><?= $request['staff_name'] ? htmlspecialchars($request['staff_name']) : 'N/A' ?></td>
                                        <td class="d-none d-md-table-cell"><?= $request['assigned_deadline'] ?: 'N/A' ?></td>
                                        
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                
                                                <button 
                                                    type="button" 
                                                    class="btn btn-success btn-sm me-1 mb-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#assignModal" 
                                                    data-bs-request-id="<?= $request['id'] ?>"
                                                    data-bs-deadline="<?= htmlspecialchars($request['deadline'] ?: $today) ?>"
                                                >
                                                    <i class="bi bi-person-plus-fill d-md-none"></i>
                                                    <span class="d-none d-md-inline">Assign</span>
                                                </button>

                                                <form action="manage_requests.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                    <input type="hidden" name="reject_request" value="1">
                                                    <button type="submit" class="btn btn-danger btn-sm mb-1">
                                                        <i class="bi bi-x-lg d-md-none"></i>
                                                        <span class="d-none d-md-inline">Reject</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>Action Taken</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No project requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="manage_requests.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="modal-request-id">
                        <div class="mb-3">
                            <label for="staffSelect" class="form-label">Assign to Staff</label>
                            <select class="form-select" id="staffSelect" name="staff_id" required>
                                <option value="">Select Staff</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="assignedDeadline" class="form-label">Set Assigned Deadline</label>
                            
                            <input
                                type="date"
                                class="form-control"
                                id="assignedDeadline"
                                name="assigned_deadline"
                                required
                                min="<?= $today ?>"
                                >

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_project" class="btn btn-primary">Assign Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        var assignModal = document.getElementById('assignModal');
        assignModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            
            // 1. Get the Request ID and set the hidden input
            var requestId = button.getAttribute('data-bs-request-id');
            document.getElementById('modal-request-id').value = requestId;
            
            // 2. Get the user's requested deadline from the button's data attribute
            var requestedDeadline = button.getAttribute('data-bs-deadline');
            
            // 3. Set the MAX attribute on the assignedDeadline input field
            var assignedDeadlineInput = document.getElementById('assignedDeadline');
            assignedDeadlineInput.setAttribute('max', requestedDeadline);
            
            // Optional: Pre-fill the field with the max date for admin convenience
            assignedDeadlineInput.value = requestedDeadline; 
        });
    </script>
    
</body>

</html>
<?php include 'includes/footer.php'; ?>