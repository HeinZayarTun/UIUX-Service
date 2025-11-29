<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
include __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT p.*, u.name as customer_name, s.name as staff_name FROM projects p JOIN users u ON p.customer_id=u.id LEFT JOIN users s ON p.assigned_staff_id=s.id WHERE p.id=?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { echo '<div class="alert alert-danger">Project not found.</div>'; include __DIR__ . '/includes/footer.php'; exit; }

$can_view = false;
if (is_admin()) $can_view = true;
elseif (is_customer() && $_SESSION['user']['id']==$p['customer_id']) $can_view = true;
elseif (is_staff() && $_SESSION['user']['id']==$p['assigned_staff_id']) $can_view = true;

if (!$can_view){ echo '<div class="alert alert-warning">You do not have permission to view this project.</div>'; include __DIR__ . '/includes/footer.php'; exit; }
?>
<h2><?php echo htmlspecialchars($p['title']); ?></h2>
<p><?php echo nl2br(htmlspecialchars($p['description'])); ?></p>
<p>Customer: <?php echo htmlspecialchars($p['customer_name']); ?></p>
<p>Status: <?php echo htmlspecialchars($p['status']); ?></p>
<p>Assigned staff: <?php echo htmlspecialchars($p['staff_name'] ?? '—'); ?></p>
<p>Estimate hours: <?php echo htmlspecialchars($p['estimate_hours'] ?? '—'); ?></p>
<p>Deadline: <?php echo htmlspecialchars($p['deadline'] ?? '—'); ?></p>

<?php if(is_admin()): ?>
  <hr>
  <h4>Admin Actions</h4>
  <form method="post" action="admin_dashboard.php" class="d-inline">
    <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
    <?php if($p['status']==='requested'): ?>
      <button name="action" value="approve" class="btn btn-success">Approve</button>
      <button name="action" value="decline" class="btn btn-danger">Decline</button>
    <?php endif; ?>
  </form>
  <button class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#assignForm">Assign/Update</button>
  <div class="collapse mt-2" id="assignForm">
    <form method="post" action="admin_dashboard.php" class="row g-2">
      <input type="hidden" name="assign_project_id" value="<?php echo $p['id']; ?>">
      <div class="col-md-4">
        <select name="staff_id" class="form-select" required>
          <option value="">Select staff</option>
          <?php
          $staffs = $pdo->query("SELECT id,name FROM users WHERE role='staff' AND status='active'")->fetchAll();
          foreach($staffs as $s) echo '<option value="'.$s['id'].'">'.htmlspecialchars($s['name']).'</option>';
          ?>
        </select>
      </div>
      <div class="col-md-2"><input name="estimate_hours" type="number" class="form-control" placeholder="Est hrs"></div>
      <div class="col-md-3"><input name="deadline" type="date" class="form-control"></div>
      <div class="col-md-3"><button name="assign" class="btn btn-primary">Assign</button></div>
    </form>
  </div>
<?php endif; ?>

<?php if(is_staff() && $_SESSION['user']['id']==$p['assigned_staff_id']): ?>
  <hr>
  <h4>Staff Actions</h4>
  <?php if($p['status']==='assigned' || $p['status']==='in_progress'): ?>
    <form method="post" action="staff_dashboard.php">
      <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
      <?php if($p['status']==='assigned'): ?>
        <button name="start" class="btn btn-success">Start Project</button>
      <?php endif; ?>
      <?php if($p['status']==='in_progress'): ?>
        <button name="complete" class="btn btn-primary">Mark Completed</button>
      <?php endif; ?>
    </form>
  <?php else: ?>
    <div class="alert alert-info">No actions available.</div>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>