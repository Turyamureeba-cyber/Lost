<?php
// api/controllers/ReviewController.php

require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Business.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

class ReviewController {
    private $reviewModel;
    private $businessModel;

    public function __construct() {
        $this->reviewModel = new Review();
        $this->businessModel = new Business();
    }

    public function getByBusiness($businessId) {
        $business = $this->businessModel->getById($businessId);
        if (!$business) {
            Response::error('Business not found', 404);
        }
        
        $reviews = $this->reviewModel->getByBusiness($businessId);
        Response::json($reviews);
    }

    public function create($businessId) {
        AuthMiddleware::verifyToken(['user', 'business_owner', 'admin']);
        
        $business = $this->businessModel->getById($businessId);
        if (!$business) {
            Response::error('Business not found', 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            Response::error('Rating must be between 1 and 5');
        }
        
        $userId = $_SERVER['USER_ID'];
        $reviewId = $this->reviewModel->create(
            $businessId, 
            $userId, 
            $data['rating'], 
            $data['comment'] ?? null
        );
        
        Response::json(['id' => $reviewId], 201);
    }
}