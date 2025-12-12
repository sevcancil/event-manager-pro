<?php
// views/client_admin/schedule.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];

// 1. Etkinlik Verisi (Navbar İçin Şart)
$event = $db->fetch("SELECT * FROM events WHERE user_id = ?", [$userId]);
if (!$event) die("Etkinlik bulunamadı.");

// --- YENİ KAYIT EKLEME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    // Tarih ve Saati alıp birleştiriyoruz (Format: Y-m-d H:i:s)
    $date = $_POST['event_date'];
    $time = $_POST['event_time'];
    
    $combinedDateTime = $date . ' ' . $time . ':00'; // Saniye ekledik

    $title = trim($_POST['title']);
    $desc = trim($_POST['description']); 
    $icon = $_POST['icon'];
    
    // Veritabanına DATETIME olarak kaydediyoruz
    $db->query("INSERT INTO event_schedule (event_id, start_time, title, description, icon) VALUES (?, ?, ?, ?, ?)", 
               [$event['id'], $combinedDateTime, $title, $desc, $icon]);
               
    header("Location: schedule.php");
    exit;
}

// --- SİLME ---
if (isset($_GET['delete'])) {
    $db->query("DELETE FROM event_schedule WHERE id = ?", [$_GET['delete']]);
    header("Location: schedule.php");
    exit;
}

// Listeyi çekerken tarihe göre sıralı çekiyoruz
$schedule = $db->fetchAll("SELECT * FROM event_schedule WHERE event_id = ? ORDER BY start_time ASC", [$event['id']]);

// 2. HEADER (STİL VE BAŞLIK)
$pageTitle = 'Program Akışı';
include __DIR__ . '/../layouts/header.php';
?>

<style>
    /* Global dark tema ayarlarını bu sayfa için eziyoruz */
    body { background-color: #f4f6f9 !important; color: #212529 !important; }
    .card { background-color: #fff !important; color: #212529 !important; }
    .list-group-item { background-color: #fff !important; color: #212529 !important; }
    .text-muted { color: #6c757d !important; }
    h6, label { color: #212529 !important; }
    .form-control, .form-select {
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
                <div class="card-header bg-primary text-white fw-bold">Program Ekle</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="add_item" value="1">
                        
                        <div class="row mb-3">
                            <div class="col-7">
                                <label class="form-label">Tarih</label>
                                <input type="date" name="event_date" class="form-control" 
                                       value="<?= date('Y-m-d', strtotime($event['event_date'])) ?>" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Saat</label>
                                <input type="time" name="event_time" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Başlık</label>
                            <input type="text" name="title" class="form-control" placeholder="Örn: Açılış Konuşması" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama / Konuşmacılar</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Konuşmacı: Ali Veli&#10;Detay: Salon A"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">İkon</label>
                            <select name="icon" class="form-select">
                                <option value="fa-microphone">🎤 Mikrofon</option>
                                <option value="fa-utensils">🍽️ Yemek</option>
                                <option value="fa-coffee">☕ Kahve Arası</option>
                                <option value="fa-music">🎵 Müzik/Parti</option>
                                <option value="fa-users">👥 Panel</option>
                                <option value="fa-video">🎥 Sunum</option>
                                <option value="fa-flag-checkered">🏁 Bitiş</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-plus me-2"></i> Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Akış Listesi</div>
                <ul class="list-group list-group-flush">
                    <?php 
                    $currentDate = null;
                    
                    if(empty($schedule)): ?>
                        <li class="list-group-item text-center text-muted py-5">
                            <i class="fa-regular fa-calendar-xmark fa-2x mb-3"></i><br>
                            Henüz akış eklenmemiş.
                        </li>
                    <?php else: ?>
                        <?php foreach($schedule as $item): 
                            // Tarih ve Saati ayırıyoruz
                            $itemTime = strtotime($item['start_time']);
                            $dateStr = date('d.m.Y', $itemTime);
                            $timeStr = date('H:i', $itemTime);
                        ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-start">
                                    
                                    <div class="bg-light rounded p-2 me-3 text-center border" style="min-width:70px;">
                                        <div class="fw-bold fs-5 text-dark"><?= $timeStr ?></div>
                                        <div class="text-muted small" style="font-size: 11px;"><?= $dateStr ?></div>
                                    </div>

                                    <div>
                                        <h6 class="m-0 mb-1 text-dark">
                                            <i class="fa-solid <?= $item['icon'] ?> me-2 text-primary"></i>
                                            <?= htmlspecialchars($item['title']) ?>
                                        </h6>
                                        <small class="text-muted" style="white-space: pre-line;"><?= htmlspecialchars($item['description']) ?></small>
                                    </div>
                                </div>
                                <a href="?delete=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu maddeyi silmek istediğinize emin misiniz?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>