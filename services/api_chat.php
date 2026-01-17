<?php
// services/api_chat.php
header('Content-Type: application/json');

require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/gemini_client.php';

// Only logged in users
if (!current_user()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get Input
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (!$message) {
    echo json_encode(['error' => 'Empty message']);
    exit;
}

// Gemini Client
try {
    $client = new GeminiClient();
    
    $systemPrompt = "You are a helpful and friendly English Tutor chatbot inside the FeedLearn application. 
    Your goal is to help students with grammar, vocabulary, and learning tips. 
    Keep your answers concise, encouraging, and easy to understand (CEFR A2-B1 level). 
    If asked about FeedLearn features:
    - Quiz: For grammar/vocab practice.
    - Speaking: For recording and pronunciation.
    - Writing: For essay feedback.
    User's message: ";

    $response = $client->generateResponse($systemPrompt . $message, false); // Plain text response

    echo json_encode(['reply' => $response]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'AI Error: ' . $e->getMessage()]);
}
?>
