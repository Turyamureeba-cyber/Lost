<?php
// register.php

// 1. FIRST - Load configuration and dependencies
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Initialize all variables with empty values
$errors = [
    'username' => '',
    'email' => '',
    'password' => '',
    'general' => ''
];
$username = '';
$email = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Get and sanitize inputs
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($username)) {
        $errors['username'] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors['username'] = "Username must be 3-20 characters (letters, numbers, underscores)";
    }

    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters";
    }

    // Only proceed if no validation errors
    if (empty(array_filter($errors))) {
        try {
            // Check if username exists using prepared statement
            $checkUser = $db->fetchOne(
                "SELECT id FROM users WHERE username = ?", 
                [$username],
                "s"
            );
            
            if ($checkUser) {
                $errors['username'] = "Username already exists";
            } else {
                // Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user using prepared statement
                $result = $db->preparedQuery(
                    "INSERT INTO users (username, email, password) VALUES (?, ?, ?)",
                    [$username, $email, $passwordHash],
                    "sss"
                );
                
                if ($result) {
                    $_SESSION['success'] = "Registration successful! Please login.";
                    header("Location: login.php");
                    exit();
                } else {
                    $errors['general'] = "Registration failed. Please try again.";
                }
            }
        } catch(Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            $errors['general'] = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - My Place</title>
    <style>
        /* [Keep all your existing CSS styles] */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .auth-container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
        
        .auth-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: #4361ee;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-primary {
            background-color: #4361ee;
            color: white;
            width: 100%;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            width: 100%;
            margin-top: 1rem;
        }
        
        .text-danger {
            color: #dc3545;
            font-size: 0.875rem;
        }
        
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <h1>My Place</h1>
                <p>Create your account</p>
            </div>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                    <?php if (!empty($errors['username'])): ?>
                        <span class="text-danger"><?php echo htmlspecialchars($errors['username']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="text-danger"><?php echo htmlspecialchars($errors['email']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <?php if (!empty($errors['password'])): ?>
                        <span class="text-danger"><?php echo htmlspecialchars($errors['password']); ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary">Register</button>
                <a href="login.php" class="btn btn-secondary">Back to Login</a>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>
</body>
</html>