<?php
// api/save_score.php
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
    echo json_encode(['success' => false, 'message' => 'Anda harus login untuk menyimpan skor']);
    exit();
}

// Rate limiting for score saving
$rateLimitFile = __DIR__ . '/../data/score_rate_limit.json';
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$username = $_SESSION['username'];
$currentTime = time();

$rateLimitData = [];
if (file_exists($rateLimitFile)) {
    $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?? [];
}

// Clean old entries (older than 5 minutes)
$rateLimitData = array_filter($rateLimitData, function($timestamp) use ($currentTime) {
    return $currentTime - $timestamp < 300;
});

// Check rate limit (max 20 score saves per 5 minutes per user)
$userKey = $username . '_' . $clientIP;
$userAttempts = array_filter($rateLimitData, function($timestamp, $key) use ($userKey) {
    return strpos($key, $userKey) === 0;
}, ARRAY_FILTER_USE_BOTH);

if (count($userAttempts) >= 20) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Terlalu banyak percobaan menyimpan skor. Tunggu sebentar.']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit();
}

// Validate required fields
$requiredFields = ['language', 'wpm', 'accuracy', 'errors', 'time'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Field {$field} diperlukan"]);
        exit();
    }
}

// Validate and sanitize data
$language = trim($input['language']);
$wpm = intval($input['wpm']);
$accuracy = intval($input['accuracy']);
$errors = intval($input['errors']);
$time = intval($input['time']);

// Validate language
$allowedLanguages = ['python', 'javascript', 'cpp', 'go', 'rust', 'scala', 'c'];
if (!in_array($language, $allowedLanguages)) {
    echo json_encode(['success' => false, 'message' => 'Bahasa pemrograman tidak valid']);
    exit();
}

// Validate score ranges
if ($wpm < 0 || $wpm > 300) {
    echo json_encode(['success' => false, 'message' => 'WPM tidak valid (0-300)']);
    exit();
}

if ($accuracy < 0 || $accuracy > 100) {
    echo json_encode(['success' => false, 'message' => 'Akurasi tidak valid (0-100%)']);
    exit();
}

if ($errors < 0 || $errors > 1000) {
    echo json_encode(['success' => false, 'message' => 'Jumlah kesalahan tidak valid']);
    exit();
}

if ($time < 1 || $time > 3600) {
    echo json_encode(['success' => false, 'message' => 'Waktu tidak valid (1-3600 detik)']);
    exit();
}

// Additional validation: check if the score is realistic
if ($wpm > 200 && $accuracy > 95) {
    echo json_encode(['success' => false, 'message' => 'Skor terlalu tinggi, kemungkinan tidak valid']);
    exit();
}

// Load existing scores
$scoresFile = __DIR__ . '/../data/scores.json';
$scores = [];

if (file_exists($scoresFile)) {
    $scores = json_decode(file_get_contents($scoresFile), true) ?? [];
}

// Create new score entry
$newScore = [
    'id' => uniqid('score_', true),
    'username' => $username,
    'language' => $language,
    'wpm' => $wpm,
    'accuracy' => $accuracy,
    'errors' => $errors,
    'time' => $time,
    'timestamp' => date('Y-m-d H:i:s'),
    'ip_hash' => hash('sha256', $clientIP),
    'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
];

// Add new score
$scores[] = $newScore;

// Keep only last 10000 scores to prevent file bloat
if (count($scores) > 10000) {
    // Sort by timestamp and keep the newest ones
    usort($scores, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    $scores = array_slice($scores, 0, 10000);
}

// Save scores
if (!file_put_contents($scoresFile, json_encode($scores, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan skor']);
    exit();
}

// Record rate limit attempt
$rateLimitData[$userKey . '_' . time()] = $currentTime;
file_put_contents($rateLimitFile, json_encode($rateLimitData));

// Log score for analysis
error_log("Score saved: User {$username}, Language {$language}, WPM {$wpm}, Accuracy {$accuracy}%");

echo json_encode([
    'success' => true, 
    'message' => 'Skor berhasil disimpan!',
    'score_id' => $newScore['id'],
    'rank_info' => 'Peringkat akan diperbarui dalam beberapa saat'
]);
?>