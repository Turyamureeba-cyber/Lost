<?php
// api/models/Category.php

require_once __DIR__ . '/../utils/Database.php';

class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Get all categories
    public function getAll() {
        $query = "SELECT * FROM categories ORDER BY name";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get single category by ID
    public function getById($id) {
        $query = "SELECT * FROM categories WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create new category
    public function create($data) {
        $query = "INSERT INTO categories (name, slug, icon) VALUES (:name, :slug, :icon)";
        $stmt = $this->db->prepare($query);
        
        // Generate slug from name if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        return $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':icon' => $data['icon'] ?? null
        ]);
    }

    // Update existing category
    public function update($id, $data) {
        $query = "UPDATE categories SET name = :name, slug = :slug, icon = :icon WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        // Generate slug from name if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':icon' => $data['icon'] ?? null
        ]);
    }

    // Delete category
    public function delete($id) {
        $query = "DELETE FROM categories WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    // Helper function to generate slug
    private function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return $slug;
    }
}