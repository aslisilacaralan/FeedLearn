<?php
// index.php
require_once __DIR__ . '/auth/_guard.php';

// Eğer giriş yaptıysa dashboard, değilse login
if (current_user()) {
    redirect('/dashboard.php');
} else {
    redirect('/auth/login.php');
}
?>
