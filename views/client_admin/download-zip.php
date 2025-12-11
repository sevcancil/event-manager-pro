<?php
// views/client_admin/download-zip.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

// Güvenlik: Sadece Client Admin girebilir
$auth = new Auth();
$auth->requireRole('client_admin');

$db = new Database();
$userId = $_SESSION['user_id'];

// Etkinliği bul
$event = $db->fetch("SELECT * FROM events WHERE user_id = ?", [$userId]);
if (!$event) die("Etkinlik bulunamadı.");

// Onaylanmış fotoğrafları çek
$photos = $db->fetchAll("SELECT file_path FROM media_uploads WHERE event_id = ? AND is_approved = 1", [$event['id']]);

if (empty($photos)) {
    die("Henüz indirilecek onaylı fotoğraf yok. <a href='dashboard.php'>Geri Dön</a>");
}

// ZIP Dosyasının Adı ve Yolu (Geçici olarak sunucuda oluşturacağız)
$zipFileName = 'Arsiv_' . $event['slug'] . '_' . date('Ymd_His') . '.zip';
$zipFilePath = __DIR__ . '/../../public/uploads/' . $zipFileName; // Temp klasörü yerine uploads'a koyuyoruz ki erişim sorunu olmasın

// ZIP Nesnesini Başlat
$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("ZIP dosyası sunucuda oluşturulamadı. Yazma izni hatası olabilir.");
}

// Dosyaları ZIP'e Ekle
$fileCount = 0;
foreach ($photos as $photo) {
    // Veritabanındaki yol: uploads/slug/img_123.jpg
    // Fiziksel yol: __DIR__ / ../../public/ + uploads/slug/img_123.jpg
    $realPath = __DIR__ . '/../../public/' . $photo['file_path'];
    
    if (file_exists($realPath)) {
        // ZIP'in içine sadece dosya adıyla ekle (Klasör yapısını koruma)
        $fileNameInZip = basename($photo['file_path']);
        $zip->addFile($realPath, $fileNameInZip);
        $fileCount++;
    }
}

$zip->close();

// Eğer dosya oluştuysa indirme işlemini başlat
if (file_exists($zipFilePath) && $fileCount > 0) {
    
    // Header ayarları (İndirme penceresi açılması için)
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($zipFilePath));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Dosyayı oku ve kullanıcıya gönder
    readfile($zipFilePath);
    
    // İşlem bitince sunucudaki ZIP dosyasını sil (Yük olmasın)
    unlink($zipFilePath);
    exit;
} else {
    echo "Hata: ZIP dosyası boş veya oluşturulamadı. (Dosya sayısı: $fileCount)";
}
?>