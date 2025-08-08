<?php
// includes/db.php

// Database configuration - should be defined in a separate config file
// includes/db.php

// Database configuration - use your actual credentials
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');  // Default XAMPP/WAMP username
if (!defined('DB_PASS')) define('DB_PASS', '');      // Default XAMPP/WAMP password (empty)
if (!defined('DB_NAME')) define('DB_NAME', 'business_directory');  // Your database name

if (!class_exists('Database')) {
    class Database {
        private $connection;

        public function __construct() {
            $this->connect();
        }

        private function connect() {
            try {
                $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                
                if ($this->connection->connect_error) {
                    throw new Exception("Connection failed: " . $this->connection->connect_error);
                }
                
                $this->connection->set_charset('utf8mb4');
                
            } catch (Exception $e) {
                die("Database error: " . $e->getMessage());
            }
        }

        public function getConnection() {
            return $this->connection;
        }

        public function preparedQuery($sql, $params = [], $types = "") {
            try {
                $stmt = $this->connection->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $this->connection->error);
                }
                
                if (!empty($params)) {
                    if (empty($types)) {
                        $types = str_repeat("s", count($params));
                    }
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                return $stmt;
                
            } catch (Exception $e) {
                error_log("Database error: " . $e->getMessage());
                return false;
            }
        }

        public function fetchAll($sql, $params = [], $types = "") {
            $stmt = $this->preparedQuery($sql, $params, $types);
            return $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : false;
        }

        public function fetchOne($sql, $params = [], $types = "") {
            $stmt = $this->preparedQuery($sql, $params, $types);
            return $stmt ? $stmt->get_result()->fetch_assoc() : false;
        }

        public function escape($value) {
            return $this->connection->real_escape_string($value);
        }

        public function insertId() {
            return $this->connection->insert_id;
        }

        public function error() {
            return $this->connection->error;
        }
    }
}

// Create database instance
if (!isset($db)) {
    $db = new Database();
}