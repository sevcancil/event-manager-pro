<?php
$settings = json_decode($event['settings_json'], true); $primaryColor = $settings['primary_color'] ?? '#0d6efd'; $action = 'anket';
if (session_status() === PHP_SESSION_NONE) session_start();
// 1. DİL DOSYASI
require_once __DIR__ . '/../../src/Language.php';
require_once __DIR__ . '/../../src/Database.php';
$db = new Database();

// Misafir Girişi Kontrolü
if (!isset($_SESSION['guest_id'])) {
    header("Location: paylas"); 
    exit;
}

// ... (Anket veritabanı sorguları aynen kalacak) ...
$activePoll = $db->fetch("SELECT * FROM polls WHERE event_id = ? AND status = 'active' LIMIT 1", [$event['id']]);
// ... OY VERME İŞLEMİ KODLARI BURAYA ...
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_option'])) {
    // ...
    // ...
    // $success = "Oyunuz kaydedildi!"; -> BUNU DİL DOSYASINDAN ÇEKECEĞİZ
}
// ...
?>

<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['vote_title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #111; color: white; display:flex; align-items:center; justify-content:center; height:100vh; text-align:center;}
        .poll-card { background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; width: 100%; max-width: 400px; border: 1px solid #333; }
        .btn-option { display: block; width: 100%; margin-bottom: 10px; padding: 15px; font-size: 1.1rem; border-radius: 10px; border: 2px solid #555; background: transparent; color: white; transition: 0.2s; }
        .btn-option:hover { border-color: #0d6efd; background: rgba(13, 110, 253, 0.1); }
        .voted-msg { color: #2ecc71; font-size: 1.2rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container px-4">
        
        <div class="poll-card animate__animated animate__fadeIn">
            <?php if (!$activePoll): ?>
                <div class="py-5">
                    <h3><?= $lang['wait_title'] ?></h3>
                    <p class="text-white-50"><?= $lang['no_active_poll'] ?></p>
                    <a href="paylas" class="btn btn-outline-light btn-sm mt-3"><?= $lang['back_to_share'] ?></a>
                </div>
            <?php else: ?>
                
                <h3 class="mb-4"><?= htmlspecialchars($activePoll['question']) ?></h3>

                <?php if ($hasVoted || isset($success)): ?>
                    <div class="voted-msg py-4">
                        <i class="fa-solid fa-circle-check fa-3x mb-3"></i><br>
                        <?= $lang['vote_received'] ?><br>
                        <small class="text-white-50 fw-normal"><?= $lang['result_screen_hint'] ?></small>
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
    <?php include __DIR__ . '/navbar_bottom.php'; ?>
</body>
</html>