<?php
// config/constants.php
// Proje genel sabitleri

define('APP_NAME', 'FEEDLEARN');
define('BASE_URL', '/feedlearn'); // XAMPP olmasa bile yapısal olarak dursun

// Basit rol tanımı
define('ROLE_STUDENT', 'student');
define('ROLE_ADMIN', 'admin');

// Basit yönlendirme yardımcıları
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}
?>
