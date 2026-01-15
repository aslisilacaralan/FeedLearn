<?php
function handle_quiz_api()
{
    $dataFile = __DIR__ . '/quiz_data.json';

    if (!file_exists($dataFile)) {
        echo json_encode(['error' => 'quiz data not found']);
        return;
    }

    $raw = json_decode(file_get_contents($dataFile), true);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Return questions without answers
        $questions = [];
        foreach ($raw['questions'] as $q) {
            $copy = $q;
            unset($copy['correct']);
            $questions[] = $copy;
        }
        echo json_encode(['success' => true, 'title' => $raw['title'] ?? 'Quiz', 'questions' => $questions]);
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $answers = $body['answers'] ?? [];
        $score = 0;
        $total = count($raw['questions']);
        foreach ($raw['questions'] as $q) {
            $id = $q['id'];
            if (isset($answers[$id]) && $answers[$id] == $q['correct']) {
                $score++;
            }
        }
        echo json_encode(['success' => true, 'score' => $score, 'total' => $total]);
        return;
    }

    echo json_encode(['error' => 'unsupported_method']);
}
