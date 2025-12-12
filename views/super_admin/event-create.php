<?php
// views/super_admin/event-create.php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

// Güvenlik Kontrolü
$auth = new Auth();
$auth->requireRole('super_admin');

$db = new Database();
$message = '';
$error = '';

// Mevcut Müşterileri Çek (Dropdown için)
$existingClients = $db->fetchAll("SELECT id, username, full_name FROM users WHERE role = 'client_admin' ORDER BY full_name ASC");

// Form Gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Etkinlik Bilgileri
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $startDate = $_POST['date'];
    $endDate = $_POST['end_date'];
    $location = trim($_POST['location']);
    
    // Müşteri Seçimi (Yeni mi Mevcut mu?)
    $clientMode = $_POST['client_mode']; // 'new' veya 'existing'
    $finalUserId = 0;

    // Basit Doğrulamalar
    if (empty($title) || empty($slug) || empty($startDate) || empty($endDate)) {
        $error = "Lütfen etkinlik bilgilerini eksiksiz doldurun.";
    } elseif (strtotime($endDate) < strtotime($startDate)) {
        $error = "Bitiş tarihi, başlangıç tarihinden önce olamaz.";
    } else {
        
        // Slug Kontrolü (Herkes için benzersiz olmalı)
        $checkSlug = $db->fetch("SELECT id FROM events WHERE slug = ?", [$slug]);
        if ($checkSlug) {
            $error = "Bu URL (Slug) zaten başka bir etkinlikte kullanılıyor.";
        } else {
            
            try {
                // --- SENARYO A: YENİ MÜŞTERİ OLUŞTURULACAK ---
                if ($clientMode === 'new') {
                    $client_name = trim($_POST['client_name']);
                    $client_user = trim($_POST['client_username']);
                    $client_pass = $_POST['client_password'];

                    if (empty($client_user) || empty($client_pass)) {
                        throw new Exception("Yeni müşteri için kullanıcı adı ve şifre zorunludur.");
                    }

                    // Kullanıcı adı müsait mi?
                    $checkUser = $db->fetch("SELECT id FROM users WHERE username = ?", [$client_user]);
                    if ($checkUser) {
                        throw new Exception("Bu kullanıcı adı zaten alınmış. Lütfen başka bir tane seçin veya mevcut müşteriyi seçin.");
                    }

                    // Kullanıcıyı Kaydet
                    $hash = password_hash($client_pass, PASSWORD_DEFAULT);
                    $db->query("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)", 
                        [$client_user, $hash, $client_name, 'client_admin']);
                    
                    $finalUserId = $db->lastInsertId();

                // --- SENARYO B: MEVCUT MÜŞTERİ SEÇİLDİ ---
                } else {
                    $finalUserId = $_POST['existing_user_id'];
                    if (empty($finalUserId)) {
                        throw new Exception("Lütfen listeden bir müşteri seçin.");
                    }
                }

                // --- ETKİNLİĞİ OLUŞTUR ---
                
                // Varsayılan ayarlar
                $defaultSettings = json_encode([
                    'primary_color' => '#0d6efd',
                    'allow_uploads' => true,
                    'gamification' => true
                ]);

                $sqlEvent = "INSERT INTO events (user_id, slug, title, event_date, event_end_date, location, settings_json, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $db->query($sqlEvent, [$finalUserId, $slug, $title, $startDate, $endDate, $location, $defaultSettings, 'active']);

                // Klasör Oluştur
                $uploadDir = __DIR__ . '/../../public/uploads/' . $slug;
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                    file_put_contents($uploadDir . '/index.html', ''); 
                }

                $message = "Etkinlik Başarıyla Oluşturuldu! 🎉";
                
            } catch (Exception $e) {
                $error = "Hata: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Etkinlik Oluştur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>Yeni Etkinlik Sihirbazı</h3>
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Geri Dön</a>
                </div>

                <?php if($message): ?>
                    <div class="alert alert-success"><?= $message ?> <a href="dashboard.php">Listeye Dön</a></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="" autocomplete="off">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white fw-bold">1. Etkinlik Detayları</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Etkinlik Adı <span class="text-danger">*</span></label>
                                        <input type="text" name="title" id="eventTitle" class="form-control" placeholder="Örn: Teknosa Yılbaşı Partisi" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Etkinlik URL (Slug) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">site.com/</span>
                                            <input type="text" name="slug" id="eventSlug" class="form-control" readonly style="background-color: #e9ecef;">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Başlangıç <span class="text-danger">*</span></label>
                                            <input type="datetime-local" name="date" id="startDate" class="form-control" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Bitiş <span class="text-danger">*</span></label>
                                            <input type="datetime-local" name="end_date" id="endDate" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Konum / Mekan</label>
                                        <input type="text" name="location" class="form-control" placeholder="Örn: Swissotel Bosphorus">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card shadow-sm border-primary h-100">
                                <div class="card-header bg-primary text-white fw-bold">2. Müşteri (Admin) Hesabı</div>
                                <div class="card-body bg-white">
                                    
                                    <div class="mb-4">
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="client_mode" id="modeExisting" value="existing" checked onclick="toggleClientMode()">
                                            <label class="btn btn-outline-primary" for="modeExisting">Mevcut Müşteri</label>

                                            <input type="radio" class="btn-check" name="client_mode" id="modeNew" value="new" onclick="toggleClientMode()">
                                            <label class="btn btn-outline-primary" for="modeNew">Yeni Oluştur</label>
                                        </div>
                                    </div>

                                    <div id="existingClientArea">
                                        <div class="mb-3">
                                            <label class="form-label">Müşteri Seçin</label>
                                            <select name="existing_user_id" class="form-select">
                                                <option value="">-- Listeden Seçin --</option>
                                                <?php foreach($existingClients as $client): ?>
                                                    <option value="<?= $client['id'] ?>">
                                                        <?= htmlspecialchars($client['full_name']) ?> (<?= htmlspecialchars($client['username']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Bu kullanıcı etkinlik paneline mevcut şifresiyle girebilecek.</div>
                                        </div>
                                    </div>

                                    <div id="newClientArea" style="display:none;">
                                        <div class="mb-3">
                                            <label class="form-label">Firma / Yetkili Adı</label>
                                            <input type="text" name="client_name" class="form-control" placeholder="Örn: Teknosa İK">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Kullanıcı Adı</label>
                                            <input type="text" name="client_username" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Şifre Belirle</label>
                                            <input type="text" name="client_password" class="form-control" value="<?= substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6); ?>">
                                        </div>
                                    </div>

                                    <hr>
                                    <button type="submit" class="btn btn-success w-100 py-3 mt-2 shadow fw-bold">
                                        <i class="fa-solid fa-check-circle me-2"></i> ETKİNLİĞİ OLUŞTUR
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        // Slug Oluşturucu
        document.getElementById('eventTitle').addEventListener('input', function() {
            var title = this.value;
            var slug = title.toLowerCase()
                .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
                .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
                .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/^-+|-+$/g, '');
            document.getElementById('eventSlug').value = slug;
        });

        // Tarih Kolaylaştırıcısı
        document.getElementById('startDate').addEventListener('change', function() {
            var startVal = this.value;
            if(startVal && !document.getElementById('endDate').value) {
                var date = new Date(startVal);
                date.setHours(date.getHours() + 2);
                
                var year = date.getFullYear();
                var month = String(date.getMonth() + 1).padStart(2, '0');
                var day = String(date.getDate()).padStart(2, '0');
                var hours = String(date.getHours()).padStart(2, '0');
                var minutes = String(date.getMinutes()).padStart(2, '0');
                
                document.getElementById('endDate').value = `${year}-${month}-${day}T${hours}:${minutes}`;
            }
        });

        // Müşteri Modu Değiştirici (Mevcut / Yeni)
        function toggleClientMode() {
            var isNew = document.getElementById('modeNew').checked;
            var existingArea = document.getElementById('existingClientArea');
            var newArea = document.getElementById('newClientArea');

            if (isNew) {
                existingArea.style.display = 'none';
                newArea.style.display = 'block';
                // Yeni müşteri seçildiyse inputları required yap (HTML5 validasyonu için)
                document.querySelector('[name="client_username"]').setAttribute('required', 'required');
                document.querySelector('[name="existing_user_id"]').removeAttribute('required');
            } else {
                existingArea.style.display = 'block';
                newArea.style.display = 'none';
                // Mevcut seçildiyse dropdown'u required yap
                document.querySelector('[name="client_username"]').removeAttribute('required');
                document.querySelector('[name="existing_user_id"]').setAttribute('required', 'required');
            }
        }
    </script>

</body>
</html>