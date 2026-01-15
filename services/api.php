<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/evaluator.php';

header('Content-Type: application/json; charset=utf-8');

$module = $_GET['module'] ?? '';

if ($module === 'activities') {
    echo json_encode([
        'ok' => true,
        'data' => db_get_activities(),
        'error' => null
    ]);
    exit;
}

if ($module === 'evaluate_writing') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'error' => 'invalid_method']);
        exit;
    }

    $raw = json_decode(file_get_contents('php://input'), true);
    $text = trim((string)($raw['user_text'] ?? ''));

    if ($text === '') {
        echo json_encode(['ok' => false, 'error' => 'empty_text']);
        exit;
    }

    $result = evaluate_writing($text);

    echo json_encode([
        'ok' => true,
        'data' => [
            'score' => $result['score_percent'],
            'cefr_level' => $result['cefr'],
            'weak_topics' => $result['weak_topics'],
            'feedback' => $result['feedback']
        ],
        'error' => null
    ]);
    exit;
}

echo json_encode([
    'ok' => false,
    'error' => 'unknown_module'
]);