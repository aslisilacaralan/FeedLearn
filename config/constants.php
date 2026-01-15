<?php
// config/constants.php
// Proje genel sabitleri

// ================================
// APP
// ================================
define('APP_NAME', 'FEEDLEARN');

// Base URL (htdocs altındaki klasör adı)
// http://localhost/feedlearn
define('BASE_URL', '/feedlearn');

// ================================
// ROLES
// ================================
define('ROLE_STUDENT', 'user');
define('ROLE_ADMIN', 'admin');

// ================================
// DEBUG
// ================================
$appDebugEnv = getenv('APP_DEBUG') ?: '';
define(
    'APP_DEBUG',
    $appDebugEnv === '1' || strtolower($appDebugEnv) === 'true'
);

// ================================
// OPENAI
// ================================
$openaiKey = getenv('OPENAI_API_KEY');

// Eğer environment yoksa manuel fallback (geliştirme için)
if (!$openaiKey) {
    // ❗ prod'da BURAYA KEY KOYMA
    $openaiKey = '';
}

define('GEMINI_API_KEY', 'AIzaSyA-RX03Em3OoWDjhupUHEQepd6R_OYW0uU'); 

// ================================
// HELPERS
// ================================
function redirect(string $path): void
{
    // /dashboard.php gibi gelirse düzgün birleştir
    if ($path && $path[0] !== '/') {
        $path = '/' . $path;
    }

    header('Location: ' . BASE_URL . $path);
    exit;
}
