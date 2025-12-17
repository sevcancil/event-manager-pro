<?php
// views/frontend/stream.php

require_once __DIR__ . '/../../src/Database.php';
$db = new Database();

// Dil dosyasını yükle
if (!isset($lang)) {
    $lang = require __DIR__ . '/../../lang/tr.php';
}

// Etkinlik kontrolü
if (!isset($event)) {
    http_response_code(404);
    exit;
}

// Renk ayarları
$settings = json_decode($event['settings_json'], true);
$primaryColor = $settings['primary_color'] ?? '#0d6efd';

// Onaylanmış fotoğrafları çek (En yeniden en eskiye)
$sql = "SELECT media_uploads.*, guests.full_name, guests.title as guest_title, guests.company
        FROM media_uploads 
        LEFT JOIN guests ON media_uploads.guest_id = guests.id
        WHERE media_uploads.event_id = ? AND media_uploads.is_approved = 1 
        ORDER BY media_uploads.created_at DESC";

$posts = $db->fetchAll($sql, [$event['id']]);

// URL Yardımcıları
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$baseFolder = dirname($_SERVER['SCRIPT_NAME']);
if ($baseFolder === '/' || $baseFolder === '\\') $baseFolder = '';
$baseUrl = "$protocol://$_SERVER[HTTP_HOST]$baseFolder";
$baseUrl = str_replace('\\', '/', $baseUrl);
if (substr($baseUrl, -1) != '/') $baseUrl .= '/';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $lang['stream_title'] ?> - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; padding-bottom: 90px; } /* Menü payı */
        
        .header-area {
            background: <?= $primaryColor ?>;
            color: white;
            padding: 20px 15px;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .feed-card {
            background: white;
            border-radius: 15px;
            border: none;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .feed-header {
            padding: 12px 15px;
            display: flex;
            align-items: center;
        }

        .avatar-placeholder {
            width: 40px; height: 40px;
            background-color: <?= $primaryColor ?>20; /* %20 opaklık */
            color: <?= $primaryColor ?>;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .user-info h6 { margin: 0; font-size: 0.95rem; font-weight: 600; }
        .user-info small { color: #888; font-size: 0.75rem; }

        .feed-image {
            width: 100%;
            height: auto;
            display: block;
            background: #eee;
            min-height: 200px;
            object-fit: cover;
        }

        .feed-footer { padding: 12px 15px; }
        .feed-note { font-size: 0.95rem; color: #333; margin-bottom: 5px; }
        .feed-time { font-size: 0.75rem; color: #aaa; }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }
    </style>
</head>
<body>

    <div class="header-area text-center">
        <h4 class="m-0 fw-bold"><?= $lang['stream_title'] ?></h4>
        <small><?= htmlspecialchars($event['title']) ?></small>
    </div>

    <div class="container" style="max-width: 600px;">
        
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-image fa-4x mb-3"></i>
                <p><?= $lang['no_posts_yet'] ?></p>
                <a href="paylas" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="fa-solid fa-camera me-1"></i> <?= $lang['nav_share'] ?>
                </a>
            </div>
        <?php else: ?>
            
            <?php foreach ($posts as $post): ?>
                <?php 
                    // İsim baş harflerini avatar için al
                    $nameParts = explode(' ', $post['full_name'] ?? 'M G');
                    $initials = mb_substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : '');
                    
                    // Resim yolu
                    $imgSrc = $baseUrl . $post['file_path'];
                    
                    // Zamanı formatla (Örn: 14:30)
                    $time = date('H:i', strtotime($post['created_at']));
                ?>
                
                <div class="feed-card animate__animated animate__fadeInUp">
                    <div class="feed-header">
                        <div class="avatar-placeholder">
                            <?= strtoupper($initials) ?>
                        </div>
                        <div class="user-info">
                            <h6><?= htmlspecialchars($post['full_name'] ?? 'Misafir') ?></h6>
                            <?php if(!empty($post['company'])): ?>
                                <small><?= htmlspecialchars($post['company']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <img src="<?= $imgSrc ?>" class="feed-image" loading="lazy" alt="Event Photo">

                    <?php if (!empty($post['note'])): ?>
                    <div class="feed-footer">
                        <p class="feed-note">
                            <i class="fa-regular fa-comment me-1 text-muted"></i> 
                            <?= htmlspecialchars($post['note']) ?>
                        </p>
                        <span class="feed-time"><?= $time ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <div style="height: 20px;"></div>
    </div>

    <?php require __DIR__ . '/navbar_bottom.php'; ?>

</body>
</html>