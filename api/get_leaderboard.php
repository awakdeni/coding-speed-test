<?php
// api/get_leaderboard.php

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

// Get language filter
$language = isset($_GET['language']) ? trim($_GET['language']) : 'all';
$language = htmlspecialchars($language, ENT_QUOTES, 'UTF-8');

// Validate language
$allowedLanguages = ['all', 'python', 'javascript', 'cpp', 'go', 'rust', 'scala', 'c'];
if (!in_array($language, $allowedLanguages)) {
    echo json_encode(['success' => false, 'message' => 'Bahasa tidak valid']);
    exit();
}

// Load scores
$scoresFile = __DIR__ . '/../data/scores.json';

if (!file_exists($scoresFile)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit();
}

$scores = json_decode(file_get_contents($scoresFile), true) ?? [];

// Filter by language if specified
if ($language !== 'all') {
    $scores = array_filter($scores, function($score) use ($language) {
        return $score['language'] === $language;
    });
}

// Group by username and get best score for each user
$userBestScores = [];

foreach ($scores as $score) {
    $username = $score['username'];
    $scoreValue = $score['wpm'] * ($score['accuracy'] / 100); // Weighted score
    
    if (!isset($userBestScores[$username]) || $scoreValue > $userBestScores[$username]['score_value']) {
        $userBestScores[$username] = [
            'username' => $username,
            'language' => $score['language'],
            'wpm' => $score['wpm'],
            'accuracy' => $score['accuracy'],
            'errors' => $score['errors'],
            'time' => $score['time'],
            'timestamp' => $score['timestamp'],
            'score_value' => $scoreValue
        ];
    }
}

// Sort by score value (WPM * accuracy percentage)
usort($userBestScores, function($a, $b) {
    if ($a['score_value'] === $b['score_value']) {
        // If scores are equal, sort by accuracy
        if ($a['accuracy'] === $b['accuracy']) {
            // If accuracy is equal, sort by WPM
            return $b['wpm'] - $a['wpm'];
        }
        return $b['accuracy'] - $a['accuracy'];
    }
    return $b['score_value'] <=> $a['score_value'];
});

// Limit to top 50 to prevent large response
$userBestScores = array_slice($userBestScores, 0, 50);

// Remove score_value from response (internal use only)
$userBestScores = array_map(function($score) {
    unset($score['score_value']);
    return $score;
}, $userBestScores);

// Add cache headers for better performance
header('Cache-Control: public, max-age=60'); // Cache for 1 minute
header('ETag: ' . md5(serialize($userBestScores)));

// Check if client has cached version
$clientETag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
$serverETag = md5(serialize($userBestScores));

if ($clientETag === $serverETag) {
    http_response_code(304);
    exit();
}

echo json_encode([
    'success' => true,
    'data' => $userBestScores,
    'total' => count($userBestScores),
    'language_filter' => $language,
    'generated_at' => date('Y-m-d H:i:s')
]);
?>