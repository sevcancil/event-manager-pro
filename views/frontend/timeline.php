<?php
// views/frontend/timeline.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../src/Database.php';
$db = new Database();

// Ayarları Çek
$settings = json_decode($event['settings_json'], true) ?? [];
$primaryColor = $settings['primary_color'] ?? '#0d6efd';

// Programı Çek
$schedule = $db->fetchAll("SELECT * FROM event_schedule WHERE event_id = ? ORDER BY start_time ASC", [$event['id']]);

// Şu anki sayfa adı (Nav için)
$action = 'program';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akış - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #111; color: white; min-height: 100vh; }
        .timeline-item { border-left: 2px solid <?= $primaryColor ?>; padding-left: 20px; margin-left: 10px; padding-bottom: 30px; position: relative; }
        .timeline-item::before { content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; background: <?= $primaryColor ?>; border-radius: 50%; }
        .time-badge { font-weight: bold; color: <?= $primaryColor ?>; margin-bottom: 5px; display: block; }
    </style>
</head>
<body>
    <div class="container py-4">
        <h3 class="mb-4 fw-bold">Etkinlik Programı</h3>
        
        <?php if(empty($schedule)): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-regular fa-calendar-xmark fa-3x mb-3"></i>
                <p>Henüz program açıklanmadı.</p>
            </div>
        <?php else: ?>
            <div class="mt-4">
                <?php foreach($schedule as $item): ?>
                    <div class="timeline-item animate__animated animate__fadeInUp">
                        <span class="time-badge"><?= substr($item['start_time'], 0, 5) ?></span>
                        <h5 class="mb-1">
                            <i class="fa-solid <?= $item['icon'] ?> me-2" style="color: <?= $primaryColor ?>; font-size: 1.1em;"></i>
                            <?= htmlspecialchars($item['title']) ?>
                        </h5>
                        <small class="text-white-50"><?= nl2br(htmlspecialchars($item['description'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/navbar_bottom.php'; // Birazdan oluşturacağız ?>
</body>
</html>