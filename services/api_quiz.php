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
        
        // Contextual prompt via GeminiClient
        $prompt = "You are a teacher evaluating a quiz student.
        The student scored $score out of $total ($percentage%).
        
        Provide a short, encouraging feedback message (max 2 sentences).
        If the score is low, suggest reviewing the material.
        If the score is high, congratulate them.
        
        Output STRICT JSON: { \"feedback\": \"...\" }
        NO Markdown. NO formatting.";

        $feedback = "Good job! You scored $score/$total."; // Default fallback
        
        try {
            $client = new GeminiClient();
            $rawResponse = $client->generateResponse($prompt, true);

            // ROBUST CLEANING (Standardized)
            // 1. Encoding
            $cleanText = mb_convert_encoding($rawResponse, 'UTF-8', 'UTF-8');
            // 2. Markdown
            $cleanText = preg_replace('/^```json\s*|\s*```$/m', '', $cleanText);
            // 3. Extract JSON
            if (preg_match('/\{.*\}/s', $cleanText, $matches)) {
                $cleanText = $matches[0];
            }
            // 4. BOM & Control Chars
            $cleanText = preg_replace('/^\xEF\xBB\xBF/', '', $cleanText);
            $cleanText = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $cleanText);
            // 5. Trailing Commas
            $cleanText = preg_replace('/,\s*}/', '}', $cleanText);
            $cleanText = preg_replace('/,\s*]/', ']', $cleanText);

            $aiResult = json_decode($cleanText, true, 512, JSON_INVALID_UTF8_IGNORE);

            if (json_last_error() === JSON_ERROR_NONE && !empty($aiResult['feedback'])) {
                $feedback = $aiResult['feedback'];
            } else {
                 error_log("Quiz AI JSON Error: " . json_last_error_msg());
            }

        } catch (Exception $e) {
            error_log("Quiz AI Error: " . $e->getMessage());
            // Silently fail to default feedback if AI fails
        }

        echo json_encode(['success' => true, 'score' => $score, 'total' => $total, 'feedback' => $feedback]);
        return;
    }

    echo json_encode(['error' => 'unsupported_method']);
}
