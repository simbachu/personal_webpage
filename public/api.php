<?php

declare(strict_types=1);

// Minimal front controller for API endpoints

// Resolve base path like index.php does
$is_dev = (basename(__DIR__) === 'dev');
$base_path = $is_dev ? dirname(dirname(__DIR__)) : dirname(__DIR__);
$env_prefix = $is_dev ? '/dev' : '';

$vendor_autoload = $base_path . '/httpd.private' . $env_prefix . '/vendor/autoload.php';
require_once $vendor_autoload;

use App\Api\SensorBatchController;

// Route only /api/sensor/batch for now
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($requestUri !== '/api/sensor/batch') {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Collect headers in a portable way
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (str_starts_with($name, 'HTTP_')) {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
}

$rawBody = file_get_contents('php://input') ?: '';

$controller = new SensorBatchController();
$result = $controller->handle($method, $headers, $rawBody);

http_response_code($result['status'] ?? 200);
header('Content-Type: application/json');
echo json_encode($result['body'] ?? []);


