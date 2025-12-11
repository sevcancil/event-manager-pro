<?php
$settings = json_decode($event['settings_json'], true); $primaryColor = $settings['primary_color'] ?? '#0d6efd'; $action = 'anket';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../src/Database.php';
$db = new Database();

// Misafir Girişi Kontrolü (Session yoksa anket yapamaz)
if (!isset($_SESSION['guest_id'])) {
    // Giriş yapmamışsa, login sayfasına (veya upload sayfasına) yönlendir
    header("Location: paylas"); 
    exit;
}

// Aktif anketi bul
$activePoll = $db->fetch("SELECT * FROM polls WHERE event_id = ? AND status = 'active' LIMIT 1", [$event['id']]);

// OY VERME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_option'])) {
    $optionId = $_POST['vote_option'];
    $pollId = $_POST['poll_id'];
    
    // Daha önce oy vermiş mi?
    $check = $db->fetch("SELECT id FROM poll_votes WHERE poll_id = ? AND guest_id = ?", [$pollId, $_SESSION['guest_id']]);
    
    if (!$check) {
        $db->query("INSERT INTO poll_votes (poll_id, option_id, guest_id) VALUES (?, ?, ?)", [$pollId, $optionId, $_SESSION['guest_id']]);
        $success = "Oyunuz kaydedildi!";
    }
}

// Bu misafir bu ankete oy vermiş mi?
$hasVoted = false;
if ($activePoll) {
    $voteCheck = $db->fetch("SELECT id FROM poll_votes WHERE poll_id = ? AND guest_id = ?", [$activePoll['id'], $_SESSION['guest_id']]);
    if ($voteCheck) $hasVoted = true;
    
    // Şıkları Çek
    $options = $db->fetchAll("SELECT * FROM poll_options WHERE poll_id = ?", [$activePoll['id']]);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canlı Oylama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                    <h3>⏳ Bekleyiniz...</h3>
                    <p class="text-white-50">Şu an aktif bir oylama yok.</p>
                    <a href="paylas" class="btn btn-outline-light btn-sm mt-3">Anı Paylaş'a Dön</a>
                </div>
            <?php else: ?>
                
                <h3 class="mb-4"><?= htmlspecialchars($activePoll['question']) ?></h3>

                <?php if ($hasVoted || isset($success)): ?>
                    <div class="voted-msg py-4">
                        <i class="fa-solid fa-circle-check fa-3x mb-3"></i><br>
                        Oyunuz Alındı!<br>
                        <small class="text-white-50 fw-normal">Sonuçlar dev ekranda.</small>
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