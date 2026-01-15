<?php
// config/constants.php
// Proje genel sabitleri

require_once __DIR__ . '/config.php';

define('APP_NAME', 'FEEDLEARN');

// Basit rol tanımı
define('ROLE_STUDENT', 'user');
define('ROLE_ADMIN', 'admin');

// OpenAI API key (load from environment or set manually).
$openaiKeyEnv = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? '';
define('OPENAI_API_KEY', is_string($openaiKeyEnv) ? $openaiKeyEnv : '');

// Debug flag (set APP_DEBUG=1 or true in environment).
$appDebugEnv = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?? '';
define('APP_DEBUG', $appDebugEnv === '1' || strtolower($appDebugEnv) === 'true');

// Basit yönlendirme yardımcıları
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}
?>
