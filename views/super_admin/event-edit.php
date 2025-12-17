<?php
// views/super_admin/event-edit.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('super_admin');
$db = new Database();

$id = $_GET['id'] ?? 0;
$event = $db->fetch("SELECT * FROM events WHERE id = ?", [$id]);

if (!$event) die("Etkinlik bulunamadı.");

$settings = json_decode($event['settings_json'], true) ?? [];
$message = '';

// --- KAYDETME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title = trim($_POST['title']);
    $date = $_POST['event_date'];
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $settings['primary_color'] = $_POST['primary_color']; 
    
    // Klasör Hazırlığı
    $uploadBase = __DIR__ . '/../../public/uploads/' . $event['slug'] . '/assets/';
    if (!file_exists($uploadBase)) mkdir($uploadBase, 0777, true);

    // SQL Güncelleme Hazırlığı (Başlangıç değerleri)
    $bannerPathSQL = $event['banner_image']; 

    // 1. BANNER YÜKLEME
    if (!empty($_FILES['banner_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
            $bannerName = 'banner_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadBase . $bannerName);
            $bannerPathSQL = 'uploads/' . $event['slug'] . '/assets/' . $bannerName;
        }
    }

    // 2. LOGO YÜKLEME
    if (!empty($_FILES['custom_logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['custom_logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
            $logoName = 'logo_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['custom_logo']['tmp_name'], $uploadBase . $logoName);
            $settings['custom_logo_path'] = 'uploads/' . $event['slug'] . '/assets/' . $logoName;
        }
    }

    // 3. ÇERÇEVE YÜKLEME (YENİLENMİŞ)
    
    // 3a. Yatay Çerçeve (Horizontal)
    if (!empty($_FILES['custom_frame_h']['name'])) {
        $ext = strtolower(pathinfo($_FILES['custom_frame_h']['name'], PATHINFO_EXTENSION));
        if ($ext == 'png') {
            $frameName = 'frame_h_' . time() . '.png';
            move_uploaded_file($_FILES['custom_frame_h']['tmp_name'], $uploadBase . $frameName);
            $settings['custom_frame_h_path'] = 'uploads/' . $event['slug'] . '/assets/' . $frameName;
        }
    }

    // 3b. Dikey Çerçeve (Vertical)
    if (!empty($_FILES['custom_frame_v']['name'])) {
        $ext = strtolower(pathinfo($_FILES['custom_frame_v']['name'], PATHINFO_EXTENSION));
        if ($ext == 'png') {
            $frameName = 'frame_v_' . time() . '.png';
            move_uploaded_file($_FILES['custom_frame_v']['tmp_name'], $uploadBase . $frameName);
            $settings['custom_frame_v_path'] = 'uploads/' . $event['slug'] . '/assets/' . $frameName;
        }
    }

    $jsonSettings = json_encode($settings);
    
    // SQL Güncelle
    $sql = "UPDATE events SET title=?, event_date=?, location=?, description=?, status=?, settings_json=?, banner_image=? WHERE id=?";
    $db->query($sql, [$title, $date, $location, $description, $status, $jsonSettings, $bannerPathSQL, $id]);
    
    $message = "Güncelleme Başarılı! ✅";
    
    // Sayfayı yenile ki yeni veriler gelsin
    header("Refresh:1"); 
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Etkinlik Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between mb-4">
                    <h3>Etkinlik Ayarları</h3>
                    <a href="dashboard.php" class="btn btn-secondary">Geri Dön</a>
                </div>

                <?php if($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="card shadow p-4">
                    
                    <h5 class="text-primary mb-3">📝 Genel Bilgiler</h5>
                    <div class="mb-3">
                        <label>Etkinlik Adı</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Ana Banner (Kapak Görseli)</label>
                        <input type="file" name="banner_image" class="form-control">
                        <?php if(!empty($event['banner_image'])): ?>
                            <div class="mt-2">
                                <img src="../../public/<?= $event['banner_image'] ?>" height="100" class="rounded border">
                                <small class="d-block text-muted">Mevcut Banner</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Tarih</label>
                            <input type="datetime-local" name="event_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label>Konum</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($event['location']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Durum</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $event['status'] == 'active' ? 'selected' : '' ?>>Yayında</option>
                            <option value="draft" <?= $event['status'] == 'draft' ? 'selected' : '' ?>>Taslak (Gizli)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Açıklama</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($event['description']) ?></textarea>
                    </div>

                    <hr>

                    <h5 class="text-primary mb-3">🎨 Görsel Özelleştirme</h5>

                    <div class="mb-3">
                        <label>Ana Renk</label>
                        <input type="color" name="primary_color" class="form-control form-control-color w-100" value="<?= $settings['primary_color'] ?? '#0d6efd' ?>">
                    </div>

                    <div class="mb-3">
                        <label>Firma Logosu (Opsiyonel)</label>
                        <input type="file" name="custom_logo" class="form-control">
                        <?php if(isset($settings['custom_logo_path'])): ?>
                            <small class="text-success">✔ Yüklü logo var.</small>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Yatay Çerçeve (Landscape)</label>
                            <input type="file" name="custom_frame_h" class="form-control" accept="image/png">
                            <div class="form-text">Örn: 1920x1080 px, Şeffaf PNG</div>
                            <?php if(isset($settings['custom_frame_h_path'])): ?>
                                <small class="text-success">✔ Yüklü yatay çerçeve var.</small>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Dikey Çerçeve (Portrait)</label>
                            <input type="file" name="custom_frame_v" class="form-control" accept="image/png">
                            <div class="form-text">Örn: 1080x1920 px, Şeffaf PNG</div>
                            <?php if(isset($settings['custom_frame_v_path'])): ?>
                                <small class="text-success">✔ Yüklü dikey çerçeve var.</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg mt-3">Kaydet ve Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>