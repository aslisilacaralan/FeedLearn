<?php
// index.php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/constants.php';

// Eğer giriş yaptıysa dashboard, değilse login
if (current_user()) {
    redirect('/dashboard.php');
} else {
    redirect('/auth/login.php');
}
?>
