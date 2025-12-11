<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../src/Database.php';
$db = new Database();

// Misafir Girişi Kontrolü
if (!isset($_SESSION['guest_id'])) {
    header("Location: paylas"); // Giriş yapmamışsa yönlendir
    exit;
}

$guest = $db->fetch("SELECT * FROM guests WHERE id = ?", [$_SESSION['guest_id']]);
$settings = json_decode($event['settings_json'], true) ?? [];
$primaryColor = $settings['primary_color'] ?? '#0d6efd';
$action = 'biletim';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>body { background-color: #111; color: white; height: 100vh; display: flex; flex-direction: column; justify-content: center; }</style>
</head>
<body>
    <div class="container text-center">
        <h3 class="mb-4">Dijital Biletiniz</h3>
        
        <div class="card border-0 mx-auto text-dark" style="max-width: 320px; border-radius: 20px; overflow: hidden;">
            <div class="card-header text-white text-center py-3" style="background-color: <?= $primaryColor ?>">
                <h5 class="m-0"><?= htmlspecialchars($event['title']) ?></h5>
            </div>
            <div class="card-body bg-white text-center py-5">
                <div id="qrcode" class="d-flex justify-content-center mb-4"></div>
                <h4 class="fw-bold"><?= htmlspecialchars($guest['full_name']) ?></h4>
                <p class="text-muted m-0"><?= htmlspecialchars($guest['email']) ?></p>
                <div class="mt-3 badge bg-light text-dark border"><?= $guest['qr_code'] ?></div>
            </div>
            <div class="card-footer bg-light text-muted small">
                Girişte bu kodu okutunuz.
            </div>
        </div>
    </div>

    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= $guest['qr_code'] ?>",
            width: 150, height: 150
        });
    </script>

    <?php include __DIR__ . '/navbar_bottom.php'; ?>
</body>
</html>