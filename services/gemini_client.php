<?php
// services/gemini_client.php

require_once __DIR__ . '/../config/constants.php';

class GeminiClient
{
    private string $apiKey;
    
    // GÜNCELLENMİŞ MODEL LİSTESİ (API'de mevcut olanlar)
    private array $models = [
        'gemini-2.0-flash',       // V2.0 Flash (Hızlı ve Mevcut)
        'gemini-flash-latest',    // En son stabil sürüm
        'gemini-2.0-flash-lite'   // Çok hızlı alternatif
    ];

    public function __construct()
    {
        if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
            $envKey = getenv('GEMINI_API_KEY');
            if ($envKey) {
                $this->apiKey = $envKey;
            } else {
                throw new Exception('Gemini API Key bulunamadı.');
            }
        } else {
            $this->apiKey = GEMINI_API_KEY;
        }
    }

    public function generateResponse(string $prompt, bool $jsonFormat = false, ?array $fileData = null): string
    {
        $lastError = '';

        // Modelleri sırayla dene
        foreach ($this->models as $model) {
            try {
                return $this->makeRequest($model, $prompt, $jsonFormat, $fileData);
            } catch (Exception $e) {
                // Hata alırsak kaydet ve bir sonraki modele geç
                $lastError = $e->getMessage();
                continue;
            }
        }

        throw new Exception("Tüm Gemini modelleri denendi ancak başarısız oldu. Son hata: " . $lastError);
    }

    private function makeRequest(string $model, string $prompt, bool $jsonFormat, ?array $fileData = null): string
    {
        // V1beta yerine V1beta kullanmaya devam ediyoruz (Genelde uyumludur)
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->apiKey;

        $parts = [
            ["text" => $prompt]
        ];

        // Eğer dosya verisi varsa (Multimodal)
        if ($fileData && isset($fileData['mimeType']) && isset($fileData['data'])) {
            $parts[] = [
                "inlineData" => [
                    "mimeType" => $fileData['mimeType'],
                    "data" => $fileData['data'] // Base64 string
                ]
            ];
        }
        
        $payload = [
            "contents" => [["parts" => $parts]],
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 8192, // Increased from 1000 to prevent truncation
            ],
            // Disable Safety Filters to prevent random cuts in academic text
            "safetySettings" => [
                [ "category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_NONE" ],
                [ "category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_NONE" ],
                [ "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_NONE" ],
                [ "category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_NONE" ],
            ]
        ];

        if ($jsonFormat) {
            $payload['generationConfig']['responseMimeType'] = "application/json";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl Hatası: ' . curl_error($ch));
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorBody = json_decode($response, true);
            $msg = $errorBody['error']['message'] ?? 'Bilinmeyen Hata';
            throw new Exception("($httpCode) $msg"); // Modeli değiştirip tekrar denesin diye Exception fırlatıyoruz
        }

        $data = json_decode($response, true);
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
             throw new Exception("Model boş yanıt döndü.");
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
} 