<?php
// api/models/Review.php

require_once __DIR__ . '/../utils/Database.php';

class Review {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByBusiness($businessId) {
        $query = "SELECT r.*, u.username, u.avatar_url 
                  FROM reviews r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.business_id = ? 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$businessId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($businessId, $userId, $rating, $comment) {
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            throw new Exception('Rating must be between 1 and 5');
        }
        
        $query = "INSERT INTO reviews (business_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$businessId, $userId, $rating, $comment]);
        
        // Update business rating
        $this->updateBusinessRating($businessId);
        
        return $this->db->lastInsertId();
    }

    private function updateBusinessRating($businessId) {
        $query = "UPDATE businesses b
                  SET rating = (
                      SELECT AVG(rating) FROM reviews WHERE business_id = ?
                  ),
                  review_count = (
                      SELECT COUNT(*) FROM reviews WHERE business_id = ?
                  )
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$businessId, $businessId, $businessId]);
    }
}