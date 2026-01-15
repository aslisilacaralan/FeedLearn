<?php
require_once __DIR__ . '/../auth/_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/openai_client.php';

header('Content-Type: application/json; charset=utf-8');

function api_response($ok, $data = null, $error = null, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'ok' => (bool)$ok,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

function api_error($message, $status) {
    api_response(false, null, $message, $status);
}

function api_error_with_debug($message, $status, $debug = null) {
    $payload = [
        'ok' => false,
        'data' => null,
        'error' => $message
    ];
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $payload['debug'] = $debug ?? [
            'http_code' => null,
            'curl_error' => null,
            'response_body' => null
        ];
    }
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$module = $_GET['module'] ?? '';
if ($module === '') {
    api_error('missing_module', 400);
}

if ($module === 'evaluate_writing') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_error('invalid_method', 400);
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('invalid_method', 400);
}

function api_get_payload(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = (string) file_get_contents('php://input');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return [];
    }
    return $_POST ?? [];
}

try {
    switch ($module) {
        case 'evaluate_writing': {
            require_login('json');

            $payload = api_get_payload();
            $promptText = trim((string)($payload['prompt_text'] ?? ''));
            $userText = trim((string)($payload['user_text'] ?? ''));

            $errors = [];
            $minChars = 50;
            $minWords = 10;

            if ($promptText === '') {
                $errors[] = 'Prompt is required.';
            }
            if ($userText === '') {
                $errors[] = 'Response is required.';
            }

            $textCompact = preg_replace('/\s+/', ' ', $userText);
            $charCount = strlen(str_replace(' ', '', (string) $textCompact));
            if ($charCount > 0 && $charCount < $minChars) {
                $errors[] = 'Response must be at least ' . $minChars . ' characters.';
            }

            $wordRaw = strtolower(preg_replace('/[^a-zA-Z\s]/', ' ', $userText));
            $words = preg_split('/\s+/', $wordRaw, -1, PREG_SPLIT_NO_EMPTY);
            $wordCount = is_array($words) ? count($words) : 0;
            if ($wordCount > 0 && $wordCount < $minWords) {
                $errors[] = 'Response must be at least ' . $minWords . ' words.';
            }

            if ($userText !== '') {
                $uniqueCount = $words ? count(array_unique($words)) : 0;
                if ($wordCount >= $minWords && $uniqueCount / max(1, $wordCount) < 0.25) {
                    $errors[] = 'Response appears overly repetitive.';
                }
                if ($uniqueCount > 0 && $uniqueCount < 3) {
                    $errors[] = 'Response appears too repetitive.';
                }

                $lettersOnly = preg_replace('/[^a-zA-Z]/', '', $userText);
                $lettersCount = strlen((string) $lettersOnly);
                $nonLettersCount = max(0, $charCount - $lettersCount);
                $nonLetterRatio = $charCount > 0 ? ($nonLettersCount / $charCount) : 1;
                if ($nonLetterRatio > 0.5) {
                    $errors[] = 'Response contains too many non-letter characters.';
                }
                if (preg_match('/(.)\\1{6,}/', $userText)) {
                    $errors[] = 'Response looks like gibberish.';
                }
            }

            if ($errors) {
                $feedback = 'Your response could not be evaluated: ' . implode(' ', $errors);
                $promptSummary = db_truncate_summary($promptText) ?? '';
                $responseSummary = db_truncate_summary($userText) ?? '';
                api_response(true, [
                    'evaluation_id' => null,
                    'score' => 10,
                    'cefr_level' => 'A1',
                    'weak_topics' => ['coherence', 'grammar'],
                    'feedback' => $feedback,
                    'prompt_summary' => $promptSummary,
                    'response_summary' => $responseSummary
                ], null, 200);
            }

            $schema = [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'score' => [
                        'type' => 'integer',
                        'minimum' => 0,
                        'maximum' => 100
                    ],
                    'cefr_level' => [
                        'type' => 'string',
                        'enum' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']
                    ],
                    'weak_topics' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'feedback' => [
                        'type' => 'string'
                    ],
                    'prompt_summary' => [
                        'type' => 'string'
                    ],
                    'response_summary' => [
                        'type' => 'string'
                    ]
                ],
                'required' => [
                    'score',
                    'cefr_level',
                    'weak_topics',
                    'feedback',
                    'prompt_summary',
                    'response_summary'
                ]
            ];

            $instructions = "You are an English writing evaluator. "
                . "Score the response (0-100), assign CEFR level, list 3-5 weak topics, "
                . "and provide concise feedback. Summarize the prompt and response in <=120 chars each.";

            $input = "PROMPT:\n" . $promptText . "\n\nRESPONSE:\n" . $userText;

            try {
                $response = openai_responses_json_schema($instructions, $input, $schema);
            } catch (Throwable $e) {
                $debug = [
                    'http_code' => null,
                    'curl_error' => null,
                    'response_body' => null
                ];
                if ($e instanceof OpenAIClientException) {
                    $debug['http_code'] = $e->getHttpCode();
                    $debug['curl_error'] = $e->getCurlError();
                    $debug['response_body'] = $e->getResponseBody();
                }
                api_error_with_debug($e->getMessage(), 500, $debug);
            }

            $structured = null;
            if (is_array($response)) {
                if (isset($response['output_text']) && is_string($response['output_text'])) {
                    $structured = json_decode($response['output_text'], true);
                }
                if (!$structured && isset($response['output']) && is_array($response['output'])) {
                    foreach ($response['output'] as $outputItem) {
                        $content = $outputItem['content'] ?? null;
                        if (!is_array($content)) {
                            continue;
                        }
                        foreach ($content as $part) {
                            if (!is_array($part)) {
                                continue;
                            }
                            if (($part['type'] ?? '') === 'output_json' && isset($part['json']) && is_array($part['json'])) {
                                $structured = $part['json'];
                                break 2;
                            }
                            if (($part['type'] ?? '') === 'output_text' && isset($part['text']) && is_string($part['text'])) {
                                $structured = json_decode($part['text'], true);
                                if ($structured) {
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            if (!is_array($structured)) {
                api_error_with_debug('OpenAI response missing structured output.', 500);
            }

            $score = (int)($structured['score'] ?? 0);
            if ($score < 0) $score = 0;
            if ($score > 100) $score = 100;

            $allowedCefr = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
            $cefrLevel = (string)($structured['cefr_level'] ?? 'A1');
            if (!in_array($cefrLevel, $allowedCefr, true)) {
                $cefrLevel = 'A1';
            }

            $weakTopics = [];
            if (isset($structured['weak_topics']) && is_array($structured['weak_topics'])) {
                foreach ($structured['weak_topics'] as $topic) {
                    if (is_string($topic) && trim($topic) !== '') {
                        $weakTopics[] = trim($topic);
                    }
                }
            }
            if (!$weakTopics) {
                $weakTopics = ['grammar', 'coherence'];
            }

            $feedback = trim((string)($structured['feedback'] ?? ''));
            if ($feedback === '') {
                $feedback = 'Your response was evaluated. Focus on clarity and grammar.';
            }
            $promptSummary = trim((string)($structured['prompt_summary'] ?? ''));
            $responseSummary = trim((string)($structured['response_summary'] ?? ''));
            if ($promptSummary === '') {
                $promptSummary = db_truncate_summary($promptText) ?? '';
            }
            if ($responseSummary === '') {
                $responseSummary = db_truncate_summary($userText) ?? '';
            }

            $activity = db_get_activity_by_type('writing');
            $activityId = (int)($activity['id'] ?? 0);
            if ($activityId <= 0) {
                api_error_with_debug('activity_not_found', 500);
            }

            $user = current_user();
            $evalId = db_create_evaluation(
                $user['id'],
                $activityId,
                $score,
                $cefrLevel,
                $feedback,
                $weakTopics,
                'text',
                $userText
            );

            if (!$evalId) {
                api_error_with_debug('evaluation_insert_failed', 500);
            }

            db_create_ai_log($user['id'], 'writing', $promptSummary, $responseSummary);

            api_response(true, [
                'evaluation_id' => $evalId,
                'score' => $score,
                'cefr_level' => $cefrLevel,
                'weak_topics' => $weakTopics,
                'feedback' => $feedback,
                'prompt_summary' => $promptSummary,
                'response_summary' => $responseSummary
            ], null, 200);
            break;
        }
        case 'activities': {
            require_login('json');
            $pdo = db_connect();
            $stmt = $pdo->prepare(
                'SELECT id, title, description, activity_type, is_enabled
                 FROM activities
                 ORDER BY id ASC'
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();
            api_response(true, $rows, null, 200);
            break;
        }
        case 'history': {
            require_login('json');
            $user = current_user();
            $pdo = db_connect();
            $stmt = $pdo->prepare(
                'SELECT e.id, e.score, e.cefr_level, e.weak_topics_json, e.created_at,
                        a.title, a.activity_type
                 FROM evaluations e
                 LEFT JOIN activities a ON a.id = e.activity_id
                 WHERE e.user_id = :user_id
                 ORDER BY datetime(e.created_at) DESC, e.id DESC'
            );
            $stmt->execute(['user_id' => (int)$user['id']]);
            $rows = $stmt->fetchAll();
            api_response(true, $rows, null, 200);
            break;
        }
        case 'weekly': {
            require_login('json');
            $user = current_user();
            $endAt = gmdate('Y-m-d H:i:s');
            $startAt = gmdate('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60);

            $pdo = db_connect();
            $stmt = $pdo->prepare(
                'SELECT score, cefr_level, weak_topics_json, created_at
                 FROM evaluations
                 WHERE user_id = :user_id
                   AND datetime(created_at) >= datetime(:start_at)
                   AND datetime(created_at) <= datetime(:end_at)
                 ORDER BY datetime(created_at) DESC, id DESC'
            );
            $stmt->execute([
                'user_id' => (int)$user['id'],
                'start_at' => $startAt,
                'end_at' => $endAt
            ]);
            $weekly = $stmt->fetchAll();

            $scores = array_map(fn($p) => (float)($p['score'] ?? 0), $weekly);
            $avgScore = count($scores) ? round(array_sum($scores) / count($scores), 2) : 0;

            $topicCount = [];
            foreach ($weekly as $p) {
                $topics = json_decode($p['weak_topics_json'] ?? '[]', true);
                if (!is_array($topics)) {
                    $topics = [];
                }
                foreach ($topics as $topic) {
                    if ($topic === '') {
                        continue;
                    }
                    $topicCount[$topic] = ($topicCount[$topic] ?? 0) + 1;
                }
            }
            arsort($topicCount);
            $topWeakTopics = array_slice(array_keys($topicCount), 0, 3);

            $cefrCounts = [];
            foreach ($weekly as $p) {
                $level = $p['cefr_level'] ?: 'Unknown';
                $cefrCounts[$level] = ($cefrCounts[$level] ?? 0) + 1;
            }
            $cefrOrder = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'Unknown'];
            $cefrDistribution = [];
            foreach ($cefrOrder as $level) {
                $count = $cefrCounts[$level] ?? 0;
                $cefrDistribution[] = ['level' => $level, 'count' => $count];
                unset($cefrCounts[$level]);
            }
            if ($cefrCounts) {
                foreach ($cefrCounts as $level => $count) {
                    $cefrDistribution[] = ['level' => $level, 'count' => $count];
                }
            }

            api_response(true, [
                'count' => count($weekly),
                'avg_score' => $avgScore,
                'cefr_distribution' => $cefrDistribution,
                'top_weak_topics' => $topWeakTopics
            ], null, 200);
            break;
        }
        case 'admin.users': {
            require_admin('json');
            $pdo = db_connect();
            $stmt = $pdo->prepare(
                'SELECT u.id, u.email, u.role, u.created_at,
                        COUNT(e.id) AS evaluation_count
                 FROM users u
                 LEFT JOIN evaluations e ON e.user_id = u.id
                 GROUP BY u.id
                 ORDER BY u.id ASC'
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();
            api_response(true, $rows, null, 200);
            break;
        }
        case 'admin.ai_logs': {
            require_admin('json');
            $pdo = db_connect();
            $stmt = $pdo->prepare(
                'SELECT id, user_id, module, prompt_summary, response_summary, created_at
                 FROM ai_logs
                 ORDER BY datetime(created_at) DESC, id DESC
                 LIMIT 100'
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();
            api_response(true, $rows, null, 200);
            break;
        }
        default:
            api_error('unknown_module', 400);
    }
} catch (Throwable $e) {
    api_error('server_error', 500);
}
