<?php

header('Content-Type: application/json');


$valid_api_keys = [
    'key123' => 'UserA',
    'key456' => 'UserB'
];


$headers = getallheaders();
$api_key = $headers['X-API-Key'] ?? null;


if (!$api_key || !array_key_exists($api_key, $valid_api_keys)) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid or missing API Key"]);
    exit;
}


$requestPath = $_GET['request_path'] ?? '';

switch ($requestPath) {
    case 'users':
        require __DIR__ . '/services/service_users.php';
        break;
    case 'products':
        require __DIR__ . '/services/service_products.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(["error" => "Service not found"]);
        break;
}


$rateLimitDir = __DIR__ . '/ratelimit_data/';
if (!file_exists($rateLimitDir)) {
    mkdir($rateLimitDir, 0755, true);
}

$rateLimitFile = $rateLimitDir . $api_key . '.json';
$currentTime = time();
$limit = 10;
$window = 60;

if (file_exists($rateLimitFile)) {
    $data = json_decode(file_get_contents($rateLimitFile), true);
    $timestamp = $data['timestamp'];
    $count = $data['count'];

    if (($currentTime - $timestamp) > $window) {
        $data = ['timestamp' => $currentTime, 'count' => 1];
    } else {
        if ($count >= $limit) {
            http_response_code(429);
            echo json_encode(["error" => "Rate limit exceeded"]);
            log_request($api_key, $requestPath ?? '', 429); // log it
            exit;
        }
        $data['count'] += 1;
    }
} else {
    $data = ['timestamp' => $currentTime, 'count' => 1];
}

file_put_contents($rateLimitFile, json_encode($data));

function log_request($api_key, $path, $status) {
    $logLine = sprintf(
        "[%s] - IP: %s - API Key: %s - Path: %s - Status: %d\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $api_key ?: 'None',
        $path,
        $status
    );
    file_put_contents(__DIR__ . '/logs/gateway.log', $logLine, FILE_APPEND);
}
log_request($api_key ?? 'None', $requestPath ?? '', 401); 
log_request($api_key, $requestPath, 200); 
log_request($api_key, $requestPath, 404); 
