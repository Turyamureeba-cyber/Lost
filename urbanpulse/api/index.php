<?php
// api/index.php

require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/utils/Response.php';

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Parse request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', trim($requestUri, '/'));

// Remove 'api' from the path if present
if ($uriParts[0] === 'api') {
    array_shift($uriParts);
}

// Route the request
try {
    switch ($uriParts[0]) {
        case 'businesses':
            require_once __DIR__ . '/controllers/BusinessController.php';
            $controller = new BusinessController();
            
            $id = $uriParts[1] ?? null;
            $subResource = $uriParts[2] ?? null;
            
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    if ($id === null) {
                        $controller->getAll();
                    } elseif ($subResource === 'reviews') {
                        require_once __DIR__ . '/controllers/ReviewController.php';
                        (new ReviewController())->getByBusiness($id);
                    } else {
                        $controller->getById($id);
                    }
                    break;
                case 'POST':
                    $controller->create();
                    break;
                case 'PUT':
                    if ($id === null) {
                        Response::error('Business ID required', 400);
                    }
                    $controller->update($id);
                    break;
                case 'DELETE':
                    if ($id === null) {
                        Response::error('Business ID required', 400);
                    }
                    $controller->delete($id);
                    break;
                default:
                    Response::error('Method not allowed', 405);
            }
            break;
            
        case 'categories':
            require_once __DIR__ . '/controllers/CategoryController.php';
            $controller = new CategoryController();
            
            $id = $uriParts[1] ?? null;
            $subResource = $uriParts[2] ?? null;
            
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    if ($id === null) {
                        $controller->getAll();
                    } elseif ($subResource === 'businesses') {
                        require_once __DIR__ . '/controllers/BusinessController.php';
                        (new BusinessController())->getByCategory($id);
                    } else {
                        Response::error('Invalid category endpoint', 404);
                    }
                    break;
                default:
                    Response::error('Method not allowed', 405);
            }
            break;
            
        case 'auth':
            require_once __DIR__ . '/controllers/AuthController.php';
            $controller = new AuthController();
            
            $action = $uriParts[1] ?? null;
            
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    if ($action === 'register') {
                        $controller->register();
                    } elseif ($action === 'login') {
                        $controller->login();
                    } elseif ($action === 'logout') {
                        $controller->logout();
                    } else {
                        Response::error('Invalid auth action', 404);
                    }
                    break;
                case 'GET':
                    if ($action === 'me') {
                        $controller->me();
                    } else {
                        Response::error('Invalid auth action', 404);
                    }
                    break;
                default:
                    Response::error('Method not allowed', 405);
            }
            break;
            
        default:
            Response::error('Not Found', 404);
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}