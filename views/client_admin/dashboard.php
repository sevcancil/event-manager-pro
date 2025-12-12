<?php
// views/client_admin/dashboard.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');

$db = new Database();
$userId = $_SESSION['user_id'];

// 1. Etkinliği Bul
$event = $db->fetch("SELECT * FROM events WHERE user_id = ?", [$userId]);

if (!$event) {
    die("Size atanmış aktif bir etkinlik bulunamadı. Lütfen yönetici ile iletişime geçin.");
}

// Layout için Gerekli Değişkenler
$settings = json_decode($event['settings_json'], true);
$primaryColor = $settings['primary_color'] ?? '#0d6efd';
$pageTitle = 'Müşteri Paneli - ' . $event['title'];

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

// 1. HTML BAŞLIK
include __DIR__ . '/../layouts/header.php'; 
?>

<style>
    /* Global dark ayarlarını eziyoruz (Okunabilirlik için) */
    body { background-color: #f4f6f9 !important; color: #212529 !important; }
    h1, h2, h3, h4, h5, h6 { color: #212529 !important; }
    
    /* Kart Stilleri */
    .media-card { transition: 0.3s; border: none; overflow: hidden; background-color: #fff !important; }
    .media-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .card-img-top { height: 200px; object-fit: cover; background-color: #eee; }
    .status-badge { position: absolute; top: 10px; right: 10px; }
    .guest-info { font-size: 0.85rem; color: #666 !important; }
    
    /* İstatistik Kartları */
    .stat-card { cursor: pointer; transition: 0.2s; }
    .stat-card:hover { filter: brightness(1.1); }
    
    /* İstatistik Kartlarının içindeki yazılar beyaz kalsın (zaten bg-dark vs. var) */
    .card.bg-primary h1, .card.bg-primary span,
    .card.bg-success h1, .card.bg-success span,
    .card.bg-info span, .card.bg-info i,
    .card.bg-secondary span, .card.bg-secondary i,
    .card.bg-dark span, .card.bg-dark i { color: #fff !important; }
    
    .card.bg-warning h1, .card.bg-warning span { color: #212529 !important; }
</style>

<?php include __DIR__ . '/navbar.php'; ?>

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