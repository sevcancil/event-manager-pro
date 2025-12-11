<?php
// views/client_admin/dashboard.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin'); // Sadece Müşteri Girebilir

$db = new Database();
$userId = $_SESSION['user_id'];

// 1. Bu müşteriye ait etkinliği bul
$event = $db->fetch("SELECT * FROM events WHERE user_id = ?", [$userId]);

if (!$event) {
    die("Size atanmış aktif bir etkinlik bulunamadı. Lütfen yönetici ile iletişime geçin.");
}

// --- AJAX İŞLEMLERİ (Onayla / Sil) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $mediaId = $_POST['media_id'];
    
    // Güvenlik: Bu medya gerçekten bu etkinliğe mi ait?
    $check = $db->fetch("SELECT id FROM media_uploads WHERE id = ? AND event_id = ?", [$mediaId, $event['id']]);
    
    if ($check) {
        if ($_POST['action'] === 'approve') {
            $db->query("UPDATE media_uploads SET is_approved = 1 WHERE id = ?", [$mediaId]);
            echo json_encode(['status' => 'success', 'message' => 'Onaylandı']);
        } elseif ($_POST['action'] === 'delete') {
            $db->query("DELETE FROM media_uploads WHERE id = ?", [$mediaId]);
            echo json_encode(['status' => 'success', 'message' => 'Silindi']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz işlem']);
    }
    exit; 
}

// 2. İstatistikler
$totalPhotos = $db->fetch("SELECT COUNT(*) as count FROM media_uploads WHERE event_id = ?", [$event['id']])['count'];
$approvedPhotos = $db->fetch("SELECT COUNT(*) as count FROM media_uploads WHERE event_id = ? AND is_approved = 1", [$event['id']])['count'];
$pendingPhotos = $totalPhotos - $approvedPhotos;

// 3. Medyaları Getir (Yeniden eskiye)
$mediaList = $db->fetchAll("
    SELECT m.*, g.full_name, g.email 
    FROM media_uploads m 
    LEFT JOIN guests g ON m.guest_id = g.id 
    WHERE m.event_id = ? 
    ORDER BY m.created_at DESC", 
    [$event['id']]
);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .media-card { transition: 0.3s; border: none; overflow: hidden; }
        .media-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-img-top { height: 200px; object-fit: cover; background-color: #eee; }
        .status-badge { position: absolute; top: 10px; right: 10px; }
        .guest-info { font-size: 0.85rem; color: #666; }
        .stat-card { cursor: pointer; transition: 0.2s; }
        .stat-card:hover { filter: brightness(1.1); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm py-3">
        <div class="container-fluid px-4">
            
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fa-solid fa-layer-group text-primary me-2"></i>Yönetim Paneli
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">
                
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fa-solid fa-images me-1"></i> Medya Yönetimi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="guests.php">
                            <i class="fa-solid fa-users me-1"></i> Misafir Listesi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="checkin.php">
                            <i class="fa-solid fa-qrcode me-1"></i> Kapı Kontrol
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="raffle.php">
                            <i class="fa-solid fa-trophy me-1 text-warning"></i> Çekiliş Yap
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="polls.php">
                            <i class="fa-solid fa-square-poll-vertical me-1 text-info"></i> Canlı Oylama
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedule.php">
                            <i class="fa-solid fa-calendar-days me-1 text-primary"></i> Program Akışı
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center border-start border-secondary ps-lg-3 ms-lg-3 mt-3 mt-lg-0">
                    <div class="text-white text-end me-3 d-none d-lg-block" style="line-height: 1.2;">
                        <small class="text-white-50" style="font-size: 0.75rem;">AKTİF ETKİNLİK</small><br>
                        <span class="fw-bold"><?= htmlspecialchars($event['title']) ?></span>
                    </div>
                    
                    <a href="../../logout.php" class="btn btn-danger btn-sm px-3">
                        <i class="fa-solid fa-power-off me-1"></i> Çıkış
                    </a>
                </div>

            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        
        <div class="row mb-4 g-3">
            
            <div class="col-md-2">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h1><?= $totalPhotos ?></h1>
                        <span class="small">Toplam Medya</span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <h1><?= $pendingPhotos ?></h1>
                        <span class="small">Onay Bekleyen</span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h1><?= $approvedPhotos ?></h1>
                        <span class="small">Yayındaki</span>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card bg-info text-white h-100 stat-card" onclick="window.open('../../public/<?= $event['slug'] ?>/akis', '_blank')">
                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                        <i class="fa-solid fa-tv fa-2x mb-2"></i>
                        <span class="fw-bold small">CANLI DUVARI AÇ</span>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card bg-secondary text-white h-100 stat-card" onclick="window.location.href='guests.php'">
                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                        <i class="fa-solid fa-file-csv fa-2x mb-2"></i>
                        <span class="fw-bold small">MİSAFİR LİSTESİ</span>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card bg-dark text-white h-100 stat-card" style="border: 1px solid #555;" onclick="window.location.href='download-zip.php'">
                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                        <i class="fa-solid fa-file-zipper fa-2x mb-2 text-warning"></i>
                        <span class="fw-bold small">HEPSİNİ İNDİR</span>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" id="mediaTabs">
            <li class="nav-item">
                <a class="nav-link active" href="#" onclick="filterMedia('all')">Tümü</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="filterMedia('pending')">Onay Bekleyenler <span class="badge bg-warning text-dark ms-1"><?= $pendingPhotos ?></span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="filterMedia('approved')">Onaylananlar</a>
            </li>
        </ul>

        <div class="row g-4" id="gallery">
            <?php foreach ($mediaList as $media): ?>
                <?php 
                    $statusClass = $media['is_approved'] ? 'approved' : 'pending'; 
                    $borderClass = $media['is_approved'] ? 'border-success' : 'border-warning';
                ?>
                <div class="col-md-3 col-sm-6 media-item <?= $statusClass ?>" id="card-<?= $media['id'] ?>">
                    <div class="card media-card h-100 <?= $borderClass ?> border-2">
                        
                        <span class="badge status-badge <?= $media['is_approved'] ? 'bg-success' : 'bg-warning text-dark' ?>" id="badge-<?= $media['id'] ?>">
                            <?= $media['is_approved'] ? 'Yayında' : 'Bekliyor' ?>
                        </span>

                        <a href="../../public/<?= $media['file_path'] ?>" target="_blank">
                            <img src="../../public/<?= $media['file_path'] ?>" class="card-img-top" alt="Media">
                        </a>

                        <div class="card-body d-flex flex-column">
                            <div class="mb-2 guest-info">
                                <i class="fa-solid fa-user me-1"></i> <strong><?= htmlspecialchars($media['full_name'] ?? 'Anonim') ?></strong><br>
                                <small class="text-muted"><?= date('H:i', strtotime($media['created_at'])) ?></small>
                            </div>
                            
                            <?php if(!empty($media['note'])): ?>
                                <p class="card-text small fst-italic">"<?= htmlspecialchars($media['note']) ?>"</p>
                            <?php endif; ?>

                            <div class="mt-auto d-flex gap-2">
                                <?php if (!$media['is_approved']): ?>
                                    <button onclick="approveMedia(<?= $media['id'] ?>)" class="btn btn-success flex-grow-1 btn-approve">
                                        <i class="fa-solid fa-check"></i> Onayla
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="deleteMedia(<?= $media['id'] ?>)" class="btn btn-outline-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($mediaList)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-regular fa-images fa-3x mb-3"></i>
                <h4>Henüz hiç fotoğraf yüklenmemiş.</h4>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveMedia(id) {
            $.post('', { action: 'approve', media_id: id }, function(response) {
                if(response.status === 'success') {
                    $('#card-' + id + ' .card').removeClass('border-warning').addClass('border-success');
                    $('#badge-' + id).removeClass('bg-warning text-dark').addClass('bg-success').text('Yayında');
                    $('#card-' + id + ' .btn-approve').fadeOut();
                }
            }, 'json');
        }

        function deleteMedia(id) {
            if(confirm('Bu fotoğrafı silmek istediğinize emin misiniz?')) {
                $.post('', { action: 'delete', media_id: id }, function(response) {
                    if(response.status === 'success') {
                        $('#card-' + id).fadeOut(300, function(){ $(this).remove(); });
                    }
                }, 'json');
            }
        }

        function filterMedia(type) {
            $('.nav-link').removeClass('active');
            $(event.target).addClass('active');

            if(type === 'all') {
                $('.media-item').fadeIn();
            } else {
                $('.media-item').hide();
                $('.media-item.' + type).fadeIn();
            }
        }
    </script>
</body>
</html>