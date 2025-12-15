<?php
// views/frontend/vote.php

// 1. DİL VE AYARLAR
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Language.php';

$db = new Database();

// Etkinlik verisi kontrolü (index.php'den geliyorsa $event tanımlıdır)
if (!isset($event)) {
    if (isset($_GET['slug'])) {
        $event = $db->fetch("SELECT * FROM events WHERE slug = ?", [$_GET['slug']]);
    }
    if (!$event) die("Etkinlik bulunamadı.");
}

$settings = json_decode($event['settings_json'], true);
$primaryColor = $settings['primary_color'] ?? '#0d6efd';
$action = 'anket';

// Misafir Girişi Kontrolü
if (!isset($_SESSION['guest_id'])) {
    // Eğer guest_id yoksa, etkinlik slug'ını kullanarak yönlendir
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    if ($basePath === '/' || $basePath === '\\') $basePath = '';
    // paylas sayfasına at (login için)
    header("Location: " . $basePath . '/' . $event['slug'] . '/paylas'); 
    exit;
}

// Değişkenleri Başlat (Hata önlemek için)
$activePoll = false;
$options = [];
$hasVoted = false;
$success = false;

// 2. AKTİF ANKETİ BUL
$activePoll = $db->fetch("SELECT * FROM polls WHERE event_id = ? AND status = 'active' LIMIT 1", [$event['id']]);

if ($activePoll) {
    // 3. SEÇENEKLERİ ÇEK
    $options = $db->fetchAll("SELECT * FROM poll_options WHERE poll_id = ?", [$activePoll['id']]);
    
    // 4. KULLANICI DAHA ÖNCE OY VERMİŞ Mİ?
    $checkVote = $db->fetch("SELECT id FROM poll_votes WHERE poll_id = ? AND guest_id = ?", [$activePoll['id'], $_SESSION['guest_id']]);
    if ($checkVote) {
        $hasVoted = true;
    }
}

// 5. OY VERME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_option']) && $activePoll && !$hasVoted) {
    $optionId = $_POST['vote_option'];
    
    try {
        // Oyu Kaydet
        $db->query("INSERT INTO poll_votes (poll_id, option_id, guest_id) VALUES (?, ?, ?)", 
                   [$activePoll['id'], $optionId, $_SESSION['guest_id']]);
        
        $success = true;
        $hasVoted = true; // Ekranda teşekkür mesajı göstermek için
        
    } catch (Exception $e) {
        // Hata olursa loglayabiliriz ama kullanıcıya basit hata gösterelim
    }
}
?>

<!DOCTYPE html>
<html lang="<?= isset($currentLang) ? $currentLang : 'tr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['vote_title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body { 
            background-color: #111; 
            color: white; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            min-height:100vh; 
            text-align:center;
            padding-bottom: 80px; /* Menü için boşluk */
        }
        .poll-card { 
            background: rgba(255,255,255,0.1); 
            backdrop-filter: blur(10px);
            padding: 30px; 
            border-radius: 20px; 
            width: 100%; 
            max-width: 400px; 
            border: 1px solid rgba(255,255,255,0.2); 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .btn-option { 
            display: block; 
            width: 100%; 
            margin-bottom: 15px; 
            padding: 15px; 
            font-size: 1.1rem; 
            border-radius: 12px; 
            border: 2px solid rgba(255,255,255,0.3); 
            background: transparent; 
            color: white; 
            transition: 0.2s; 
            font-weight: 500;
        }
        .btn-option:hover { 
            border-color: <?= $primaryColor ?>; 
            background: <?= $primaryColor ?>; 
            color: white;
            transform: translateY(-2px);
        }
        .voted-msg { color: #2ecc71; font-size: 1.3rem; font-weight: bold; }
        
        /* Geri butonu */
        .btn-back-link {
            text-decoration: none; color: rgba(255,255,255,0.6); font-size: 0.9rem;
            display: inline-block; margin-top: 20px;
        }
        .btn-back-link:hover { color: white; }
    </style>
</head>
<body>
    <div class="container px-4">
        
        <div class="poll-card animate__animated animate__fadeInUp">
            <?php if (!$activePoll): ?>
                <div class="py-5">
                    <i class="fa-regular fa-clock fa-3x mb-3 text-white-50"></i>
                    <h3><?= $lang['wait_title'] ?></h3>
                    <p class="text-white-50 mt-3"><?= $lang['no_active_poll'] ?></p>
                    
                    <?php 
                        // Geri linkini oluştur
                        $basePath = dirname($_SERVER['SCRIPT_NAME']);
                        if ($basePath === '/' || $basePath === '\\') $basePath = '';
                        $shareLink = $basePath . '/' . $event['slug'] . '/paylas';
                    ?>
                    <a href="<?= $shareLink ?>" class="btn btn-outline-light btn-sm mt-4 px-4 rounded-pill"><?= $lang['back_to_share'] ?></a>
                </div>
            <?php else: ?>
                
                <h4 class="mb-4 fw-bold"><?= htmlspecialchars($activePoll['question']) ?></h4>

                <?php if ($hasVoted): ?>
                    <div class="voted-msg py-5 animate__animated animate__zoomIn">
                        <i class="fa-solid fa-circle-check fa-4x mb-3"></i><br>
                        <?= $lang['vote_received'] ?><br>
                        <small class="text-white-50 fw-normal fs-6 d-block mt-2"><?= $lang['result_screen_hint'] ?></small>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="poll_id" value="<?= $activePoll['id'] ?>">
                        <?php foreach($options as $opt): ?>
                            <button type="submit" name="vote_option" value="<?= $opt['id'] ?>" class="btn btn-option">
                                <?= htmlspecialchars($opt['option_text']) ?>
                            </button>
                        <?php endforeach; ?>
                    </form>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        
    </div>
    
    <?php 
    // Navbar'ı include et
    $navPath = __DIR__ . '/navbar_bottom.php';
    if(file_exists($navPath)) include $navPath; 
    ?>
</body>
</html>