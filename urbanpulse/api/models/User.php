<?php
// api/models/User.php

require_once __DIR__ . '/../utils/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByEmail($email) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT id, username, email, role, avatar_url FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($username, $email, $password, $role = 'user') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username, $email, $passwordHash, $role]);
        
        return $this->db->lastInsertId();
    }

    public function createSession($userId, $token, $expiresAt, $deviceInfo, $ipAddress) {
        $query = "INSERT INTO user_sessions (user_id, session_token, expires_at, device_info, ip_address) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId, $token, $expiresAt, $deviceInfo, $ipAddress]);
    }

    public function getSession($token) {
        $query = "SELECT us.*, u.role 
                  FROM user_sessions us 
                  JOIN users u ON us.user_id = u.id 
                  WHERE us.session_token = ? AND us.expires_at > NOW()";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteSession($token) {
        $query = "DELETE FROM user_sessions WHERE session_token = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$token]);
    }
}