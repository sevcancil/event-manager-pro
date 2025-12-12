<?php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();

if (!isset($_GET['id'])) die("Oturum ID eksik.");
$sessionId = $_GET['id'];

// Oturum Bilgisini Çek
$session = $db->fetch("SELECT * FROM sessions WHERE id = ?", [$sessionId]);
if (!$session) die("Oturum bulunamadı.");

// --- AJAX İŞLEMİ (QR OKUNDUĞUNDA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_code'])) {
    header('Content-Type: application/json');
    $code = trim($_POST['qr_code']);
    
    // 1. Misafiri Bul
    $guest = $db->fetch("SELECT * FROM guests WHERE qr_code = ?", [$code]);
    
    if ($guest) {
        // 2. Misafir daha önce BU OTURUMA girmiş mi?
        $check = $db->fetch("SELECT id FROM session_logs WHERE session_id = ? AND guest_id = ?", [$sessionId, $guest['id']]);
        
        $response = [
            'name' => $guest['full_name'],
            'company' => $guest['company'],
            'title' => $guest['title']
        ];

        if ($check) {
            echo json_encode(array_merge($response, ['status' => 'warning', 'message' => 'Bu kişi ZATEN BU OTURUMA girdi.']));
        } else {
            // 3. Girişi Kaydet (Session Log)
            $db->query("INSERT INTO session_logs (session_id, guest_id, scanned_at) VALUES (?, ?, NOW())", [$sessionId, $guest['id']]);
            
            // Toplam sayıyı da döndürelim
            $count = $db->fetch("SELECT COUNT(*) as c FROM session_logs WHERE session_id = ?", [$sessionId]);
            
            echo json_encode(array_merge($response, [
                'status' => 'success', 
                'message' => 'Oturum Girişi Onaylandı!',
                'total_count' => $count['c']
            ]));
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz QR Kod!']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Oturum Girişi: <?= htmlspecialchars($session['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { background-color: #2c3e50; color: white; font-family: 'Segoe UI', sans-serif; }
        #reader { width: 100%; max-width: 500px; margin: 0 auto; border: 5px solid rgba(255,255,255,0.1); border-radius: 10px; }
        .result-card { display: none; margin-top: 20px; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .bg-success-custom { background-color: #27ae60; }
        .bg-warning-custom { background-color: #f39c12; color: #2c3e50; }
        .bg-danger-custom { background-color: #c0392b; }
    </style>
</head>
<body>

    <div class="container py-5 text-center">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="sessions.php" class="btn btn-outline-light btn-sm">← Oturumlar</a>
            <div>
                <small class="text-white-50 d-block">Şu an Taranıyor:</small>
                <h4 class="m-0 fw-bold text-warning"><?= htmlspecialchars($session['title']) ?></h4>
            </div>
            <div class="badge bg-primary fs-6">
                <span id="liveCount">0</span> Kişi
            </div>
        </div>

        <div id="reader"></div>

        <div class="mt-4 input-group justify-content-center" style="max-width: 400px; margin: 0 auto;">
            <input type="text" id="manualCode" class="form-control" placeholder="Manuel Kod Gir">
            <button class="btn btn-primary" onclick="manualCheckIn()">Giriş Yap</button>
        </div>

        <div id="resultArea" class="result-card">
            <h1 id="iconStatus" class="display-3 mb-2"></h1>
            <h2 id="guestName" class="fw-bold m-0"></h2>
            <h5 id="guestInfo" class="mt-2 mb-3 opacity-75 fw-normal"></h5>
            <div class="badge bg-white bg-opacity-25 px-3 py-2 rounded-pill mt-2">
                <span id="statusMsg" class="lead fs-6"></span>
            </div>
        </div>
    </div>

    <script>
        let isProcessing = false;

        function onScanSuccess(decodedText) {
            if (isProcessing) return;
            sendCheckIn(decodedText);
        }

        let html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
        html5QrcodeScanner.render(onScanSuccess, () => {});

        function sendCheckIn(code) {
            isProcessing = true;
            const formData = new FormData();
            formData.append('qr_code', code);

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                showResult(data);
                if(data.total_count) document.getElementById('liveCount').innerText = data.total_count;
                setTimeout(() => { isProcessing = false; }, 2000);
            });
        }

        function manualCheckIn() {
            sendCheckIn(document.getElementById('manualCode').value);
        }

        function showResult(data) {
            const area = document.getElementById('resultArea');
            const icon = document.getElementById('iconStatus');
            const name = document.getElementById('guestName');
            const info = document.getElementById('guestInfo');
            const msg = document.getElementById('statusMsg');

            area.style.display = 'block';
            area.classList.remove('bg-success-custom', 'bg-warning-custom', 'bg-danger-custom');
            name.innerText = data.name || '';
            
            // Unvan Bilgisi
            let details = [];
            if(data.title) details.push(data.title);
            if(data.company) details.push(data.company);
            info.innerText = details.join(' - ');

            if (data.status === 'success') {
                area.classList.add('bg-success-custom');
                icon.innerHTML = '✅';
            } else if (data.status === 'warning') {
                area.classList.add('bg-warning-custom');
                icon.innerHTML = '⚠️';
            } else {
                area.classList.add('bg-danger-custom');
                icon.innerHTML = '⛔';
                name.innerText = 'Hata';
            }
            msg.innerText = data.message;
            
            setTimeout(() => { area.style.display = 'none'; }, 3000);
        }
    </script>
</body>
</html>