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
        // Cevap verilerini hazırla (Şirket ve Unvan eklendi)
        $responseData = [
            'name' => $guest['full_name'],
            'company' => $guest['company'], // YENİ
            'title' => $guest['title']      // YENİ
        ];

        if ($guest['check_in_status'] == 1) {
            echo json_encode(array_merge($responseData, [
                'status' => 'warning', 
                'message' => 'Bu misafir zaten giriş yapmış!'
            ]));
        } else {
            // Durumu güncelle (GELDİ)
            $db->query("UPDATE guests SET check_in_status = 1, check_in_at = NOW() WHERE id = ?", [$guest['id']]);
            
            echo json_encode(array_merge($responseData, [
                'status' => 'success', 
                'message' => 'Giriş Başarılı!'
            ]));
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
        body { background-color: #2c3e50; color: white; font-family: 'Segoe UI', sans-serif; }
        #reader { width: 100%; max-width: 500px; margin: 0 auto; border-radius: 10px; overflow: hidden; border: 5px solid rgba(255,255,255,0.1); }
        .result-card { display: none; margin-top: 20px; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        
        /* Renkler */
        .bg-success-custom { background-color: #27ae60; }
        .bg-warning-custom { background-color: #f39c12; color: #2c3e50; } /* Yazı koyu olsun ki okunsun */
        .bg-danger-custom { background-color: #c0392b; }

        /* Animasyon */
        @keyframes popIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .pop-in { animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    </style>
</head>
<body>

    <div class="container py-5 text-center">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Panele Dön</a>
            <h3 class="m-0 fw-bold">Kapı Kontrol 🕵️‍♂️</h3>
            <div style="width: 80px;"></div>
        </div>

        <div id="reader"></div>

        <div class="mt-4">
            <p class="text-white-50 small mb-2">Kamera açılmazsa kodu elle girebilirsiniz:</p>
            <div class="input-group mb-3 justify-content-center shadow-sm" style="max-width: 400px; margin: 0 auto;">
                <input type="text" id="manualCode" class="form-control" placeholder="Bilet Kodu (Örn: A1B2C3)">
                <button class="btn btn-primary fw-bold" onclick="manualCheckIn()">Kontrol Et</button>
            </div>
        </div>

        <div id="resultArea" class="result-card pop-in">
            <h1 id="iconStatus" class="display-1 mb-2"></h1>
            
            <h2 id="guestName" class="fw-bold m-0"></h2>
            
            <h5 id="guestInfo" class="mt-2 mb-3 opacity-75 fw-normal"></h5>
            
            <div class="badge bg-white bg-opacity-25 px-3 py-2 rounded-pill mt-2">
                <span id="statusMsg" class="lead fs-6"></span>
            </div>
        </div>
    </div>

    <script>
        let isProcessing = false; // Peş peşe okumayı engellemek için

        function onScanSuccess(decodedText, decodedResult) {
            if (isProcessing) return;
            sendCheckIn(decodedText);
        }

        function onScanFailure(error) {
            // Hataları sessizce geçiştir
        }

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);

        function sendCheckIn(code) {
            isProcessing = true; // İşlem başladı, kilitle
            
            const formData = new FormData();
            formData.append('qr_code', code);

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                showResult(data);
                // 2.5 saniye sonra yeni okumaya izin ver
                setTimeout(() => { isProcessing = false; }, 2500);
            })
            .catch(error => {
                console.error('Hata:', error);
                isProcessing = false;
            });
        }

        function manualCheckIn() {
            const code = document.getElementById('manualCode').value;
            if(code) sendCheckIn(code);
        }

        function showResult(data) {
            const area = document.getElementById('resultArea');
            const icon = document.getElementById('iconStatus');
            const name = document.getElementById('guestName');
            const info = document.getElementById('guestInfo'); // YENİ
            const msg = document.getElementById('statusMsg');

            area.style.display = 'block';
            
            // Sınıfları Temizle
            area.classList.remove('bg-success-custom', 'bg-warning-custom', 'bg-danger-custom');

            // Bilgileri Temizle
            name.innerText = '';
            info.innerText = '';

            if (data.status === 'success') {
                area.classList.add('bg-success-custom');
                icon.innerHTML = '✅';
                name.innerText = data.name;
                
                // Şirket ve Unvanı Birleştir
                let detailText = [];
                if(data.title) detailText.push(data.title);
                if(data.company) detailText.push(data.company);
                info.innerText = detailText.join(' - '); // "Müdür - Teknosa" gibi görünür
                
                // Başarı Sesi (Tarayıcı izin verirse)
                // playAudio('success'); 

            } else if (data.status === 'warning') {
                area.classList.add('bg-warning-custom');
                icon.innerHTML = '⚠️';
                name.innerText = data.name;
                
                let detailText = [];
                if(data.title) detailText.push(data.title);
                if(data.company) detailText.push(data.company);
                info.innerText = detailText.join(' - ');

            } else {
                area.classList.add('bg-danger-custom');
                icon.innerHTML = '⛔';
                name.innerText = 'Bilinmeyen Bilet';
                info.innerText = 'Lütfen kodu kontrol edin.';
            }
            
            msg.innerText = data.message;

            // 3.5 Saniye sonra sonucu gizle
            setTimeout(() => {
                area.style.display = 'none';
                document.getElementById('manualCode').value = ''; // Inputu temizle
            }, 3500);
        }
    </script>
</body>
</html>