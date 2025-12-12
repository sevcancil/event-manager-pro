<?php
// Oturum başlatılmamışsa başlat
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. URL'den dil seçimi geldi mi? (Örn: ?lang=en)
if (isset($_GET['lang'])) {
    // Sadece izin verilen diller
    if (in_array($_GET['lang'], ['tr', 'en'])) {
        $_SESSION['selected_lang'] = $_GET['lang'];
    }
}

// 2. Varsayılan dili belirle (Önce Session, yoksa TR)
$currentLang = $_SESSION['selected_lang'] ?? 'tr';

// 3. İlgili dil dosyasını yükle
// Dosya yolunu ana dizinden itibaren bulması için __DIR__ kullanıyoruz
$langFilePath = __DIR__ . "/../lang/$currentLang.php";

if (file_exists($langFilePath)) {
    $lang = require $langFilePath;
} else {
    // Dosya yoksa hata vermesin, Türkçe yüklemeye çalışsın
    $lang = require __DIR__ . "/../lang/tr.php";
}
?>