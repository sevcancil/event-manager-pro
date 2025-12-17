<?php
// views/client_admin/guests.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];

// --- ETKİNLİK KONTROLÜ VE OTOMATİK SEÇİM (DÜZELTME BURADA) ---
if (!isset($_SESSION['current_event_id'])) {
    // Eğer seçili etkinlik yoksa, kullanıcının en son oluşturduğu etkinliği bul
    $latestEvent = $db->fetch("SELECT * FROM events WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [$userId]);
    
    if ($latestEvent) {
        // Varsa onu otomatik olarak "seçili etkinlik" yap
        $_SESSION['current_event_id'] = $latestEvent['id'];
    } else {
        // Eğer kullanıcının hiç etkinliği yoksa Dashboard'a gönder
        header("Location: dashboard.php");
        exit;
    }
}

$eventId = $_SESSION['current_event_id'];
// Etkinliğin bu kullanıcıya ait olup olmadığını tekrar doğrula
$event = $db->fetch("SELECT * FROM events WHERE id = ? AND user_id = ?", [$eventId, $userId]);

if (!$event) {
    // Geçersiz bir ID varsa oturumdan sil ve geri gönder
    unset($_SESSION['current_event_id']);
    header("Location: dashboard.php");
    exit;
}

// --- ARAMA FİLTRESİ HAZIRLIĞI ---
$searchQuery = $_GET['q'] ?? '';
$sql = "SELECT * FROM guests WHERE event_id = ?";
$params = [$event['id']];

if (!empty($searchQuery)) {
    $sql .= " AND (full_name LIKE ? OR company LIKE ? OR email LIKE ?)";
    $term = '%' . $searchQuery . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}
$sql .= " ORDER BY created_at DESC";

// --- EXCEL İNDİRME ---
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $guests = $db->fetchAll($sql, $params);
    $filename = 'Misafir_Listesi_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

    fputcsv($output, ['ID', 'Ad Soyad', 'Şirket', 'Unvan', 'E-Posta', 'Telefon', 'Durum', 'Giriş Saati', 'Kayıt Tarihi'], ";");

    foreach ($guests as $guest) {
        $status = ($guest['check_in_status'] == 1) ? 'İçeride' : 'Gelmedi';
        $checkInTime = $guest['check_in_at'] ? date('H:i:s', strtotime($guest['check_in_at'])) : '-';
        fputcsv($output, [
            $guest['id'], $guest['full_name'], $guest['company'], $guest['title'],
            $guest['email'], $guest['phone'], $status, $checkInTime, $guest['created_at']
        ], ";");
    }
    fclose($output);
    exit;
}

// --- HTML LİSTELEME ---
$guests = $db->fetchAll($sql, $params);
$pageTitle = 'Misafir Listesi';
include __DIR__ . '/../layouts/header.php';
?>
<style>
    body { background-color: #f4f6f9 !important; color: #212529 !important; }
    .card { background-color: #fff !important; }
    .table { color: #212529 !important; }
    .text-muted { color: #6c757d !important; }
    h4 { color: #212529 !important; }
</style>

<?php include __DIR__ . '/navbar.php'; ?>
<div class="container">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h4>
                Misafir Listesi
                <span class="badge bg-secondary fs-6 align-middle ms-2"><?= count($guests) ?> Kişi</span>
            </h4>
        </div>
        <div class="col-md-6">
            <form method="GET" class="d-flex gap-2 justify-content-md-end">
                <input type="text" name="q" class="form-control w-50" placeholder="İsim, şirket veya e-posta ara..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Ara</button>
                <?php if(!empty($searchQuery)): ?>
                    <a href="guests.php" class="btn btn-outline-secondary">Temizle</a>
                <?php endif; ?>
                
                <a href="?export=excel&q=<?= htmlspecialchars($searchQuery) ?>" class="btn btn-success text-nowrap">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </form>
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
                            <th>Durum</th>
                            <th>Giriş</th> 
                            <th class="text-end pe-3">İşlemler</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($guests)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-search fa-2x mb-3"></i><br>
                                    Kayıt bulunamadı.
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
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($guest['company'] ?? '-') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($guest['title'] ?? '') ?></small>
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
                                    <?= $guest['check_in_at'] ? date('H:i', strtotime($guest['check_in_at'])) : '-' ?>
                                </td>

                                <td class="text-end pe-3">
                                    <a href="print-badge.php?id=<?= $guest['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Yaka Kartı Yazdır">
                                        <i class="fa-solid fa-id-card-clip"></i> <span class="d-none d-lg-inline">Yaka Kartı</span>
                                    </a>
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