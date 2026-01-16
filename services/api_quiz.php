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

        // Generate AI Feedback via Gemini
        require_once __DIR__ . '/gemini_client.php';
        
        $percentage = ($total > 0) ? round(($score / $total) * 100) : 0;
        
        // Contextual prompt
        $prompt = "You are a teacher evaluating a quiz student.
        The student scored $score out of $total ($percentage%).
        
        Provide a short, encouraging feedback message (max 2 sentences).
        If the score is low, suggest reviewing the material.
        If the score is high, congratulate them.
        
        Output valid JSON: { \"feedback\": \"...\" }";

        $feedback = "Good job!"; // Default fallback
        
        try {
            $aiResult = gemini_generate_json($prompt, ['temperature' => 0.7]);
            if (!empty($aiResult['feedback'])) {
                $feedback = $aiResult['feedback'];
            }
        } catch (Exception $e) {
            // Silently fail to default feedback if AI fails, to not block the user
        }

        echo json_encode(['success' => true, 'score' => $score, 'total' => $total, 'feedback' => $feedback]);
        return;
    }

    echo json_encode(['error' => 'unsupported_method']);
}
