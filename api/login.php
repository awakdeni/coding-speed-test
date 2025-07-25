<?php
// api/login.php
session_start();

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers for local development
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

// Rate limiting
$rateLimitFile = __DIR__ . '/../data/rate_limit.json';
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$currentTime = time();

// Load rate limit data
$rateLimitData = [];
if (file_exists($rateLimitFile)) {
    $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?? [];
}

// Clean old entries (older than 1 hour)
$rateLimitData = array_filter($rateLimitData, function($timestamp) use ($currentTime) {
    return $currentTime - $timestamp < 3600;
});

// Check rate limit (max 10 login attempts per hour per IP)
$ipAttempts = array_filter($rateLimitData, function($timestamp, $ip) use ($clientIP) {
    return $ip === $clientIP;
}, ARRAY_FILTER_USE_BOTH);

if (count($ipAttempts) >= 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Terlalu banyak percobaan login. Coba lagi nanti.']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Handle logout
if (isset($input['action']) && $input['action'] === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logout berhasil']);
    exit();
}

// Handle login
$username = trim($input['username'] ?? '');

// Validate username
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username tidak boleh kosong']);
    exit();
}

if (strlen($username) < 3 || strlen($username) > 20) {
    echo json_encode(['success' => false, 'message' => 'Username harus 3-20 karakter']);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username hanya boleh mengandung huruf, angka, dan underscore']);
    exit();
}

// Sanitize username
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

// Load users data
$usersFile = __DIR__ . '/../data/users.json';
$users = [];

if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
}

// Check if user exists
$userExists = false;
foreach ($users as $user) {
    if (strcasecmp($user['username'], $username) === 0) {
        $userExists = true;
        $username = $user['username']; // Use original case
        break;
    }
}

// Add user if doesn't exist
if (!$userExists) {
    $newUser = [
        'id' => uniqid('user_', true),
        'username' => $username,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => date('Y-m-d H:i:s'),
        'ip_address' => hash('sha256', $clientIP) // Hash IP for privacy
    ];
    
    $users[] = $newUser;
    
    // Save users data
    if (!file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data user']);
        exit();
    }
} else {
    // Update last login for existing user
    for ($i = 0; $i < count($users); $i++) {
        if (strcasecmp($users[$i]['username'], $username) === 0) {
            $users[$i]['last_login'] = date('Y-m-d H:i:s');
            break;
        }
    }
    
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

// Set session
$_SESSION['username'] = $username;
$_SESSION['login_time'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Record login attempt for rate limiting
$rateLimitData[$clientIP . '_' . time()] = $currentTime;
file_put_contents($rateLimitFile, json_encode($rateLimitData));

echo json_encode([
    'success' => true, 
    'message' => 'Login berhasil',
    'username' => $username
]);
?>