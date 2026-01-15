<?php

function evaluate_writing_with_gemini(string $prompt, string $text): array
{
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === '') {
        return [
            'score' => 60,
            'cefr' => 'B1',
            'weak_topics' => ['configuration'],
            'feedback' => 'Gemini API key is missing.'
        ];
    }

    $aiPrompt = <<<PROMPT
You are an English writing examiner.

Return ONLY raw JSON. No markdown. No explanation.

JSON format:
{
  "score": number between 40 and 95,
  "cefr": "A1|A2|B1|B2|C1",
  "weak_topics": [string],
  "feedback": string
}

WRITING PROMPT:
"$prompt"

STUDENT RESPONSE:
"$text"
PROMPT;

    $payload = [
        "contents" => [[
            "parts" => [[ "text" => $aiPrompt ]]
        ]]
    ];

    $ch = curl_init(
        "https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY
    );

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);
    $textOut = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

    $ai = json_decode($textOut, true);

    if (!is_array($ai)) {
        return [
            'score' => 65,
            'cefr' => 'B1',
            'weak_topics' => ['coherence'],
            'feedback' => 'AI response could not be parsed. Fallback used.'
        ];
    }

    return [
        'score' => (int)($ai['score'] ?? 65),
        'cefr' => (string)($ai['cefr'] ?? 'B1'),
        'weak_topics' => is_array($ai['weak_topics'] ?? null) ? $ai['weak_topics'] : ['grammar'],
        'feedback' => (string)($ai['feedback'] ?? 'AI feedback.')
    ];
}