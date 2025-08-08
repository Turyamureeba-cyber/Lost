<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and check if user is already logged in
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /business/index.php');
    exit();
}

$pageTitle = "Register | UrbanPulse";
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
            --success: #4cc9f0;
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
            position: relative;
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
        
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            background: var(--gray);
            transition: width 0.3s ease;
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
        
        .login-link {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        .terms {
            font-size: 0.8rem;
            color: var(--gray);
            text-align: center;
            margin-top: 1rem;
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
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <img src="../assets/images/logo.png" alt="UrbanPulse Logo">
                </div>
                <h1>Create Account</h1>
                <p class="auth-subtitle">Join UrbanPulse to discover local businesses</p>
            </div>

            <form id="register-form" class="auth-form" method="POST" action="register-process.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Choose a username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Your email address" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter" id="passwordStrength"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Repeat your password" required>
                        <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <button type="submit" class="auth-btn">
                    <i class="fas fa-user-plus"></i> Register
                </button>

                <div class="auth-divider">
                    <span>or continue with</span>
                </div>

                <div class="social-login">
                    <a href="google-auth.php?action=register" class="social-btn btn-google">
                        <img src="../assets/images/google-icon.png" alt="Google" width="18">
                        Google
                    </a>
                    <a href="#" class="social-btn btn-facebook">
                        <i class="fab fa-facebook-f"></i>
                        Facebook
                    </a>
                </div>

                <div class="terms">
                    By registering, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </div>

                <div class="auth-footer">
                    Already have an account? <a href="login.php" class="login-link">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const confirmPassword = document.querySelector('#confirm-password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        // Password strength indicator
        password.addEventListener('input', function() {
            const strengthMeter = document.getElementById('passwordStrength');
            const strength = calculatePasswordStrength(this.value);
            
            if (strength < 30) {
                strengthMeter.style.backgroundColor = '#dc3545'; // Red
            } else if (strength < 70) {
                strengthMeter.style.backgroundColor = '#ffc107'; // Yellow
            } else {
                strengthMeter.style.backgroundColor = '#28a745'; // Green
            }
            
            strengthMeter.style.width = strength + '%';
        });
        
        function calculatePasswordStrength(password) {
            let strength = 0;
            
            // Length contributes up to 40%
            strength += Math.min(password.length * 5, 40);
            
            // Character variety contributes up to 60%
            if (/[A-Z]/.test(password)) strength += 10;
            if (/[0-9]/.test(password)) strength += 10;
            if (/[^A-Za-z0-9]/.test(password)) strength += 10;
            if (password.length >= 8) strength += 10;
            if (password.length >= 12) strength += 10;
            if (password.length >= 16) strength += 10;
            
            return Math.min(strength, 100);
        }
        
        // Form validation
        document.getElementById('register-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            // Add additional validation as needed
            return true;
        });
    </script>
</body>
</html>