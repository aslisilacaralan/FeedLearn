<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_login();

$user = current_user();
if (($user['role'] ?? '') !== ROLE_ADMIN) {
    // admin değilse dashboard'a
    redirect('/dashboard.php');
}
