<?php
// api/controllers/BusinessController.php

require_once __DIR__ . '/../models/Business.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

class BusinessController {
    private $businessModel;
    private $categoryModel;

    public function __construct() {
        $this->businessModel = new Business();
        $this->categoryModel = new Category();
    }

    public function getAll() {
        $filters = [
            'category_id' => $_GET['category_id'] ?? null,
            'search' => $_GET['search'] ?? null,
            'city' => $_GET['city'] ?? null,
            'sort' => $_GET['sort'] ?? 'newest',
            'limit' => $_GET['limit'] ?? 10,
            'page' => $_GET['page'] ?? 1
        ];

        $businesses = $this->businessModel->getAll($filters);
        
        // Add pagination metadata
        $total = $this->businessModel->getCount($filters);
        $totalPages = ceil($total / $filters['limit']);
        
        Response::json([
            'data' => $businesses,
            'meta' => [
                'current_page' => (int)$filters['page'],
                'total_pages' => $totalPages,
                'total_items' => $total,
                'per_page' => (int)$filters['limit']
            ]
        ]);
    }

    public function getById($id) {
        $business = $this->businessModel->getById($id);
        
        if (!$business) {
            Response::error('Business not found', 404);
        }
        
        Response::json($business);
    }

    public function create() {
        AuthMiddleware::verifyToken(['admin', 'business_owner']);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (empty($data['name']) || empty($data['category_id']) || empty($data['address']) || 
            empty($data['city']) || empty($data['state']) || empty($data['zip_code']) || empty($data['phone'])) {
            Response::error('Missing required fields');
        }
        
        $userId = $_SERVER['USER_ID'] ?? null;
        $businessId = $this->businessModel->create($data, $userId);
        
        Response::json(['id' => $businessId], 201);
    }

    public function update($id) {
        $business = $this->businessModel->getById($id);
        if (!$business) {
            Response::error('Business not found', 404);
        }
        
        // Check if user is admin or owner
        AuthMiddleware::verifyToken(['admin']);
        if ($_SERVER['USER_ROLE'] !== 'admin' && $business['owner_id'] != $_SERVER['USER_ID']) {
            Response::error('Unauthorized', 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $success = $this->businessModel->update($id, $data);
        
        if ($success) {
            Response::json(['message' => 'Business updated successfully']);
        } else {
            Response::error('Failed to update business');
        }
    }

    public function delete($id) {
        $business = $this->businessModel->getById($id);
        if (!$business) {
            Response::error('Business not found', 404);
        }
        
        // Check if user is admin or owner
        AuthMiddleware::verifyToken(['admin']);
        if ($_SERVER['USER_ROLE'] !== 'admin' && $business['owner_id'] != $_SERVER['USER_ID']) {
            Response::error('Unauthorized', 403);
        }
        
        $success = $this->businessModel->delete($id);
        
        if ($success) {
            Response::json(['message' => 'Business deleted successfully']);
        } else {
            Response::error('Failed to delete business');
        }
    }

    public function getByCategory($categoryId) {
        $category = $this->categoryModel->getById($categoryId);
        if (!$category) {
            Response::error('Category not found', 404);
        }
        
        $businesses = $this->businessModel->getAll(['category_id' => $categoryId]);
        Response::json($businesses);
    }
}