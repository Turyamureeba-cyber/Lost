<?php
// C:\wamp64\www\urbanpulse\auth\register-process.php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Correct database connection path
require_once __DIR__ . '/../includes/db.php'; // If moving up one level is sufficient

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize variables
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];

    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters';
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Check if user exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors['general'] = 'Username or email already exists';
            }
        } catch (PDOException $e) {
            $errors['general'] = 'Database error: ' . $e->getMessage();
        }
    }

    // Create user if no errors
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$username, $email, $password_hash]);
            
            // Set success message
            $_SESSION['registration_success'] = true;
            header('Location: login.php?registration=success');
            exit();
            
        } catch (PDOException $e) {
            $errors['general'] = 'Registration failed: ' . $e->getMessage();
        }
    }

    // If we got here, there were errors
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_form_data'] = ['username' => $username, 'email' => $email];
    header('Location: register.php');
    exit();
} else {
    // Not a POST request
    header('Location: register.php');
    exit();
}