<?php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();

// AJAX İşlemi: QR Kod Geldiğinde Veritabanını Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_code'])) {
    header('Content-Type: application/json');
    $code = trim($_POST['qr_code']);
    
    // Bu kod veritabanında var mı?
    $guest = $db->fetch("SELECT * FROM guests WHERE qr_code = ?", [$code]);
    
    if ($guest) {
        if ($guest['check_in_status'] == 1) {
            echo json_encode(['status' => 'warning', 'message' => 'Bu misafir zaten giriş yapmış!', 'name' => $guest['full_name']]);
        } else {
            // Durumu güncelle (GELDİ)
            // Hem durumu 1 yap, hem de saati şu an (NOW) olarak ayarla
            $db->query("UPDATE guests SET check_in_status = 1, check_in_at = NOW() WHERE id = ?", [$guest['id']]);
            echo json_encode(['status' => 'success', 'message' => 'Giriş Başarılı!', 'name' => $guest['full_name']]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz Bilet Kodu!']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kapı Girişi / Check-in</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { background-color: #2c3e50; color: white; }
        #reader { width: 100%; max-width: 500px; margin: 0 auto; border-radius: 10px; overflow: hidden; }
        .result-card { display: none; margin-top: 20px; padding: 20px; border-radius: 10px; text-align: center; }
        .bg-success-custom { background-color: #27ae60; }
        .bg-warning-custom { background-color: #f39c12; color: #333; }
        .bg-danger-custom { background-color: #c0392b; }
    </style>
</head>
<body>

    <div class="container py-5 text-center">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Panele Dön</a>
            <h3 class="m-0">Kapı Kontrol 🕵️‍♂️</h3>
            <div style="width: 80px;"></div>
        </div>

        <div id="reader"></div>

        <div class="mt-4">
            <p class="text-white-50 small">Kamera açılmazsa kodu elle girebilirsiniz:</p>
            <div class="input-group mb-3 justify-content-center" style="max-width: 400px; margin: 0 auto;">
                <input type="text" id="manualCode" class="form-control" placeholder="Bilet Kodu">
                <button class="btn btn-primary" onclick="manualCheckIn()">Kontrol Et</button>
            </div>
        </div>

        <div id="resultArea" class="result-card">
            <h1 id="iconStatus" class="display-1 mb-2"></h1>
            <h2 id="guestName" class="fw-bold"></h2>
            <p id="statusMsg" class="lead"></p>
        </div>
    </div>

    <script>
        // QR Okunduğunda Çalışacak Fonksiyon
        function onScanSuccess(decodedText, decodedResult) {
            // Aynı kodu peş peşe okumasın diye kısa bir duraklama mantığı eklenebilir
            sendCheckIn(decodedText);
        }

        function onScanFailure(error) {
            // Hataları sessizce geçiştir (Sürekli log basmasın)
        }

        // Kamerayı Başlat
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", { fps: 10, qrbox: {width: 250, height: 250} }, 
            /* verbose= */ false);
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);

        // Sunucuya Gönder
        function sendCheckIn(code) {
            const formData = new FormData();
            formData.append('qr_code', code);

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                showResult(data);
            })
            .catch(error => console.error('Hata:', error));
        }

        // Manuel Giriş İçin
        function manualCheckIn() {
            const code = document.getElementById('manualCode').value;
            if(code) sendCheckIn(code);
        }

        // Sonucu Ekrana Bas
        function showResult(data) {
            const area = document.getElementById('resultArea');
            const icon = document.getElementById('iconStatus');
            const name = document.getElementById('guestName');
            const msg = document.getElementById('statusMsg');

            area.style.display = 'block';
            area.className = 'result-card animate__animated animate__fadeInUp'; // Reset class

            if (data.status === 'success') {
                area.classList.add('bg-success-custom');
                icon.innerHTML = '✅';
                name.innerText = data.name;
                
                // Başarılı sesi çal (Opsiyonel)
                // new Audio('../../public/assets/success.mp3').play();
                
            } else if (data.status === 'warning') {
                area.classList.add('bg-warning-custom');
                icon.innerHTML = '⚠️';
                name.innerText = data.name;
            } else {
                area.classList.add('bg-danger-custom');
                icon.innerHTML = '⛔';
                name.innerText = 'Bilinmiyor';
            }
            msg.innerText = data.message;

            // 3 Saniye sonra sonucu temizle
            setTimeout(() => {
                area.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>