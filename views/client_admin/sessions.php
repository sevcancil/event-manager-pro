<?php
// views/client_admin/sessions.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];

// 1. Etkinlik Verisi (Navbar İçin Şart)
// --- ETKİNLİK SEÇİM KONTROLÜ (GÜNCELLENDİ) ---
if (!isset($_SESSION['current_event_id'])) {
    header("Location: dashboard.php");
    exit;
}
$eventId = $_SESSION['current_event_id'];

// Sadece oturumdaki ID'ye ve User ID'ye uyan etkinliği çek (Güvenlik için User ID şart)
$event = $db->fetch("SELECT * FROM events WHERE id = ? AND user_id = ?", [$eventId, $userId]);

if (!$event) {
    // Eğer session'daki ID veritabanında yoksa (silinmişse vb.)
    unset($_SESSION['current_event_id']);
    header("Location: dashboard.php");
    exit;
}
// ---------------------------------------------

// --- YENİ OTURUM EKLEME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_session'])) {
    $title = trim($_POST['title']);
    $time = $_POST['start_time'];
    
    if (!empty($title)) {
        $db->query("INSERT INTO sessions (event_id, title, start_time) VALUES (?, ?, ?)", 
                   [$event['id'], $title, $time]);
        header("Location: sessions.php");
        exit;
    }
}

// --- OTURUM LİSTESİ VE KATILIMCI SAYILARI ---
$sessions = $db->fetchAll("
    SELECT s.*, 
    (SELECT COUNT(*) FROM session_logs l WHERE l.session_id = s.id) as guest_count 
    FROM sessions s 
    WHERE s.event_id = ? 
    ORDER BY s.start_time ASC", 
    [$event['id']]
);

// 2. HEADER (BAŞLIK VE STİL)
$pageTitle = 'Oturum Yönetimi';
include __DIR__ . '/../layouts/header.php';
?>

<style>
    /* Global dark tema ayarlarını bu sayfa için eziyoruz */
    body { background-color: #f4f6f9 !important; color: #212529 !important; }
    .card { background-color: #fff !important; color: #212529 !important; }
    
    /* TABLO DÜZELTMESİ */
    .table { 
        color: #212529 !important; /* Tablo yazılarını siyah yap */
        background-color: #fff !important; /* Tablo zeminini beyaz yap */
    }
    .table th, .table td {
        color: #212529 !important; /* Hücre yazılarını da siyah yap */
    }
    
    .text-muted { color: #6c757d !important; }
    label { color: #212529 !important; }
    .form-control {
        background-color: #fff !important;
        color: #212529 !important;
        border: 1px solid #ced4da !important;
    }
</style>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="container">
    <div class="row">
        
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">Yeni Oturum / Kapı</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="add_session" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Oturum Adı</label>
                            <input type="text" name="title" class="form-control" placeholder="Örn: Sabah Paneli, Öğle Yemeği..." required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Başlangıç Saati</label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-plus me-2"></i> Oluştur
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Oturumlar & Katılım Durumu</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Oturum Adı</th>
                                <th class="d-none d-md-table-cell">Tarih</th>
                                <th class="text-center">İçerideki</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($sessions)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Henüz oturum oluşturulmadı.</td></tr>
                            <?php else: ?>
                                <?php foreach($sessions as $session): ?>
                                <tr>
                                    <td class="fw-bold">
                                        <?= htmlspecialchars($session['title']) ?>
                                        <div class="d-block d-md-none small text-muted">
                                            <?= date('d.m H:i', strtotime($session['start_time'])) ?>
                                        </div>
                                    </td>
                                    
                                    <td class="small text-muted d-none d-md-table-cell">
                                        <?= date('d.m.Y H:i', strtotime($session['start_time'])) ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge bg-info text-dark fs-6"><?= $session['guest_count'] ?></span>
                                    </td>
                                    
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="session_details.php?id=<?= $session['id'] ?>" class="btn btn-secondary" title="Listeyi Gör / İndir">
                                                <i class="fa-solid fa-list"></i> Liste
                                            </a>
                                            
                                            <a href="session_scan.php?id=<?= $session['id'] ?>" class="btn btn-primary" title="Giriş Yap">
                                                <i class="fa-solid fa-qrcode"></i> Tara
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>