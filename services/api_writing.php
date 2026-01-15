<?php
function handle_writing_api()
{
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../config/auth.php';
    require_once __DIR__ . '/evaluator.php';

    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'ok' => false,
            'error' => 'METHOD_NOT_ALLOWED'
        ]);
        return;
    }

    $user = current_user();
    if (!$user) {
        echo json_encode([
            'ok' => false,
            'error' => 'UNAUTHORIZED'
        ]);
        return;
    }

    // Text al (form veya JSON body)
    $body = json_decode(file_get_contents('php://input'), true);
    $text = $body['text'] ?? $_POST['text'] ?? '';

    if (trim($text) === '') {
        echo json_encode([
            'ok' => false,
            'error' => 'EMPTY_TEXT'
        ]);
        return;
    }

    // 🔥 GERÇEK AI DEĞERLENDİRME
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
}
handle_writing_api();