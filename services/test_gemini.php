<?php
// test_gemini.php

// Hataları ekrana bastır ki sorunu görelim
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Gemini API Testi</h1>";

try {
    // 1. Yazdığımız sınıfı dahil et
    // Dosya zaten services klasöründe olduğu için doğrudan çağırıyoruz:
    require_once __DIR__ . '/gemini_client.php';
    echo "✅ GeminiClient dosyası bulundu.<br>";

    // 2. Sınıfı başlat
    $client = new GeminiClient();
    echo "✅ İstemci başlatıldı (API Key mevcut).<br>";

    // 3. Basit bir soru sor
    echo "⏳ Gemini'ye bağlanılıyor...<br>";
    
    $prompt = "Merhaba, bana PHP hakkında kısa, tek cümlelik ilginç bir bilgi ver.";
    $cevap = $client->generateResponse($prompt);

    // 4. Sonucu yazdır
    echo "<hr>";
    echo "<h3>🤖 Gemini'den Gelen Cevap:</h3>";
    echo "<p style='font-size: 18px; color: green;'>" . htmlspecialchars($cevap) . "</p>";

} catch (Exception $e) {
    echo "<hr>";
    echo "<h3>❌ BİR HATA OLUŞTU:</h3>";
    echo "<p style='color: red; font-weight: bold;'>" . $e->getMessage() . "</p>";
}
?>