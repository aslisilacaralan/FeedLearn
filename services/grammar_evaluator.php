<?php
// services/grammar_evaluator.php

require_once __DIR__ . '/../config/constants.php';

/**
 * STRICT Grammar Evaluator using Gemini.
 * Fails loudly if API fails. No fallbacks.
 */
function evaluate_writing_with_gemini(string $text): array
{
    // 1. Validate Input
    if (trim($text) === '') {
        throw new InvalidArgumentException("Input text cannot be empty.");
    }

    // 2. Prepare Strict Prompt
    $prompt = <<<PROMPT
You are a strict English Grammar Examiner.

Your ONLY job is to detect grammar mistakes and score the student's writing.
Ignore content, creativity, and flow. Focus ONLY on grammar.

Student Text:
"$text"

OUTPUT FORMAT (STRICT JSON ONLY):
{
  "score": <number 40-95>,
  "cefr": <string "A1"|"A2"|"B1"|"B2"|"C1">,
  "errors": [
    {
      "wrong": "<exact substring from text>",
      "correct": "<corrected version>"
    }
  ]
}

RULES:
- If there are no errors, "errors" should be [].
- "score" must NOT be 0.
- Return ONLY valid JSON. No markdown (```json). No comments.
PROMPT;

    // 3. Call Gemini API using Shared Client
    require_once __DIR__ . '/gemini_client.php';
    $client = new GeminiClient();
    
    // Request JSON format explicitly
    $rawText = $client->generateResponse($prompt, true);

    // 4. Robust Cleaning (Standardized)
    $cleanText = $rawText;

    // A) UTF-8 Validation and Cleanup (Strip invalid UTF-8 sequences)
    // This fixes "Control character error" caused by truncated multi-byte chars
    $cleanText = mb_convert_encoding($cleanText, 'UTF-8', 'UTF-8');
    
    // B) Remove Markdown
    $cleanText = preg_replace('/^```json\s*|\s*```$/m', '', $cleanText);
    
    // C) Extract JSON Object
    if (preg_match('/\{.*\}/s', $cleanText, $matches)) {
        $cleanText = $matches[0];
    }
    
    // D) Remove BOM
    $cleanText = preg_replace('/^\xEF\xBB\xBF/', '', $cleanText);

    // E) Aggressive Control Character Removal (Replace with Space)
    // We strictly remove ASCII 0-31 (except usually we might want lines, but for strict JSON parsing safe mode, space is safer)
    // Note: This makes the JSON single-line effectively if newlines were formatting only.
    $cleanText = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $cleanText);
    
    // F) Fix Trailing Commas
    $cleanText = preg_replace('/,\s*}/', '}', $cleanText);
    $cleanText = preg_replace('/,\s*]/', ']', $cleanText);

    // 5. Parse
    // Use JSON_INVALID_UTF8_IGNORE just in case
    $aiJson = json_decode($cleanText, true, 512, JSON_INVALID_UTF8_IGNORE);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $logFile = __DIR__ . '/../logs/grammar_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] GRAMMAR JSON ERROR: " . json_last_error_msg() . "\n";
        $logEntry .= "RAW (Full): " . $rawText . "\n"; 
        $logEntry .= "CLEANED: " . $cleanText . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        throw new RuntimeException("Gemini output was not valid JSON (" . json_last_error_msg() . ").");
    }

    // 6. Output Validation
    if (!isset($aiJson['score']) || !array_key_exists('errors', $aiJson)) {
         throw new RuntimeException("Gemini output missing strict fields (score/errors).");
    }

    return [
        'score' => (int)$aiJson['score'],
        'cefr' => (string)($aiJson['cefr'] ?? 'B1'),
        'errors' => $aiJson['errors']
    ];
}
