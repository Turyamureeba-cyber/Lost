<?php
// api/controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            Response::error('Username, email and password are required');
        }
        
        if (!Validator::validateEmail($data['email'])) {
            Response::error('Invalid email format');
        }
        
        if (strlen($data['password']) < 8) {
            Response::error('Password must be at least 8 characters long');
        }
        
        // Check if email already exists
        $existingUser = $this->userModel->getByEmail($data['email']);
        if ($existingUser) {
            Response::error('Email already in use', 409);
        }
        
        // Create user
        $userId = $this->userModel->create(
            $data['username'],
            $data['email'],
            $data['password']
        );
        
        Response::json(['id' => $userId], 201);
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['email']) || empty($data['password'])) {
            Response::error('Email and password are required');
        }
        
        $user = $this->userModel->getByEmail($data['email']);
        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }
        
        // Generate session token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $this->userModel->createSession(
            $user['id'],
            $token,
            $expiresAt,
            $deviceInfo,
            $ipAddress
        );
        
        Response::json([
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }

    public function logout() {
        $token = $this->getBearerToken();
        if (!$token) {
            Response::error('No token provided', 401);
        }
        
        $this->userModel->deleteSession($token);
        Response::json(['message' => 'Logged out successfully']);
    }

    public function me() {
        $token = $this->getBearerToken();
        if (!$token) {
            Response::error('No token provided', 401);
        }
        
        $session = $this->userModel->getSession($token);
        if (!$session) {
            Response::error('Invalid or expired token', 401);
        }
        
        $user = $this->userModel->getById($session['user_id']);
        if (!$user) {
            Response::error('User not found', 404);
        }
        
        Response::json($user);
    }

    private function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}