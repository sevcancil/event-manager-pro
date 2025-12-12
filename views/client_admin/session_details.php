<?php
// views/client_admin/session_details.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();

if (!isset($_GET['id'])) die("Oturum ID eksik.");
$sessionId = $_GET['id'];

// 1. Oturum Bilgisini ve Yetkiyi Kontrol Et
$session = $db->fetch("
    SELECT s.*, e.title as event_title 
    FROM sessions s 
    JOIN events e ON s.event_id = e.id 
    WHERE s.id = ? AND e.user_id = ?", 
    [$sessionId, $_SESSION['user_id']]
);

if (!$session) {
    die("Oturum bulunamadı veya yetkiniz yok.");
}

// Global Event değişkeni (Navbar için gerekli)
$event = ['title' => $session['event_title'], 'id' => $session['event_id']];

// 2. Katılımcıları Çek
$attendees = $db->fetchAll("
    SELECT g.*, l.scanned_at 
    FROM session_logs l 
    JOIN guests g ON l.guest_id = g.id 
    WHERE l.session_id = ? 
    ORDER BY l.scanned_at DESC", 
    [$sessionId]
);

// --- EXCEL İNDİRME İŞLEMİ ---
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $filename = 'Oturum_Katilim_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); 

    // Başlıklar
    fputcsv($output, ['Oturum Adı: ' . $session['title']], ";");
    fputcsv($output, [], ";"); // Boş satır
    fputcsv($output, ['Ad Soyad', 'Şirket', 'Unvan', 'E-Posta', 'Giriş Saati'], ";");

    foreach ($attendees as $person) {
        fputcsv($output, [
            $person['full_name'],
            $person['company'],
            $person['title'],
            $person['email'],
            date('H:i:s', strtotime($person['scanned_at']))
        ], ";");
    }
    fclose($output);
    exit;
}

// 3. HEADER VE BAŞLIK
$pageTitle = 'Oturum Detayı: ' . $session['title'];
include __DIR__ . '/../layouts/header.php';
?>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="sessions.php" class="text-decoration-none text-muted small">
                <i class="fa-solid fa-arrow-left"></i> Oturumlara Dön
            </a>
            <h3 class="fw-bold mt-1">
                <?= htmlspecialchars($session['title']) ?>
                <span class="badge bg-primary fs-6 align-middle ms-2"><?= count($attendees) ?> Kişi</span>
            </h3>
            <small class="text-muted">
                <i class="fa-regular fa-clock me-1"></i> 
                <?= date('d.m.Y H:i', strtotime($session['start_time'])) ?>
            </small>
        </div>
        
        <div class="btn-group">
            <a href="session_scan.php?id=<?= $session['id'] ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-qrcode me-2"></i> Tarama Yap
            </a>
            <a href="?id=<?= $session['id'] ?>&export=excel" class="btn btn-success">
                <i class="fa-solid fa-file-excel me-2"></i> Listeyi İndir
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped m-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Ad Soyad</th>
                            <th>Şirket / Unvan</th>
                            <th>İletişim</th>
                            <th>Giriş Saati</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendees)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-users-slash fa-2x mb-2"></i><br>
                                    Bu oturuma henüz kimse giriş yapmamış.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendees as $index => $p): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted"><?= $index + 1 ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td>
                                    <?php if($p['company']): ?>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($p['company']) ?></div>
                                    <?php else: ?>
                                        <div class="text-muted">-</div>
                                    <?php endif; ?>
                                    
                                    <?php if($p['title']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($p['title']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($p['email']) ?></td>
                                <td class="fw-bold text-primary">
                                    <?= date('H:i:s', strtotime($p['scanned_at'])) ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>