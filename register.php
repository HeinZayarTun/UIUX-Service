<?php
require_once __DIR__ . '/includes/auth.php';

$err = '';
$success = '';

function validatePassword($password) {
    if (strlen($password) < 6) {
        return 'Password must be at least 6 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must include at least one uppercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must include at least one number.';
    }
    if (!preg_match('/[^a-zA-Z0-9\s]/', $password)) {
        return 'Password must include at least one special character.';
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!$name || !$email || !$password) {
        $err = 'All fields required.';
    } else {
        $err = validatePassword($password);
    }

    if (!$err) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
        try {
            $stmt->execute([$name, $email, $hash, 'customer']);
            $success = '✅ Registration successful! You can now <a href="login.php">log in</a>.';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $err = 'Email already registered.';
            } else {
                $err = 'An unexpected database error occurred.';
                error_log('Registration Error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
            opacity: 0.9;
        }
        .login-container-modern {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background-color: #000000ff;
            border-radius: 10px;
            box-sizing: border-box;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
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
        .back-arrow-modern:hover { color: #00a6ff; }
        .title-modern {
            font-size: 2.5rem;
            font-weight: 700;
            color: #e0e0e0;
            margin: 0;
        }
        .form-group-modern { margin-bottom: 25px; }
        .input-wrapper-modern {
            position: relative;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #666;
            padding-bottom: 8px;
            transition: border-bottom-color 0.3s ease;
        }
        .input-wrapper-modern:focus-within { border-bottom-color: #00a6ff; }
        .icon-modern { font-size: 1.2rem; color: #888; margin-right: 15px; }
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
        .input-field-modern::placeholder { color: transparent; }
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
        .input-label-modern.input-label-active {
            top: -15px;
            font-size: 0.8rem;
            color: #00a6ff;
        }
        .alert-danger, .alert-success {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: #f8d7da;
            border: 1px solid #dc3545;
        }
        .alert-success {
            background-color: rgba(25, 135, 84, 0.15);
            color: #d1e7dd;
            border: 1px solid #28a745;
        }
        .alert-icon {
            font-size: 1.2rem;
            margin-right: 10px;
        }
        .login-button-modern {
            width: 100%;
            padding: 15px;
            background-color: #3b3d4a;
            border: none;
            border-radius: 10px;
            color: #e0e0e0;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .login-button-modern:hover {
            background-color: #020012ff;
            transform: translateY(-2px);
            color: #00a6ff;
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
        .signup-link-modern:hover { color: #00a6ff; }
        .password-hint {
            color: #666;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        .valid { color: #28a745; }
        .invalid { color: #dc3545; }
    </style>
</head>
<body>

<div class="login-container-modern">
    <div class="header-modern">
        <a href="index.php" class="back-arrow-modern">
            <i class="bi bi-house-heart-fill"></i>
        </a>
        <h1 class="title-modern">Register</h1>
    </div>

    <?php if ($err): ?>
        <div class="alert-danger">
            <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
            <div><?php echo $err; ?></div>
        </div>
    <?php elseif ($success): ?>
        <div class="alert-success">
            <i class="bi bi-check-circle-fill alert-icon"></i>
            <div><?php echo $success; ?></div>
        </div>
    <?php endif; ?>

    <form method="post" class="form-modern">
        <div class="form-group-modern mb-2">
            <div class="input-wrapper-modern">
                <i class="bi bi-person-fill icon-modern"></i>
                <input name="name" id="name" class="input-field-modern" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                       placeholder="" required>
                <label for="name" class="input-label-modern">Enter Name</label>
            </div>
        </div>

        <div class="form-group-modern mb-2">
            <div class="input-wrapper-modern">
                <i class="bi bi-envelope-fill icon-modern"></i>
                <input name="email" id="email" type="email" class="input-field-modern" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                       placeholder="" required>
                <label for="email" class="input-label-modern">Enter Email</label>
            </div>
        </div>

        <div class="form-group-modern mb-2">
            <div class="input-wrapper-modern">
                <i class="bi bi-lock-fill icon-modern"></i>
                <input name="password" id="password" type="password" class="input-field-modern" 
                       placeholder=" " required
                       minlength="6"
                       pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9\s]).{6,}$"
                       title="Must be at least 6 characters long, include one uppercase letter, one number, and one special character.">
                <label for="password" class="input-label-modern">Password</label>
            </div>
            <div class="password-hint" id="password-hint">
                <span id="length" class="invalid">❌ At least 6 characters</span><br>
                <span id="uppercase" class="invalid">❌ At least 1 uppercase letter</span><br>
                <span id="number" class="invalid">❌ At least 1 number</span><br>
                <span id="special" class="invalid">❌ At least 1 special character</span>
            </div>
        </div>

        <button type="submit" class="login-button-modern mt-5">Register</button>

        <p class="signup-text-modern mt-4">
            Already have an account? <a href="login.php" class="signup-link-modern">Log in.</a>
        </p>
    </form>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const lengthReq = document.getElementById('length');
    const uppercaseReq = document.getElementById('uppercase');
    const numberReq = document.getElementById('number');
    const specialReq = document.getElementById('special');

    passwordInput.addEventListener('input', function () {
        const value = passwordInput.value;

        // Length check
        if (value.length >= 6) {
            lengthReq.classList.replace('invalid', 'valid');
            lengthReq.textContent = "✅ At least 6 characters";
        } else {
            lengthReq.classList.replace('valid', 'invalid');
            lengthReq.textContent = "❌ At least 6 characters";
        }

        // Uppercase check
        if (/[A-Z]/.test(value)) {
            uppercaseReq.classList.replace('invalid', 'valid');
            uppercaseReq.textContent = "✅ At least 1 uppercase letter";
        } else {
            uppercaseReq.classList.replace('valid', 'invalid');
            uppercaseReq.textContent = "❌ At least 1 uppercase letter";
        }

        // Number check
        if (/[0-9]/.test(value)) {
            numberReq.classList.replace('invalid', 'valid');
            numberReq.textContent = "✅ At least 1 number";
        } else {
            numberReq.classList.replace('valid', 'invalid');
            numberReq.textContent = "❌ At least 1 number";
        }

        // Special character check
        if (/[^a-zA-Z0-9\s]/.test(value)) {
            specialReq.classList.replace('invalid', 'valid');
            specialReq.textContent = "✅ At least 1 special character";
        } else {
            specialReq.classList.replace('valid', 'invalid');
            specialReq.textContent = "❌ At least 1 special character";
        }
    });

    // Floating label fix
    const inputFields = document.querySelectorAll('.input-field-modern');
    function checkInputContent(input) {
        const label = input.nextElementSibling;
        if (label) {
            if (input.value.length > 0) {
                label.classList.add('input-label-active');
            } else {
                label.classList.remove('input-label-active');
            }
        }
    }
    inputFields.forEach(input => {
        checkInputContent(input);
        input.addEventListener('blur', () => checkInputContent(input));
        input.addEventListener('input', () => checkInputContent(input));
    });
</script>

</body>
</html>
