<?php
session_start();
require_once __DIR__ . '/../src/Database.php';

// 1. URL Analizi
// ?ajax=1 gibi parametreleri temizleyerek saf yolu alıyoruz
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];

// Klasör yolunu temizle, sadece istediğimiz kısmı al
$basePath = str_replace('/index.php', '', $scriptName);
$path = str_replace($basePath, '', $requestUri);
$path = trim($path, '/'); 

$segments = explode('/', $path);
$firstSegment = $segments[0] ?? ''; 
$action = $segments[1] ?? 'home';   

// 2. Yönlendirme Mantığı

// A) Ana Sayfa (Siteye direkt girildiyse)
if ($firstSegment == '' || $firstSegment == 'index.php') {
    // Kurumsal Ana Sayfayı Göster
    require_once __DIR__ . '/../views/home.php';
    exit;

// B) Admin Kısayolu
} elseif ($firstSegment == 'admin') {
    header("Location: ../views/super_admin/dashboard.php");
    exit;

// C) Etkinlik Sayfaları
} else {
    // Veritabanında bu "slug"a sahip bir etkinlik var mı?
    $db = new Database();
    $event = $db->fetch("SELECT * FROM events WHERE slug = ? AND status != 'draft'", [$firstSegment]);

    if ($event) {
        // Etkinlik bulundu! Hangi alt sayfayı istiyor?
        
        if ($action == 'kayit') {
            require_once __DIR__ . '/../views/frontend/register.php';
            
        } elseif ($action == 'paylas') {
            require_once __DIR__ . '/../views/frontend/upload.php';

        } elseif ($action == 'anket') {
            // Misafir Oylama Ekranı
            require_once __DIR__ . '/../views/frontend/vote.php';
            
        } elseif ($action == 'anket-sonuc') {
            // Dev Ekran Grafik Sonucu
            require_once __DIR__ . '/../views/frontend/poll-results.php';

        } elseif ($action == 'program') {
            // Etkinlik Takvimi
            require_once __DIR__ . '/../views/frontend/timeline.php';

        } elseif ($action == 'biletim') {
            // Dijital Bilet Görüntüleme
            require_once __DIR__ . '/../views/frontend/ticket.php';
            
        } elseif ($action == 'canli') {
            // Mobil Uyumlu Canlı Akış Sayfası
            require_once __DIR__ . '/../views/frontend/stream.php';
            
        } elseif ($action == 'akis') {
            // Canlı Duvar (Dosya adı wall.php ama URL'de 'akis' yazıyor)
            require_once __DIR__ . '/../views/frontend/wall.php';
            
        } else {
            // Varsayılan: Karşılama Sayfası
            require_once __DIR__ . '/../views/frontend/landing.php';
        }
        
    } else {
        // 404 - Bulunamadı
        http_response_code(404);
        echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>";
        echo "<h1>404</h1>";
        echo "<h3>Aradığınız etkinlik bulunamadı veya yayından kaldırıldı.</h3>";
        echo "<a href='$basePath'>Ana Sayfaya Dön</a>";
        echo "</div>";
    }
}
?>