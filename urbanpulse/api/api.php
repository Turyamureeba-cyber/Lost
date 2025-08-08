<?php
// api/index.php

require_once __DIR__ . '/utils/Database.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/Cache.php';
require_once __DIR__ . '/config/database.php';

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

// Initialize cache
$cache = new Cache();
$cacheKey = md5($_SERVER['REQUEST_URI'] . serialize($_GET));

// Check cache first for GET requests
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $cache->has($cacheKey)) {
    Response::json($cache->get($cacheKey));
    exit;
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
                        $data = $controller->getAll();
                        $cache->set($cacheKey, $data, 300); // Cache for 5 minutes
                        Response::json($data);
                    } elseif ($subResource === 'reviews') {
                        require_once __DIR__ . '/controllers/ReviewController.php';
                        $data = (new ReviewController())->getByBusiness($id);
                        $cache->set($cacheKey, $data, 300);
                        Response::json($data);
                    } else {
                        $data = $controller->getById($id);
                        $cache->set($cacheKey, $data, 300);
                        Response::json($data);
                    }
                    break;
                case 'POST':
                    $data = $controller->create();
                    Response::json($data, 201);
                    break;
                case 'PUT':
                    if ($id === null) {
                        Response::error('Business ID required', 400);
                    }
                    $data = $controller->update($id);
                    Response::json($data);
                    break;
                case 'DELETE':
                    if ($id === null) {
                        Response::error('Business ID required', 400);
                    }
                    $controller->delete($id);
                    Response::json(['message' => 'Business deleted successfully']);
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
                        $data = $controller->getAll();
                        $cache->set($cacheKey, $data, 300);
                        Response::json($data);
                    } elseif ($subResource === 'businesses') {
                        require_once __DIR__ . '/controllers/BusinessController.php';
                        $data = (new BusinessController())->getByCategory($id);
                        $cache->set($cacheKey, $data, 300);
                        Response::json($data);
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
                        $data = $controller->register();
                        Response::json($data, 201);
                    } elseif ($action === 'login') {
                        $data = $controller->login();
                        Response::json($data);
                    } elseif ($action === 'logout') {
                        $data = $controller->logout();
                        Response::json($data);
                    } else {
                        Response::error('Invalid auth action', 404);
                    }
                    break;
                case 'GET':
                    if ($action === 'me') {
                        $data = $controller->me();
                        Response::json($data);
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