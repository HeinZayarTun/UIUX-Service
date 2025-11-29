<?php
session_start();
require_once __DIR__ . '/includes/auth.php'; 
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email=? AND status="active"');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'admin') {
            header('Location: admin_dashboard.php');
            exit;
        } elseif ($user['role'] === 'staff') {
            header('Location: staff_dashboard.php');
            exit;
        } else {
            header('Location: index.php');
            exit;
        }
    } else {
        $err = 'Invalid credentials or inactive account';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <style>
        
        @import url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #fefcda;
            background-image: url(img/bg.jpg);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #e0e0e0;
            opacity:   0.9;
        }

        .login-container-modern {
            width: 100%;
            max-width: 400px; /* Max width similar to a mobile screen */
            padding: 30px;
            background-color: #000000ff; 
            border-radius: 10px; /* Soft edges */
            box-sizing: border-box; /* Include padding in width */
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4); /* More pronounced shadow for a 'floating' effect */
        }

        .header-modern {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
        }
    

        .back-arrow-modern {
            font-size: 1.8rem;
            color: #e0e0e0;
            margin-right: 20px;
            text-decoration: none;
        }

        .title-modern {
            font-size: 2.5rem;
            font-weight: 700;
            color: #e0e0e0;
            margin: 0;
        }

        .form-group-modern {
            margin-bottom: 25px;
        }

        .input-wrapper-modern {
            position: relative;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #666; /* Underline effect for inputs */
            padding-bottom: 8px;
            transition: border-bottom-color 0.3s ease;
        }

        .input-wrapper-modern:focus-within {
            border-bottom-color: #00a6ffff;; /* Orange highlight on focus */
        }

        .icon-modern {
            font-size: 1.2rem;
            color: #888;
            margin-right: 15px;
        }

        .input-field-modern {
            flex-grow: 1;
            background: transparent;
            border: none;
            color: #e0e0e0;
            font-size: 1rem;
            padding: 5px 0;
            outline: none;
            width: 100%; 
        }

        /* Hiding the placeholder with the floating label */
        .input-field-modern::placeholder {
            color: transparent;
        }

        /* Style for the active label */
        .input-label-modern {
            position: absolute;
            left: 45px; 
            top: 5px;
            color: #888;
            font-size: 1rem;
            pointer-events: none;
            transition: all 0.2s ease-out;
        }

        .input-field-modern:focus + .input-label-modern,
        .input-label-modern.input-label-active { /* Added .input-label-active for pre-filled state */
            top: -15px; /* Move label up */
            font-size: 0.8rem;
            color: #00a6ffff;; /* Orange color when active/focused */
        }

        /* Custom styling for the error message to fit the dark theme */
        .alert-danger {
            background-color: #3b3d4a;
            color: #dc3545;
            border: 1px solid #dc3545;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }

        .login-button-modern {
            width: 100%;
            padding: 15px;
            background-color: #3b3d4a; /* Darker button background */
            border: none;
            border-radius: 10px;
            color: #e0e0e0;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Subtle shadow */
        }

        .login-button-modern:hover {
            background-color: #000418ff;
            color: #00a6ffff;;
            transform: translateY(-2px);
        }

        .signup-text-modern {
            text-align: center;
            font-size: 0.95rem;
            color: #888;
        }

        .signup-link-modern {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .signup-link-modern:hover {
            color: #00a6ffff;; /* Orange highlight on hover */
        }
    </style>
</head>
<body>

<div class="login-container-modern">
    <div class="header-modern">
        <a href="index.php" class="back-arrow-modern">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="title-modern">Log In</h1>
    </div>

    <?php if ($err) : ?>
        <div class="alert alert-danger"><?php echo $err; ?></div>
    <?php endif; ?>

    <form method="post" class="form-modern">
        <div class="form-group-modern mb-2">
            <div class="input-wrapper-modern">
                <!-- Changed icon to a more appropriate one for email -->
                <i class="bi bi-envelope-fill icon-modern"></i> 
                <input type="email" id="email" name="email" class="input-field-modern" placeholder="">
                <label for="email" class="input-label-modern">Enter Email</label>
            </div>
        </div>

        <div class="form-group-modern mb-2">
            <div class="input-wrapper-modern">
                <i class="bi bi-lock-fill icon-modern"></i>
                <input type="password" id="password" name="password" class="input-field-modern" placeholder=" ">
                <label for="password" class="input-label-modern">Password</label>
            </div>
        </div>

        <button type="submit" class="login-button-modern mt-5">Log in</button>

        <p class="signup-text-modern mt-4">
            First time here? <a href="register.php" class="signup-link-modern">Sign up.</a>
        </p>
    </form>
</div>

<script>
    // Get all input fields that use the floating label effect
    const inputFields = document.querySelectorAll('.input-field-modern');

    // Function to check if an input has content and activate the label
    function checkInputContent(input) {
        const label = input.nextElementSibling;
        if (input.value.length > 0) {
            label.classList.add('input-label-active');
        } else {
            label.classList.remove('input-label-active');
        }
    }

    // Loop through each input field
    inputFields.forEach(input => {
        // Check on page load in case the browser auto-filled the fields
        checkInputContent(input);

        // Add event listeners to handle focus and blur
        input.addEventListener('focus', () => {
            const label = input.nextElementSibling;
            label.classList.add('input-label-active');
        });

        input.addEventListener('blur', () => {
            checkInputContent(input);
        });
    });
</script>

</body>
</html>
