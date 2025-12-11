<?php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];

$event = $db->fetch("SELECT * FROM events WHERE user_id = ?", [$userId]);

// ==========================================================================
// ÇEKİLİŞ KURALLARI (İstediğin kuralın başındaki // işaretlerini kaldır)
// ==========================================================================

// 1. SENARYO: Sadece "Check-in" yapanlar (Kapıdan girenler) katılsın.
/*
$sql = "SELECT full_name, email FROM guests 
        WHERE event_id = ? AND check_in_status = 1";
*/

// 2. SENARYO: Fotoğraf yükleyen herkes (Onay Şart Değil / Tek Hak).
/*
$sql = "SELECT DISTINCT g.full_name, g.email FROM guests g
        JOIN media_uploads m ON g.id = m.guest_id
        WHERE g.event_id = ?";
*/

// 3. SENARYO: Yüklediği fotoğraf sayısı kadar hakkı olsun (Onaylı Olmalı).
// "Ahmet 10 onaylı foto attıysa kazanma şansı 10 kattır."
/*
$sql = "SELECT g.full_name, g.email FROM guests g
        JOIN media_uploads m ON g.id = m.guest_id
        WHERE g.event_id = ? AND m.is_approved = 1";
*/

// 4. SENARYO: Fotoğraf yükleyenler (Onaylı Olmalı / Tek Hak). [VARSAYILAN]
// "Ahmet 100 foto da atsa 1 hakkı vardır ama fotosu onaylanmış olmalıdır."
/*
$sql = "SELECT DISTINCT g.full_name, g.email FROM guests g
        JOIN media_uploads m ON g.id = m.guest_id
        WHERE g.event_id = ? AND m.is_approved = 1";
*/

// 5. SENARYO: Yüklediği sayı kadar hakkı olsun (Onay Şart Değil).
// "Ne kadar çok yüklerse (çöp bile olsa) o kadar şans."

$sql = "SELECT g.full_name, g.email FROM guests g
        JOIN media_uploads m ON g.id = m.guest_id
        WHERE g.event_id = ?";


// ==========================================================================
// VERİLERİ HAZIRLAMA (Anti-Şüphe Modu)
// ==========================================================================

if (isset($sql)) {
    // 1. Gerçek Havuz (Matematiksel Şans): Seçilen senaryoya göre veriyi çeker.
    // Eğer Senaryo 3 seçiliyse, burada Ahmet 10 defa yer alır.
    $participants = $db->fetchAll($sql, [$event['id']]);
} else {
    $participants = [];
}

// 2. Görsel Havuz (Animasyon): İsimleri teke düşürürüz.
// Senaryo 3 bile seçilse, Ahmet ekranda dönerken sadece 1 kere görünür.
$visualMap = [];
foreach ($participants as $p) {
    $visualMap[$p['full_name']] = $p['full_name'];
}
$visualList = array_values($visualMap); // Indexleri düzelt

// Javascript'e aktar
$jsonAllParticipants = json_encode($participants); // Kazanma şansı için
$jsonVisualNames = json_encode($visualList);       // Ekranda dönmesi için
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Çekiliş Ekranı - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        body { 
            background: radial-gradient(circle, #4b134f 0%, #110313 100%); 
            color: white; 
            height: 100vh; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden;
        }
        .raffle-box {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 20px;
            border: 2px solid rgba(255,255,255,0.2);
            text-align: center;
            min-width: 500px;
            box-shadow: 0 0 50px rgba(255,215,0, 0.2);
        }
        #winnerName { font-size: 4rem; font-weight: bold; color: #ffd700; text-shadow: 0 0 20px rgba(255,215,0,0.5); }
        .participant-count { color: #aaa; margin-bottom: 20px; }
        .btn-spin { font-size: 1.5rem; padding: 15px 40px; border-radius: 50px; transition: 0.3s; }
        .btn-spin:hover { transform: scale(1.1); box-shadow: 0 0 30px rgba(255,255,255,0.5); }
        .rule-info { font-size: 0.8rem; color: rgba(255,255,255,0.3); margin-top: 15px; }
    </style>
</head>
<body>

    <div class="position-absolute top-0 start-0 p-4">
        <a href="dashboard.php" class="text-white-50 text-decoration-none">← Panele Dön</a>
    </div>

    <div class="raffle-box">
        <h2 class="mb-4">🎉 Büyük Çekiliş 🎉</h2>
        
        <div class="participant-count">
            <i class="fa-solid fa-users"></i> Toplam Çekiliş Hakkı: <b><?= count($participants) ?></b>
        </div>

        <div id="displayArea" style="height: 150px; display: flex; align-items: center; justify-content: center;">
            <div id="winnerName">???</div>
        </div>

        <button id="startBtn" class="btn btn-light btn-spin mt-4 fw-bold text-dark" onclick="startRaffle()">
            ÇEKİLİŞİ BAŞLAT
        </button>
        
        <div class="rule-info">
            *Animasyon sırasında isimler rastgele gösterilir. Kazanma şansı çekiliş hakkına bağlıdır.
        </div>
    </div>

    <script>
        // PHP'den gelen veriler
        const allParticipants = <?= $jsonAllParticipants ?>; // Matematiksel Havuz (Kazanan buradan çıkar)
        const visualNames = <?= $jsonVisualNames ?>;         // Görsel Havuz (Animasyon buradan döner)
        
        const display = document.getElementById('winnerName');
        const btn = document.getElementById('startBtn');
        let interval;

        function startRaffle() {
            if (allParticipants.length === 0) {
                alert("Mevcut kurallara uyan katılımcı bulunamadı!");
                return;
            }

            btn.style.display = 'none';
            
            // DÖNME EFEKTİ (Görsel Havuzdan Seç - Eşit Görünüm)
            interval = setInterval(() => {
                // Eğer görsel havuz boşsa (teknik hata) yedeğe geç
                const pool = visualNames.length > 0 ? visualNames : allParticipants.map(p => p.full_name);
                
                const randomName = pool[Math.floor(Math.random() * pool.length)];
                
                display.innerText = randomName;
                display.style.opacity = 0.5;
            }, 100);

            // 5 Saniye Sonra Durdur ve Kazananı Seç
            setTimeout(stopRaffle, 5000);
        }

        function stopRaffle() {
            clearInterval(interval);
            
            // KAZANAN SEÇİMİ (Matematiksel Havuzdan Seç - Hakka Göre Şans)
            // Burada kişinin ne kadar çok kaydı varsa o kadar şansı var.
            const winner = allParticipants[Math.floor(Math.random() * allParticipants.length)];
            
            display.innerText = winner.full_name;
            display.style.opacity = 1;
            display.style.transform = "scale(1.2)";
            display.style.transition = "0.5s";
            
            launchConfetti();
            
            setTimeout(() => {
                btn.innerText = "YENİDEN ÇEK";
                btn.style.display = 'inline-block';
            }, 2000);
        }

        function launchConfetti() {
            var duration = 3 * 1000;
            var animationEnd = Date.now() + duration;
            var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 0 };

            function random(min, max) { return Math.random() * (max - min) + min; }

            var interval = setInterval(function() {
                var timeLeft = animationEnd - Date.now();
                if (timeLeft <= 0) return clearInterval(interval);
                var particleCount = 50 * (timeLeft / duration);
                
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: random(0.1, 0.3), y: Math.random() - 0.2 } }));
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: random(0.7, 0.9), y: Math.random() - 0.2 } }));
            }, 250);
        }
    </script>
</body>
</html>