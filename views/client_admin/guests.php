<?php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];

$event = $db->fetch("SELECT * FROM events WHERE user_id = ?", [$userId]);

// --- EXCEL İNDİRME ---
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $guests = $db->fetchAll("SELECT * FROM guests WHERE event_id = ? ORDER BY created_at DESC", [$event['id']]);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Misafir_Listesi_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

    // Başlığa "Giriş Saati" ekledik
    fputcsv($output, ['ID', 'Ad Soyad', 'E-Posta', 'Telefon', 'Durum', 'Giriş Saati (Check-in)', 'Kayıt Olma Tarihi'], ";");

    foreach ($guests as $guest) {
        $status = ($guest['check_in_status'] == 1) ? 'İçeride' : 'Gelmedi';
        // Giriş saati var mı? Varsa formatla, yoksa tire koy.
        $checkInTime = $guest['check_in_at'] ? date('H:i:s', strtotime($guest['check_in_at'])) : '-';
        
        fputcsv($output, [
            $guest['id'],
            $guest['full_name'],
            $guest['email'],
            $guest['phone'],
            $status,
            $checkInTime, // <--- Yeni Veri
            $guest['created_at']
        ], ";");
    }
    fclose($output);
    exit;
}

// --- HTML LİSTELEME ---
$guests = $db->fetchAll("SELECT * FROM guests WHERE event_id = ? ORDER BY created_at DESC", [$event['id']]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Misafir Listesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Medya Yönetimi</a></li>
                    <li class="nav-item"><a class="nav-link active" href="guests.php">Misafir Listesi</a></li>
                    <li class="nav-item"><a class="nav-link" href="checkin.php">Kapı Kontrol</a></li>
                    <li class="nav-item"><a class="nav-link" href="raffle.php">Çekiliş</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3"><?= htmlspecialchars($event['title']) ?></span>
                    <a href="../../logout.php" class="btn btn-danger btn-sm">Çıkış</a>
                </div>
            </div>
        </div>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
                                <th>İletişim</th>
                                <th>Durum</th>
                                <th>Giriş Saati</th> <th>Kayıt Tarihi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($guests)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Henüz kimse kayıt olmamış.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($guests as $index => $guest): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted"><?= $index + 1 ?></td>
                                    <td class="fw-bold">
                                        <?= htmlspecialchars($guest['full_name']) ?>
                                        <br>
                                        <small class="text-muted fw-normal" style="font-size:0.75rem">QR: <?= $guest['qr_code'] ?></small>
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

</body>
</html>