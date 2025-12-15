<?php
// views/client_admin/guests.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];

// 1. Etkinlik Verisi
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
if (!$event) die("Etkinlik bulunamadı.");

// --- EXCEL İNDİRME ---
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $guests = $db->fetchAll("SELECT * FROM guests WHERE event_id = ? ORDER BY created_at DESC", [$event['id']]);
    $filename = 'Misafir_Listesi_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

    fputcsv($output, ['ID', 'Ad Soyad', 'Şirket', 'Unvan', 'E-Posta', 'Telefon', 'Durum', 'Giriş Saati (Check-in)', 'Kayıt Tarihi'], ";");

    foreach ($guests as $guest) {
        $status = ($guest['check_in_status'] == 1) ? 'İçeride' : 'Gelmedi';
        $checkInTime = $guest['check_in_at'] ? date('H:i:s', strtotime($guest['check_in_at'])) : '-';
        
        fputcsv($output, [
            $guest['id'],
            $guest['full_name'],
            $guest['company'],
            $guest['title'],
            $guest['email'],
            $guest['phone'],
            $status,
            $checkInTime,
            $guest['created_at']
        ], ";");
    }
    fclose($output);
    exit;
}

// --- HTML LİSTELEME ---
$guests = $db->fetchAll("SELECT * FROM guests WHERE event_id = ? ORDER BY created_at DESC", [$event['id']]);

$pageTitle = 'Misafir Listesi';
include __DIR__ . '/../layouts/header.php';
?>

<style>
    /* Global dark tema ayarlarını bu sayfa için eziyoruz */
    body { background-color: #f4f6f9 !important; color: #212529 !important; }
    .card { background-color: #fff !important; }
    .table { color: #212529 !important; }
    .text-muted { color: #6c757d !important; }
    h4 { color: #212529 !important; }
</style>

<?php include __DIR__ . '/navbar.php'; ?>

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>
            Misafir Listesi
            <span class="badge bg-secondary fs-6 align-middle ms-2"><?= count($guests) ?> Kişi</span>
        </h4>
        <a href="?export=excel" class="btn btn-success">
            <i class="fa-solid fa-file-excel me-2"></i> Excel İndir
        </a>
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
                            <th>Durum</th>
                            <th>Giriş Saati</th> 
                            <th>Kayıt Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($guests)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-user-slash fa-2x mb-3"></i><br>
                                    Henüz kimse kayıt olmamış.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($guests as $index => $guest): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-muted"><?= $index + 1 ?></td>
                                
                                <td class="fw-bold">
                                    <?= htmlspecialchars($guest['full_name']) ?>
                                    <br>
                                    <small class="text-muted fw-normal" style="font-size:0.75rem">
                                        <i class="fa-solid fa-qrcode me-1"></i><?= $guest['qr_code'] ?>
                                    </small>
                                </td>

                                <td>
                                    <?php if($guest['company']): ?>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($guest['company']) ?></div>
                                    <?php else: ?>
                                        <div class="text-muted">-</div>
                                    <?php endif; ?>
                                    
                                    <?php if($guest['title']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($guest['title']) ?></small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="small"><?= htmlspecialchars($guest['email']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($guest['phone']) ?></div>
                                </td>

                                <td>
                                    <?php if($guest['check_in_status'] == 1): ?>
                                        <span class="badge bg-success">İÇERİDE</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary opacity-50">GELMEDİ</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="fw-bold text-primary">
                                    <?php if($guest['check_in_at']): ?>
                                        <i class="fa-regular fa-clock me-1"></i>
                                        <?= date('H:i', strtotime($guest['check_in_at'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td class="small text-muted">
                                    <?= date('d.m.Y H:i', strtotime($guest['created_at'])) ?>
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