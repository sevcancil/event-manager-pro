<?php
// views/super_admin/dashboard.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

// Güvenlik: Sadece Super Admin girebilir!
$auth = new Auth();
$auth->requireRole('super_admin');

$db = new Database();

// --- SİLME İŞLEMİ (DELETE) ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // 1. Önce etkinliğin "slug" bilgisini al (Klasörü silmek için)
    $eventToDelete = $db->fetch("SELECT slug FROM events WHERE id = ?", [$id]);

    if ($eventToDelete) {
        // 2. Veritabanından Sil (Cascading ayarlıysa bağlı resimler de silinir)
        // Eğer veritabanında "ON DELETE CASCADE" yoksa manuel silmek gerekebilir.
        // Biz şimdilik direkt events tablosundan siliyoruz.
        $db->query("DELETE FROM events WHERE id = ?", [$id]);

        // 3. Klasörü ve Dosyaları Sil (Temizlik)
        $folderPath = __DIR__ . '/../../public/uploads/' . $eventToDelete['slug'];
        deleteFolder($folderPath);

        // Sayfayı yenile (URL'deki ?delete paramtresini temizle)
        header("Location: dashboard.php");
        exit;
    }
}

// Tüm etkinlikleri veritabanından çek
$sql = "SELECT events.*, users.full_name as client_name 
        FROM events 
        JOIN users ON events.user_id = users.id 
        ORDER BY event_date DESC";
$events = $db->fetchAll($sql);

// Klasör silme fonksiyonu (Yardımcı)
function deleteFolder($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? deleteFolder("$dir/$file") : unlink("$dir/$file");
    }
    rmdir($dir);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli - Event Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-header { background: #fff; padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .stat-card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .table-custom th { background-color: #f8f9fa; color: #495057; }
    </style>
</head>
<body style="background-color: #f4f6f9;">

    <div class="dashboard-header">
        <h4 class="m-0 text-primary"><i class="fa-solid fa-calendar-check me-2"></i>Etkinlik Yönetimi</h4>
        <div>
            <span class="me-3 text-muted">Hoş geldin, <b><?= htmlspecialchars($_SESSION['full_name']) ?></b></span>
            <a href="../../logout.php" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Çıkış</a>
        </div>
    </div>

    <div class="container mt-4">
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Toplam Etkinlik</h6>
                            <h3><?= count($events) ?></h3>
                        </div>
                        <i class="fa-solid fa-calendar fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                 <div class="card stat-card p-3" onclick="window.location.href='users.php'" style="cursor: pointer;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Müşteri Yönetimi</h6>
                            <h3 class="text-success"><i class="fa-solid fa-users"></i></h3>
                        </div>
                        <i class="fa-solid fa-user-tie fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0">Etkinlik Listesi</h5>
                <a href="event-create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Yeni Etkinlik Oluştur</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-custom m-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Etkinlik Adı</th>
                                <th>Müşteri</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th class="text-end pe-4">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-folder-open fa-3x mb-3"></i><br>
                                        Henüz hiç etkinlik oluşturulmamış.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        <a href="../../public/<?= $event['slug'] ?>" target="_blank" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($event['title']) ?> <i class="fa-solid fa-arrow-up-right-from-square small text-muted ms-1"></i>
                                        </a>
                                        <br><small class="text-muted">/<?= htmlspecialchars($event['slug']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($event['client_name']) ?></td>
                                    <td><?= date('d.m.Y H:i', strtotime($event['event_date'])) ?></td>
                                    <td>
                                        <?php if($event['status'] == 'active'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php elseif($event['status'] == 'completed'): ?>
                                            <span class="badge bg-secondary">Tamamlandı</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Taslak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="event-edit.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-warning text-dark me-1" title="Düzenle">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        
                                        <a href="?delete=<?= $event['id'] ?>" class="btn btn-sm btn-outline-danger" title="Sil" onclick="return confirm('Bu etkinliği ve tüm fotoğraflarını silmek istediğinize emin misiniz?');">
                                            <i class="fa-solid fa-trash"></i>
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

</body>
</html>