<?php
// views/client_admin/print-badge.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();

// 1. Güvenlik ve Veri Çekme
if (!isset($_GET['id']) || !isset($_SESSION['current_event_id'])) {
    die("Geçersiz istek.");
}

$guestId = $_GET['id'];
$eventId = $_SESSION['current_event_id'];
$userId  = $_SESSION['user_id'];

// Etkinlik sahibinin doğru olup olmadığını ve misafirin bu etkinliğe ait olup olmadığını kontrol et
$event = $db->fetch("SELECT * FROM events WHERE id = ? AND user_id = ?", [$eventId, $userId]);
if (!$event) die("Yetkisiz erişim.");

$guest = $db->fetch("SELECT * FROM guests WHERE id = ? AND event_id = ?", [$guestId, $eventId]);
if (!$guest) die("Misafir bulunamadı.");

// Ayarlardan renk çekme (Opsiyonel, yoksa varsayılan siyah)
$settings = json_decode($event['settings_json'], true) ?? [];
$themeColor = $settings['primary_color'] ?? '#000000';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yaka Kartı - <?= htmlspecialchars($guest['full_name']) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* Yazdırma Ayarları: 10cm x 14cm */
        @page {
            size: 100mm 140mm;
            margin: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: white;
            width: 100mm;
            height: 140mm;
            overflow: hidden; /* Taşmayı engelle */
            display: flex;
            flex-direction: column;
            text-align: center;
            border: 1px solid #ddd; /* Ekranda sınırları görmek için, yazıcıda görünmeyebilir */
        }

        /* Tasarım */
        .header {
            background-color: <?= $themeColor ?>;
            color: white;
            padding: 20px 10px;
            height: 15%; /* Üst %15 */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header h2 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .name {
            font-size: 24px;
            font-weight: 700;
            color: #000;
            margin-bottom: 5px;
            line-height: 1.2;
        }

        .title {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
            font-weight: 400;
        }
        
        .company {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        #qrcode {
            margin: 10px auto;
        }

        .footer {
            height: 10%;
            border-top: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #777;
        }

        /* Yazdırma anında kenar çizgilerini kaldır ve arkaplan grafiklerini zorla */
        @media print {
            body { border: none; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()"> <div class="header">
        <h2><?= htmlspecialchars($event['title']) ?></h2>
    </div>

    <div class="content">
        <div class="name"><?= htmlspecialchars($guest['full_name']) ?></div>
        
        <?php if(!empty($guest['title'])): ?>
            <div class="title"><?= htmlspecialchars($guest['title']) ?></div>
        <?php endif; ?>

        <?php if(!empty($guest['company'])): ?>
            <div class="company"><?= htmlspecialchars($guest['company']) ?></div>
        <?php endif; ?>

        <div id="qrcode"></div>
        <div style="font-size: 10px; letter-spacing: 2px; margin-top: 5px;"><?= $guest['qr_code'] ?></div>
    </div>

    <div class="footer">
        <?= strtoupper($guest['qr_code']) ?> &bull; MİSAFİR
    </div>

    <script>
        // QR Kodu oluştur
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= $guest['qr_code'] ?>",
            width: 120,
            height: 120,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>