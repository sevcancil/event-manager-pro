<?php
// views/client_admin/polls.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];

// Navbar'ın çalışması için Event verisi ŞART
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

// --- YENİ ANKET EKLEME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_poll'])) {
    $question = trim($_POST['question']);
    $options = $_POST['options']; 
    
    // Soruyu kaydet
    $db->query("INSERT INTO polls (event_id, question, status) VALUES (?, ?, 'passive')", [$event['id'], $question]);
    $pollId = $db->lastInsertId();
    
    // Şıkları kaydet
    foreach ($options as $opt) {
        if (!empty(trim($opt))) {
            $db->query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)", [$pollId, trim($opt)]);
        }
    }
    header("Location: polls.php");
    exit;
}

// --- DURUM DEĞİŞTİR (AKTİF/PASİF) ---
if (isset($_GET['toggle'])) {
    $pollId = $_GET['toggle'];
    $db->query("UPDATE polls SET status = 'passive' WHERE event_id = ?", [$event['id']]);
    $db->query("UPDATE polls SET status = 'active' WHERE id = ?", [$pollId]);
    header("Location: polls.php");
    exit;
}

// --- SİLME ---
if (isset($_GET['delete'])) {
    $db->query("DELETE FROM polls WHERE id = ?", [$_GET['delete']]);
    header("Location: polls.php");
    exit;
}

// Anketleri listele
$polls = $db->fetchAll("SELECT * FROM polls WHERE event_id = ? ORDER BY id DESC", [$event['id']]);

// 1. STANDARD HEADER
$pageTitle = 'Anket Yönetimi';
include __DIR__ . '/../layouts/header.php';
?>

<style>
    /* Global dark tema ayarlarını bu sayfa için eziyoruz */
    body { background-color: #f4f6f9 !important; color: #212529 !important; }
    .card { background-color: #fff !important; color: #212529 !important; }
    .table { color: #212529 !important; }
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
                <div class="card-header bg-primary text-white fw-bold">Yeni Soru Oluştur</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="create_poll" value="1">
                        <div class="mb-3">
                            <label class="form-label">Soru:</label>
                            <input type="text" name="question" class="form-control" placeholder="Örn: Yemeği beğendiniz mi?" required>
                        </div>
                        
                        <label class="form-label">Seçenekler:</label>
                        <div class="mb-2"><input type="text" name="options[]" class="form-control form-control-sm" placeholder="Seçenek 1" required></div>
                        <div class="mb-2"><input type="text" name="options[]" class="form-control form-control-sm" placeholder="Seçenek 2" required></div>
                        <div class="mb-2"><input type="text" name="options[]" class="form-control form-control-sm" placeholder="Seçenek 3 (Opsiyonel)"></div>
                        <div class="mb-2"><input type="text" name="options[]" class="form-control form-control-sm" placeholder="Seçenek 4 (Opsiyonel)"></div>
                        
                        <button type="submit" class="btn btn-success w-100 mt-2">
                            <i class="fa-solid fa-plus me-2"></i> Oluştur
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Anket Listesi</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover m-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Soru</th>
                                    <th>Durum</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($polls)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Henüz anket oluşturulmadı.</td></tr>
                                <?php else: ?>
                                    <?php foreach($polls as $poll): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($poll['question']) ?></td>
                                        <td>
                                            <?php if($poll['status'] == 'active'): ?>
                                                <span class="badge bg-success animate__animated animate__pulse animate__infinite">YAYINDA</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="../../public/<?= $event['slug'] ?>/anket-sonuc?poll_id=<?= $poll['id'] ?>" target="_blank" class="btn btn-info text-white" title="Dev Ekrana Yansıt">
                                                    <i class="fa-solid fa-tv"></i> Ekran
                                                </a>

                                                <?php if($poll['status'] == 'passive'): ?>
                                                    <a href="?toggle=<?= $poll['id'] ?>" class="btn btn-outline-success" title="Anketi Başlat">
                                                        <i class="fa-solid fa-play"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="#" class="btn btn-success disabled" title="Zaten Yayında">
                                                        <i class="fa-solid fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="?delete=<?= $poll['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Bu anketi ve sonuçlarını silmek istediğine emin misin?')">
                                                    <i class="fa-solid fa-trash"></i>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>