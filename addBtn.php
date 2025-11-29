<?php require 'config/db.php'; ?>
 
<style>
    body {
        position: relative;
    }
    .fab-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 1000;
    }
    .fab-btn {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #28a745; 
        border-color: #28a745; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: background-color 0.3s ease; 
    }
    .fab-btn:hover {
        background-color: #1f0025 !important; 
        border-color: #e3ffafff !important;
        
    }
    .fab-menu {
        position: absolute;
        bottom: 100%;
        right: 0;
        margin-bottom: 1rem;
        transform-origin: bottom right;
        animation: fadeInScaleUp 0.3s ease-out forwards;
        display: none;
        padding: 0;
        list-style: none;
    }
    .fab-menu.show {
        display: block;
    }
    .fab-menu li {
        margin-bottom: 0.5rem;
    }
    .fab-menu a {
        display: flex;
        align-items: center;
        background-color: #ffffff;
        color: #333;
        text-decoration: none;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: background-color 0.2s;
        white-space: nowrap;
    }
    .fab-menu a:hover {
        background-color: #f0f0f0;
    }
    @keyframes fadeInScaleUp {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
            /* Corrected typo: Removed the extra '}' here */
        }
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="CSS/styles.css">

<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php if (isset($_SESSION['user'])): ?>
    <div class="fab-container">
        <ul class="fab-menu" id="fabMenu">
            <?php if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'staff'): ?>
                <li>
                    <a href="message.php">
                        <i class="bi bi-chat-text me-2"></i> Messages
                    </a>
                </li>
                <li>
                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                        <a href="admin_dashboard.php">
                            <i class="bi bi-speedometer me-2"></i> Dashboard
                        </a>
                    <?php elseif ($_SESSION['user']['role'] === 'staff'): ?>
                        <a href="staff_dashboard.php">
                            <i class="bi bi-speedometer me-2"></i> Dashboard
                        </a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['user']['role'] === 'customer'): ?>
                <li>
                    <a href="./message.php">
                        <i class="bi bi-chat-text me-2"></i> Chat Box
                    </a>
                </li>
                <li>
                    <a href="user_requests.php">
                        <i class="bi bi-list-task me-2"></i> Requests Projects
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        
        <button type="button" class="btn btn-primary fab-btn" id="fabButton">
            <i class="bi bi-plus-lg fs-4 text-white"></i>
        </button>
    </div>
<?php endif; ?>

<script>
    document.getElementById('fabButton').addEventListener('click', function() {
        document.getElementById('fabMenu').classList.toggle('show');
    });

    
    document.addEventListener('click', function(event) {
        const fabContainer = document.querySelector('.fab-container');
        if (!fabContainer.contains(event.target) && document.getElementById('fabMenu').classList.contains('show')) {
            document.getElementById('fabMenu').classList.remove('show');
        }
    });
</script>