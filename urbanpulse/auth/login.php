<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /urbanpulse/business/index.php');
    exit();
}

// Display registration success message
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $successMessage = 'Registration successful! Please log in with your credentials.';
}

// Process login form if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validate inputs
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    if (empty($errors)) {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Authentication successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];

                // Remember me functionality
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + 60 * 60 * 24 * 30; // 30 days
                    
                    setcookie('remember_token', $token, $expiry, '/');
                    
                    // Store token in database
                    $stmt = $pdo->prepare("
                        INSERT INTO user_sessions 
                        (user_id, session_token, expires_at, ip_address) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $user['id'],
                        $token,
                        date('Y-m-d H:i:s', $expiry),
                        $_SERVER['REMOTE_ADDR']
                    ]);
                }

                // Redirect to dashboard - corrected path
                header('Location: /urbanpulse/business/index.php');
                exit();
            } else {
                $errors['general'] = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Database error: ' . $e->getMessage();
        }
    }

    // Store errors and form data in session
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_form_data'] = ['email' => $email];
    header('Location: login.php');
    exit();
}

// Retrieve errors and form data from session if they exist
$errors = $_SESSION['login_errors'] ?? [];
$formData = $_SESSION['login_form_data'] ?? [];

// Clear the session variables so they don't persist
unset($_SESSION['login_errors']);
unset($_SESSION['login_form_data']);

$pageTitle = "Login | UrbanPulse";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .auth-container {
            width: 100%;
            max-width: 480px;
        }
        
        .auth-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }
        
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .auth-header {
            padding: 2.5rem 2.5rem 1.5rem;
            text-align: center;
        }
        
        .auth-logo {
            margin-bottom: 1.5rem;
        }
        
        .auth-logo img {
            height: 50px;
        }
        
        .auth-header h1 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        .auth-form {
            padding: 0 2.5rem 2.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            color: var(--dark);
            font-weight: 500;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .input-with-icon input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            outline: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            cursor: pointer;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .auth-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .remember-me label {
            font-size: 0.9rem;
            color: var(--dark);
            cursor: pointer;
        }
        
        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .auth-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .auth-divider {
            display: flex;
            align-items: center;
            color: var(--gray);
            margin: 1.5rem 0;
        }
        
        .auth-divider::before,
        .auth-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .auth-divider::before {
            margin-right: 1rem;
        }
        
        .auth-divider::after {
            margin-left: 1rem;
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .social-btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .btn-google {
            background: white;
            border: 1px solid #e2e8f0;
            color: var(--dark);
        }
        
        .btn-google:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }
        
        .btn-facebook {
            background: #1877f2;
            color: white;
            border: none;
        }
        
        .btn-facebook:hover {
            background: #166fe5;
        }
        
        .auth-footer {
            text-align: center;
            color: var(--gray);
            font-size: 0.95rem;
            margin-top: 1.5rem;
        }
        
        .register-link {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .register-link:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        @media (max-width: 576px) {
            .auth-header {
                padding: 2rem 2rem 1rem;
            }
            
            .auth-form {
                padding: 0 2rem 2rem;
            }
            
            .social-login {
                flex-direction: column;
            }
        }
        .back-to-home {
    margin-bottom: 20px;
}

.back-to-home a {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
}

.back-to-home a:hover {
    text-decoration: underline;
}
    </style>
</head>
<body>
    
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <img src="/urbanpulse/assets/images/logo.png" alt="UrbanPulse Logo">
                </div>
                 <div class="login-container">
            <div class="back-to-home">
                <a href="http://localhost/urbanpulse/index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </div>
                <h1>Welcome Back</h1>
                <p class="auth-subtitle">Login to your UrbanPulse account</p>
            </div>

            <?php if (!empty($successMessage)) : ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errors['general'])) : ?>
            <div class="alert alert-error" style="margin: 0 2.5rem 1rem;">
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
            <?php endif; ?>

            <form id="login-form" class="auth-form" method="POST" action="login.php">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="login-email" name="email" placeholder="Enter your email" 
                               value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                    </div>
                    <?php if (isset($errors['email'])) : ?>
                        <span class="error-message"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="login-password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                    <?php if (isset($errors['password'])) : ?>
                        <span class="error-message"><?= htmlspecialchars($errors['password']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="auth-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>

                <div class="auth-divider">
                    <span>or continue with</span>
                </div>

                <div class="social-login">
                    <a href="google-auth.php?action=login" class="social-btn btn-google">
                        <img src="/urbanpulse/assets/images/google-icon.png" alt="Google" width="18">
                        Google
                    </a>
                    <a href="#" class="social-btn btn-facebook">
                        <i class="fab fa-facebook-f"></i>
                        Facebook
                    </a>
                </div>

                <div class="auth-footer">
                    Don't have an account? <a href="register.php" class="register-link">Register here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#login-password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>