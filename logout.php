<?php
// logout.php
require_once 'src/Auth.php';

$auth = new Auth();
$auth->logout();

// Giriş sayfasına geri gönder
header("Location: views/auth/login.php");
exit;
?>