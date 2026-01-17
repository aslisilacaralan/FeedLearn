<?php
// services/evaluator.php

require_once __DIR__ . '/gemini_client.php';

function evaluate_writing(string $userText): array
{
    $client = new GeminiClient();

    // Prompt: Yapay zekayı çok sıkı uyarıyoruz
    $prompt = "
    Sen bir İngilizce öğretmenisin. Öğrenci yazısı:
    \"$userText\"

    Görevin:
    1. Puan ver (0-100)
    2. Seviye (A1-C2)
    3. Eksik 2 konu
    4. Türkçe geri bildirim

    KURALLAR:
    - SADECE SAF JSON FORMATI VER.
    - Markdown (```json) KULLANMA.
    - JSON içinde çift tırnak (\") yerine tek tırnak (') kullan ki format bozulmasın.
    
    İSTENEN FORMAT:
    {
        \"score_percent\": 80,
        \"cefr\": \"B1\",
        \"weak_topics\": [\"Grammar\", \"Vocabulary\"],
        \"feedback\": \"Guzel bir yazi ancak bazi kelime hatalari var.\"
    }
    ";

    try {
        $rawResponse = $client->generateResponse($prompt, true);
        
        // --- 1. Temizlik: Markdown bloklarını sil ---
        $clean = preg_replace('/```json|```/', '', $rawResponse);
        
        // --- 2. Cımbızlama: İlk { ile son } arasını al ---
        if (preg_match('/\{[\s\S]*\}/', $clean, $matches)) {
            $clean = $matches[0];
        }

        // --- 3. Görünmez Karakter Temizliği (BOM, Null vb.) ---
        // Sadece görünür ASCII karakterleri ve Türkçe karakterleri tut
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);

        // --- 4. JSON Çözümleme ---
        $result = json_decode($clean, true);

        // Hata varsa manuel düzeltme dene (Çift tırnak hatası vb.)
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Basit bir onarım denemesi: Tırnakları temizle
            $clean = str_replace(["\r", "\n"], " ", $clean); 
            $result = json_decode($clean, true);
        }

        // Hala bozuksa varsayılan değer dön (Site çökmesin!)
        if (!$result) {
            error_log("JSON Parse Hatası. Gelen: " . $rawResponse);
            // Fallback (Yedek) Cevap
            return [
                'score_percent' => 50,
                'cefr' => 'B1',
                'weak_topics' => ['Genel Değerlendirme'],
                'feedback' => 'Yazınız alındı. Yapay zeka yanıtı teknik bir sebeple tam işlenemedi ancak yazınız sisteme kaydedildi.' . 
                              ' (Ham Cevap: ' . substr(strip_tags($rawResponse), 0, 50) . '...)'
            ];
        }

        return [
            'score_percent' => $result['score_percent'] ?? 0,
            'cefr' => $result['cefr'] ?? 'A1',
            'weak_topics' => $result['weak_topics'] ?? [],
            'feedback' => $result['feedback'] ?? 'Geri bildirim oluşturuldu.'
        ];

    } catch (Exception $e) {
        return [
            'score_percent' => 0,
            'cefr' => 'N/A',
            'weak_topics' => ['Sistem'],
            'feedback' => 'Hata: ' . $e->getMessage()
        ];
    }
}