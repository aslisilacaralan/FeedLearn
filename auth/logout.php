<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

logout_user();
redirect('/auth/login.php');
?>
