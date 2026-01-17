<?php
// services/check_models.php
require_once __DIR__ . '/../config/constants.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Gemini Model Kontrolü</h1>";

// 1. Anahtar Kontrolü
if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
    die("<h3 style='color:red'>❌ HATA: GEMINI_API_KEY sabitlerde tanımlı değil!</h3>");
}

$apiKey = GEMINI_API_KEY;
// Anahtarın sadece ilk ve son karakterlerini göster (Güvenlik için)
$maske = substr($apiKey, 0, 5) . "..." . substr($apiKey, -5);
echo "<p><strong>Kullanılan Anahtar:</strong> $maske</p>";

// 2. Google'a Sor: "Bana hangi modelleri verirsin?"
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL hatalarını geçici olarak yoksay
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 3. Sonucu Yazdır
if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['models'])) {
        echo "<h2 style='color:green'>✅ Bağlantı Başarılı! İşte Senin Anahtarının İzin Verdiği Modeller:</h2>";
        echo "<ul>";
        $bulundu = false;
        foreach ($data['models'] as $model) {
            // Sadece metin üretme (generateContent) yeteneği olanları listele
            if (isset($model['supportedGenerationMethods']) && in_array("generateContent", $model['supportedGenerationMethods'])) {
                // model isminin başındaki 'models/' kısmını atalım temiz görünsün
                $cleanName = str_replace('models/', '', $model['name']);
                echo "<li><strong>" . $cleanName . "</strong> <span style='color:gray'>(" . $model['displayName'] . ")</span></li>";
                $bulundu = true;
            }
        }
        echo "</ul>";
        
        if (!$bulundu) {
            echo "<p style='color:orange'>Bağlantı başarılı ama 'generateContent' destekleyen model bulunamadı.</p>";
        } else {
            echo "<p>👉 <strong>ÇÖZÜM:</strong> Yukarıdaki listeden bir ismi kopyala ve <code>gemini_client.php</code> dosyasındaki model listesine ekle.</p>";
        }
    } else {
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
} else {
    echo "<h2 style='color:red'>❌ Kritik Hata (Kod: $httpCode)</h2>";
    echo "<p>Google'dan gelen cevap:</p>";
    echo "<div style='background:#eee; padding:10px; border:1px solid #ccc;'>";
    echo htmlspecialchars($response);
    echo "</div>";
    
    echo "<h3>Olası Sebepler:</h3>";
    echo "<ul>";
    echo "<li>API Anahtarın geçersiz.</li>";
    echo "<li>API Anahtarın 'Generative Language API' servisi için etkinleştirilmemiş.</li>";
    echo "<li>Google Cloud projesinde faturalandırma (Billing) ile ilgili bir sorun var.</li>";
    echo "</ul>";
}
?>