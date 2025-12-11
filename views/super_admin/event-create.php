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

// Form Gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verileri Al
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $date = $_POST['date'];
    $location = trim($_POST['location']);
    
    // Müşteri Bilgileri
    $client_name = trim($_POST['client_name']);
    $client_user = trim($_POST['client_username']);
    $client_pass = $_POST['client_password'];

    // 2. Basit Doğrulamalar
    if (empty($title) || empty($slug) || empty($client_user) || empty($client_pass)) {
        $error = "Lütfen zorunlu alanları doldurun.";
    } else {
        // 3. Slug ve Kullanıcı Adı Müsait mi?
        $checkSlug = $db->fetch("SELECT id FROM events WHERE slug = ?", [$slug]);
        $checkUser = $db->fetch("SELECT id FROM users WHERE username = ?", [$client_user]);

        if ($checkSlug) {
            $error = "Bu URL (Slug) zaten başka bir etkinlikte kullanılıyor.";
        } elseif ($checkUser) {
            $error = "Bu kullanıcı adı zaten alınmış.";
        } else {
            try {
                // --- İŞLEM BAŞLIYOR ---
                
                // A) Müşteri Kullanıcısını Oluştur
                $hash = password_hash($client_pass, PASSWORD_DEFAULT);
                $db->query("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)", 
                    [$client_user, $hash, $client_name, 'client_admin']);
                
                $newUserId = $db->lastInsertId(); // Yeni oluşan ID'yi al

                // B) Etkinliği Oluştur ve Kullanıcıya Bağla
                // Varsayılan ayarlar (JSON)
                $defaultSettings = json_encode([
                    'primary_color' => '#0d6efd',
                    'allow_uploads' => true,
                    'gamification' => true
                ]);

                $sqlEvent = "INSERT INTO events (user_id, slug, title, event_date, location, settings_json, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $db->query($sqlEvent, [$newUserId, $slug, $title, $date, $location, $defaultSettings, 'active']);

                // C) Klasör Oluştur (Permission Yönetimi)
                // public/uploads/slug seklinde klasör açar
                $uploadDir = __DIR__ . '/../../public/uploads/' . $slug;
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                    // İçine güvenli bir index dosyası koyalım (Listelemeyi engellemek için)
                    file_put_contents($uploadDir . '/index.html', ''); 
                }

                $message = "Etkinlik ve Müşteri Hesabı Başarıyla Oluşturuldu! 🎉";
                
            } catch (Exception $e) {
                $error = "Sistem Hatası: " . $e->getMessage();
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
                                        <div class="form-text">Etkinlik adı girildikçe otomatik oluşur. Türkçe karakter içermez.</div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tarih & Saat</label>
                                            <input type="datetime-local" name="date" class="form-control" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Konum / Mekan</label>
                                            <input type="text" name="location" class="form-control" placeholder="Örn: Swissotel Bosphorus">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card shadow-sm border-primary">
                                <div class="card-header bg-primary text-white fw-bold">2. Müşteri (Admin) Hesabı</div>
                                <div class="card-body bg-white">
                                    <div class="alert alert-info py-2" style="font-size: 0.9rem;">
                                        <i class="fa-solid fa-circle-info"></i> Bu bilgilerle müşteri kendi paneline giriş yapacak.
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Firma / Yetkili Adı</label>
                                        <input type="text" name="client_name" class="form-control" placeholder="Örn: Teknosa İK" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Kullanıcı Adı</label>
                                        <input type="text" name="client_username" class="form-control" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Şifre Belirle</label>
                                        <input type="text" name="client_password" class="form-control" value="<?= substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6); ?>" required>
                                        <div class="form-text">Otomatik önerilen şifreyi değiştirebilirsiniz.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 py-3 mt-3 shadow fw-bold">
                                <i class="fa-solid fa-check-circle me-2"></i> ETKİNLİĞİ OLUŞTUR
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.getElementById('eventTitle').addEventListener('input', function() {
            var title = this.value;
            var slug = title.toLowerCase()
                .replace(/ğ/g, 'g')
                .replace(/ü/g, 'u')
                .replace(/ş/g, 's')
                .replace(/ı/g, 'i')
                .replace(/ö/g, 'o')
                .replace(/ç/g, 'c')
                .replace(/[^a-z0-9\s-]/g, '') // Harf rakam dışındakileri sil
                .replace(/\s+/g, '-')         // Boşlukları tire yap
                .replace(/^-+|-+$/g, '');     // Baştaki sondaki tireleri sil
            
            document.getElementById('eventSlug').value = slug;
        });
    </script>

</body>
</html>