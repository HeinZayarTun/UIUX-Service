<?php
require 'config/db.php';
require 'includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = $_SESSION['user']['id'] ?? $_SESSION['id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit;
}

// Fetch user info
// We specifically select 'profile_photo' which is now a LONGBLOB
$stmt = $pdo->prepare("SELECT id, name, email, role, password, bio, profile_photo FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Bio
    if (isset($_POST['update_bio'])) {
        $bio = trim($_POST['bio']);
        $stmt = $pdo->prepare("UPDATE users SET bio=? WHERE id=?");
        $stmt->execute([$bio, $userId]);
        $success = "Bio updated successfully.";
    }

    // Delete Bio
    if (isset($_POST['delete_bio'])) {
        $stmt = $pdo->prepare("UPDATE users SET bio=NULL WHERE id=?");
        $stmt->execute([$userId]);
        $success = "Bio deleted successfully.";
    }

    // Update Password
    if (isset($_POST['update_password'])) {
        $currentPass = $_POST['current_password'];
        $newPass     = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];

        if (!password_verify($currentPass, $user['password'])) {
            $error = "Current password is incorrect.";
        } elseif ($newPass !== $confirmPass) {
            $error = "New passwords do not match.";
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hashed, $userId]);
            $success = "Password updated successfully.";
        }
    }

    // ==========================================================
    // MODIFIED PROFILE PHOTO UPLOAD LOGIC (BLOB Storage)
    // ==========================================================
    if (isset($_POST['update_photo']) && isset($_FILES['profile_photo'])) {
        $file = $_FILES['profile_photo'];
        
        if ($file['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $mime_type = $file['type'];
            
            if (!in_array($mime_type, $allowed_types)) {
                $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            } else {
                // Read the file content
                $imgData = file_get_contents($file['tmp_name']);
                
                // Combine MIME type and image data for storage
                $dataToStore = $mime_type . "|" . $imgData;
                
                try {
                    // Use a prepared statement to bind the data as a Large Object (LOB)
                    $stmt = $pdo->prepare("UPDATE users SET profile_photo=? WHERE id=?");
                    $stmt->bindParam(1, $dataToStore, PDO::PARAM_LOB); 
                    $stmt->bindParam(2, $userId);
                    $stmt->execute();
                    
                    // NOTE: We do not update the session with BLOB data, as it is too large.
                    
                    $success = "Profile photo updated successfully (stored in DB).";
                } catch (PDOException $e) {
                    $error = "Database error while storing photo: " . $e->getMessage();
                }
            }
        } else {
            $error = "No file selected or an upload error occurred.";
        }
    }
    // ==========================================================
    
    // Refresh user data after updates to get the new BLOB
    $stmt = $pdo->prepare("SELECT id, name, email, role, password, bio, profile_photo FROM users WHERE id=?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ==========================================================
// IMAGE DISPLAY LOGIC (FOR COMBINED BLOB DATA)
// Parses the stored data (mime_type|binary_data) and generates a Base64 data URL
// ==========================================================
$image_src = null;
if (!empty($user['profile_photo'])) {
    $data = $user['profile_photo'];
    
    // Find the position of the separator "|"
    $separatorPos = strpos($data, '|');
    
    if ($separatorPos !== false) {
        // Extract the mime type (before "|") and the binary data (after "|")
        $mime_type = substr($data, 0, $separatorPos);
        $binary_data = substr($data, $separatorPos + 1);
        
        // Encode the binary data to base64
        $base64_img = base64_encode($binary_data);
        
        // Create the data URL for the image source
        $image_src = "data:{$mime_type};base64,{$base64_img}";
    }
}
?>

<?php include 'includes/header.php'; ?>

<style>
.profile-card-base {
    border-radius: 1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.profile-icon-fallback {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 3px solid #5d98d8;
    background-color: #f8f9fa; 
    color: #5d98d8; 
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto 1.5rem auto; 
}
.profile-img {
    object-fit: cover; 
    border: 3px solid #5d98d8;
    margin-bottom: 1.5rem;
}
.setting-card .card-title {
    color: #333;
    border-bottom: 2px solid #eee;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}
.info-label {
    font-weight: 600;
    color: #555;
    display: block;
    margin-top: 0.5rem;
}
.info-value {
    font-size: 1.1rem;
    color: #000;
}
</style>

<div class="container mt-5">
    <h2 class="mb-4">Profile Settings</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card profile-card-base text-center p-4">
                <div class="card-body">
                    <?php 
                        // Check if we have BLOB data for the image
                        if ($image_src):
                    ?>
                        <img src="<?= htmlspecialchars($image_src) ?>" 
                            alt="Profile Photo" 
                            class="img-fluid rounded-circle profile-img" 
                            width="150" 
                            height="150">
                    <?php else: ?>
                        <div class="profile-icon-fallback">
                            <i class="bi bi-person-badge display-3"></i>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="mb-1"><?= htmlspecialchars($user['name']) ?></h3>
                    <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="badge bg-primary fs-6 mt-2 mb-4"><?= htmlspecialchars(ucfirst($user['role'])) ?></span>

                    <hr class="my-4">
                    
                    <h5 class="text-start text-primary mb-2">Bio</h5>
                    <p class="text-start text-dark">
                        <?= $user['bio'] ? nl2br(htmlspecialchars($user['bio'])) : '<em class="text-muted">No bio set</em>' ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            
            <div class="card shadow mb-4 setting-card">
                <div class="card-body">
                    <h5 class="card-title">Change Profile Photo</h5>
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-9">
                                <input type="file" name="profile_photo" class="form-control" accept="image/jpeg, image/png, image/gif" required>
                            </div>
                            <div class="col-3">
                                <button type="submit" name="update_photo" class="btn btn-primary w-100">Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow mb-4 setting-card">
                <div class="card-body">
                    <h5 class="card-title">Manage Bio</h5>
                    <form method="post">
                        <textarea name="bio" class="form-control mb-3" rows="3" placeholder="Enter your bio here..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        <button type="submit" name="update_bio" class="btn btn-success me-2">Save Bio</button>
                        <?php if ($user['bio']): ?>
                            <button type="submit" name="delete_bio" class="btn btn-outline-danger">Delete Bio</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card shadow mb-4 setting-card">
                <div class="card-body">
                    <h5 class="card-title">Change Password</h5>
                    <form method="post">
                        <input type="password" name="current_password" class="form-control mb-3" placeholder="Current Password" required>
                        <input type="password" name="new_password" class="form-control mb-3" placeholder="New Password" required>
                        <input type="password" name="confirm_password" class="form-control mb-3" placeholder="Confirm New Password" required>
                        <button type="submit" name="update_password" class="btn btn-warning">Update Password</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>