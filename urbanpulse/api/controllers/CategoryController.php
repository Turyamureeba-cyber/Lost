<?php
// api/controllers/CategoryController.php

require_once __DIR__ . '/../models/Category.php';

class CategoryController {
    private $categoryModel;

    public function __construct() {
        $this->categoryModel = new Category();
    }

    // Get all categories
    public function getAll() {
        $categories = $this->categoryModel->getAll();
        $this->sendResponse(200, $categories);
    }

    // Get single category by ID
    public function getById($id) {
        $category = $this->categoryModel->getById($id);
        
        if ($category) {
            $this->sendResponse(200, $category);
        } else {
            $this->sendResponse(404, ['message' => 'Category not found']);
        }
    }

    // Create new category
    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name'])) {
            $this->sendResponse(400, ['message' => 'Name is required']);
            return;
        }
        
        if ($this->categoryModel->create($data)) {
            $this->sendResponse(201, ['message' => 'Category created successfully']);
        } else {
            $this->sendResponse(500, ['message' => 'Failed to create category']);
        }
    }

    // Update existing category
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name'])) {
            $this->sendResponse(400, ['message' => 'Name is required']);
            return;
        }
        
        if ($this->categoryModel->update($id, $data)) {
            $this->sendResponse(200, ['message' => 'Category updated successfully']);
        } else {
            $this->sendResponse(500, ['message' => 'Failed to update category']);
        }
    }

    // Delete category
    public function delete($id) {
        if ($this->categoryModel->delete($id)) {
            $this->sendResponse(200, ['message' => 'Category deleted successfully']);
        } else {
            $this->sendResponse(500, ['message' => 'Failed to delete category']);
        }
    }

    // Helper function to send JSON response
    private function sendResponse($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}