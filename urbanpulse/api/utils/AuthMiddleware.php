<?php
// api/utils/AuthMiddleware.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/Response.php';

class AuthMiddleware {
    public static function verifyToken($allowedRoles = []) {
        $token = self::getBearerToken();
        if (!$token) {
            Response::error('No token provided', 401);
        }
        
        $userModel = new User();
        $session = $userModel->getSession($token);
        
        if (!$session) {
            Response::error('Invalid or expired token', 401);
        }
        
        // Check if user has required role
        if (!empty($allowedRoles) {
            if (!in_array($session['role'], $allowedRoles)) {
                Response::error('Insufficient permissions', 403);
            }
        }
        
        // Store user info in server context
        $_SERVER['USER_ID'] = $session['user_id'];
        $_SERVER['USER_ROLE'] = $session['role'];
    }

    private static function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}