<?php
// api/get_chat.php

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Load chat messages
$chatFile = __DIR__ . '/../data/chat.json';

if (!file_exists($chatFile)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit();
}

$messages = json_decode(file_get_contents($chatFile), true) ?? [];

// Sort by timestamp (newest first, but we'll reverse for display)
usort($messages, function($a, $b) {
    return strtotime($a['timestamp']) - strtotime($b['timestamp']);
});

// Get only last 50 messages for performance
$messages = array_slice($messages, -50);

// Remove sensitive data before sending to client
$publicMessages = array_map(function($message) {
    return [
        'id' => $message['id'],
        'username' => $message['username'],
        'message' => $message['message'],
        'timestamp' => $message['timestamp']
    ];
}, $messages);

// Add cache headers
header('Cache-Control: public, max-age=10'); // Cache for 10 seconds
header('ETag: ' . md5(serialize($publicMessages)));

// Check if client has cached version
$clientETag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$serverETag = md5(serialize($publicMessages));

if ($clientETag === $serverETag) {
    http_response_code(304);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $publicMessages,
    'total' => count($publicMessages),
    'generated_at' => date('Y-m-d H:i:s')
]);
?>