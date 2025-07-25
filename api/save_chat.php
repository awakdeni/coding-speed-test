<?php
// api/save_chat.php
session_start();

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Anda harus login untuk mengirim pesan']);
    exit();
}

// Rate limiting for chat
$rateLimitFile = __DIR__ . '/../data/chat_rate_limit.json';
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$username = $_SESSION['username'];
$currentTime = time();

$rateLimitData = [];
if (file_exists($rateLimitFile)) {
    $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?? [];
}

// Clean old entries (older than 1 minute)
$rateLimitData = array_filter($rateLimitData, function($timestamp) use ($currentTime) {
    return $currentTime - $timestamp < 60;
});

// Check rate limit (max 10 messages per minute per user)
$userKey = $username . '_' . $clientIP;
$userAttempts = array_filter($rateLimitData, function($timestamp, $key) use ($userKey) {
    return strpos($key, $userKey) === 0;
}, ARRAY_FILTER_USE_BOTH);

if (count($userAttempts) >= 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Terlalu banyak pesan. Tunggu sebentar.']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['message'])) {
    echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong']);
    exit();
}

$message = trim($input['message']);

// Validate message
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong']);
    exit();
}

if (strlen($message) > 200) {
    echo json_encode(['success' => false, 'message' => 'Pesan terlalu panjang (maksimal 200 karakter)']);
    exit();
}

// Content filtering
$prohibitedWords = ['spam', 'hack', 'cheat', 'bot', 'script'];
$messageLower = strtolower($message);

foreach ($prohibitedWords as $word) {
    if (strpos($messageLower, $word) !== false) {
        echo json_encode(['success' => false, 'message' => 'Pesan mengandung kata yang dilarang']);
        exit();
    }
}

// Check for repeated messages (anti-spam)
$chatFile = __DIR__ . '/../data/chat.json';
$messages = [];

if (file_exists($chatFile)) {
    $messages = json_decode(file_get_contents($chatFile), true) ?? [];
}

// Check last 5 messages from same user
$userMessages = array_filter($messages, function($msg) use ($username) {
    return $msg['username'] === $username;
});

$userMessages = array_slice($userMessages, -5);

foreach ($userMessages as $msg) {
    if ($msg['message'] === $message && (time() - strtotime($msg['timestamp'])) < 300) {
        echo json_encode(['success' => false, 'message' => 'Jangan mengirim pesan yang sama berulang-ulang']);
        exit();
    }
}

// Sanitize message
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Create new message
$newMessage = [
    'id' => uniqid('msg_', true),
    'username' => $username,
    'message' => $message,
    'timestamp' => date('Y-m-d H:i:s'),
    'ip_hash' => hash('sha256', $clientIP)
];

// Add new message
$messages[] = $newMessage;

// Keep only last 100 messages to prevent file bloat
if (count($messages) > 100) {
    $messages = array_slice($messages, -100);
}

// Save messages
if (!file_put_contents($chatFile, json_encode($messages, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pesan']);
    exit();
}

// Record rate limit attempt
$rateLimitData[$userKey . '_' . time()] = $currentTime;
file_put_contents($rateLimitFile, json_encode($rateLimitData));

// Log message for moderation
error_log("Chat message: User {$username}, Message: {$message}");

echo json_encode([
    'success' => true, 
    'message' => 'Pesan berhasil dikirim',
    'message_id' => $newMessage['id']
]);
?>