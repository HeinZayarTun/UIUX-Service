<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profile_content = ''; 

if (isset($_SESSION['user'])) {
    $profile_image_path = $_SESSION['user']['profile_photo'] ?? null;
    
    $profile_content = '<i class="bi bi-person-circle fs-5"></i>'; 

    if ($profile_image_path && strtolower($profile_image_path) !== 'null') {
        $profile_content = '<img src="' . htmlspecialchars($profile_image_path) . '" alt="Profile" class="profile-nav-avatar">';
    }
}


$role = $_SESSION['user']['role'] ?? '';
        $dashboard_url = 'profile.php';
        $dashboard_icon = 'bi-person-circle';
        $dashboard_text = 'My Profile';
        
        if ($role === 'admin') {
            $dashboard_url = 'admin_dashboard.php'; // Admin Dashboard
            $dashboard_icon = 'bi-speedometer2';
            $dashboard_text = 'Dashboard';
        } elseif ($role === 'staff') {
            $dashboard_url = 'staff_dashboard.php'; // Staff Dashboard
            $dashboard_icon = 'bi-ui-checks';
            $dashboard_text = 'My Tasks';
        } elseif ($role === 'customer') {
            $dashboard_url = 'user_requests.php'; // Customer Requests
            $dashboard_icon = 'bi-list-check';
            $dashboard_text = 'Request Service';
        }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UIUX Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="CSS/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
    .profile-nav-avatar {
        width: 30px; 
        height: 30px; 
        border-radius: 50%; 
        object-fit: cover;
        border: 2px solid #5d98d8; 
    }
    /* Style for the button background */
    .btn-profile-toggle {
        background-color: transparent;
        border: none;
        color: #accdffff; /* Use primary color */
    }
    .btn-profile-toggle:hover {
        color: #f1ff9eff;
    }
    .navbar-brand {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: #343a40; 
    }
    .navbar-toggler-icon {
        /* This SVG code draws three white lines */
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='white' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
    }
    .navbar-toggler {
        border-color: white !important;
    }

    
    
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">UIUX Service</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu" aria-controls="navmenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navmenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Services</a></li>

                <?php if(isset($_SESSION['user'])): ?>
                    
                    <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle btn btn-sm btn-outline-secondary rounded-pill d-flex align-items-center" 
                    href="#" 
                    id="profileDropdown" 
                    role="button" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false" 
                    style="gap: 5px; padding-left: 0.75rem; padding-right: 0.75rem;">
        
                    <?= $profile_content; ?>
        
                    <span class="ms-1 fw-bold"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Profile'); ?></span>
                    </a>

    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2" aria-labelledby="profileDropdown">
        
        <li>
            <h6 class="dropdown-header text-truncate">
                <i class="bi bi-person-badge me-1"></i>
                <?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?> 
                <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($_SESSION['user']['role'] ?? 'Role')) ?></span>
            </h6>
        </li>   
        
        <li><hr class="dropdown-divider"></li>
        
         <li>
            <a class="dropdown-item" href="<?= htmlspecialchars($dashboard_url) ?>">
                <i class="bi <?= htmlspecialchars($dashboard_icon) ?> me-2"></i><?= $dashboard_text ?>
            </a>
        </li>

        <li>
            <a class="dropdown-item" href="profile.php">
                <i class="bi bi-person-circle me-2"></i>My Profile
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="message.php">
                <i class="bi bi-chat-dots-fill me-2"></i>Message Box
            </a>
        </li>
        
        <li>
            <a class="dropdown-item text-danger" href="logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </li>
    </ul>
</li>
                    
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>