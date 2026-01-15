<?php
// services/openai_client.php
// Minimal OpenAI Responses API client with JSON Schema output.

require_once __DIR__ . '/../config/constants.php';

class OpenAIClientException extends RuntimeException {
    private $httpCode;
    private $curlError;
    private $responseBody;

    public function __construct($message, $httpCode = null, $curlError = null, $responseBody = null) {
        parent::__construct($message);
        $this->httpCode = $httpCode;
        $this->curlError = $curlError;
        $this->responseBody = $responseBody;
    }

    public function getHttpCode() {
        return $this->httpCode;
    }

    public function getCurlError() {
        return $this->curlError;
    }

    public function getResponseBody() {
        return $this->responseBody;
    }
}

function openai_responses_json_schema($instructions, $input, $schema) {
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (!is_string($apiKey) || trim($apiKey) === '') {
        throw new OpenAIClientException('Missing OpenAI API key.');
    }
    if (!is_array($schema)) {
        throw new InvalidArgumentException('Schema must be an array.');
    }

    $payload = [
        'model' => 'gpt-5.2',
        'input' => [
            [
                'role' => 'system',
                'content' => [
                    ['type' => 'input_text', 'text' => (string) $instructions]
                ]
            ],
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => (string) $input]
                ]
            ]
        ],
        'text' => [
            'format' => [
                'type' => 'json_schema',
                'name' => 'response',
                'schema' => $schema,
                'strict' => true
            ]
        ]
    ];

    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($body === false) {
        throw new OpenAIClientException('Failed to encode request JSON: ' . json_last_error_msg());
    }

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        throw new OpenAIClientException(
            'OpenAI request failed: ' . $error . ' (cURL ' . $errno . ')',
            null,
            $error,
            null
        );
    }

    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new OpenAIClientException(
            'Failed to decode response JSON: ' . json_last_error_msg(),
            $httpCode,
            null,
            $response
        );
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $message = 'OpenAI API error';
        if (is_array($decoded) && isset($decoded['error']['message'])) {
            $message .= ': ' . $decoded['error']['message'];
        }
        throw new OpenAIClientException(
            $message . ' (HTTP ' . $httpCode . ')',
            $httpCode,
            null,
            $response
        );
    }

    return $decoded;
}
?>
