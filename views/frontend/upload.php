<?php
// views/frontend/upload.php
$action = 'paylas';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Language.php';

$db = new Database();

// Etkinlik bilgisini al
if (!isset($event) || empty($event)) {
    // index.php'den gelmediyse URL'den bulmaya çalış
    if (isset($_GET['slug'])) { 
        $event = $db->fetch("SELECT * FROM events WHERE slug = ?", [$_GET['slug']]); 
    }
    // Hâlâ yoksa hata ver
    if (!$event) die("Etkinlik verisi bulunamadı.");
}

$settings = json_decode($event['settings_json'], true) ?? [];
$primaryColor = $settings['primary_color'] ?? '#0d6efd';
$message = ''; 
$error = '';

// Giriş İşlemi
if (isset($_POST['login_guest'])) {
    $email = trim($_POST['email']);
    $guest = $db->fetch("SELECT * FROM guests WHERE event_id = ? AND email = ?", [$event['id'], $email]);
    if ($guest) { 
        $_SESSION['guest_id'] = $guest['id']; 
        $_SESSION['guest_name'] = $guest['full_name']; 
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?')); 
        exit; 
    } else { 
        $error = "Kayıt bulunamadı."; 
    }
}

// FOTOĞRAF YÜKLEME İŞLEMİ (BURASI DÜZELTİLDİ)
if (isset($_POST['upload_media']) && isset($_SESSION['guest_id'])) {
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
        
        $uploadDir = __DIR__ . '/../../public/uploads/' . $event['slug'] . '/';
        
        // Klasör yoksa oluştur
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExt, $allowed)) {
            // Dosyaya benzersiz isim ver: img_ZAMAN_RASTGELE.jpg
            $newFileName = 'img_' . time() . '_' . rand(1000,9999) . '.' . $fileExt;
            $targetPath = $uploadDir . $newFileName;
            
            // Veritabanı için yol (public/uploads/...)
            $dbPath = 'uploads/' . $event['slug'] . '/' . $newFileName;

            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $targetPath)) {
                // Veritabanına kaydet
                $db->query("INSERT INTO media_uploads (event_id, guest_id, file_path, is_approved) VALUES (?, ?, ?, 0)", 
                           [$event['id'], $_SESSION['guest_id'], $dbPath]);
                
                $message = "Fotoğraf başarıyla gönderildi! Moderatör onayından sonra yayında olacak.";
            } else {
                $error = "Dosya sunucuya taşınırken hata oluştu. Klasör izinlerini kontrol edin.";
            }
        } else {
            $error = "Sadece JPG, PNG ve GIF formatları kabul edilir.";
        }
    } else {
        $error = "Dosya seçilmedi veya yükleme hatası oluştu.";
    }
}

$isLoggedIn = isset($_SESSION['guest_id']);
?>

<!DOCTYPE html>
<html lang="<?= isset($currentLang) ? $currentLang : 'tr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $lang['share_memory'] ?> - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body { 
            background-color: #111; 
            color: #fff; /* Genel yazı rengi beyaz */
            min-height: 100vh; 
            display: flex; flex-direction: column; font-family: 'Segoe UI', sans-serif; 
        }
        .container-center { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 20px; text-align: center; }
        
        .upload-zone { 
            border: 2px dashed rgba(255,255,255,0.5); /* Çerçeveyi belirginleştir */
            border-radius: 20px; 
            padding: 40px 20px; 
            margin-bottom: 20px; 
            background: rgba(255,255,255,0.08); /* Hafif aydınlık zemin */
            cursor: pointer; 
            transition: 0.3s; 
        }
        .upload-zone:hover, .upload-zone.active { border-color: <?= $primaryColor ?>; background: rgba(255,255,255,0.15); }
        
        /* Upload İkonu ve Yazısı */
        .upload-icon { color: #ddd; } 
        .upload-text { color: #fff; font-weight: 500; font-size: 1.1rem; margin-top: 10px; }
        
        .btn-main { background-color: <?= $primaryColor ?>; border: none; padding: 12px; font-size: 1.1rem; border-radius: 10px; width: 100%; font-weight: bold; color:white; transition: 0.3s; }
        .btn-main:hover { filter: brightness(1.1); }
        
        .glass-card { 
            background: rgba(255,255,255,0.1); 
            backdrop-filter: blur(10px); 
            padding: 30px; 
            border-radius: 20px; 
            border: 1px solid rgba(255,255,255,0.2); /* Kenarlık belirginleşti */
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
        }
        
        /* Inputlar */
        .form-control {
            background-color: #222 !important;
            border: 1px solid #444 !important;
            color: #fff !important;
        }
        .form-control::placeholder { color: #bbb !important; }

        .loading-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; display:none; flex-direction:column; align-items:center; justify-content:center; }
        
        /* Lang Switcher */
        .lang-btn { background: rgba(255,255,255,0.2); color: white; padding: 2px 8px; border-radius: 15px; text-decoration: none; font-size: 0.75rem; border: 1px solid rgba(255,255,255,0.3); }
        .lang-btn.active { background: <?= $primaryColor ?>; border-color: <?= $primaryColor ?>; }
        
        #fileInput { display: none; }
        .preview-img { max-width: 100%; max-height: 300px; border-radius: 10px; display: none; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
    </style>
</head>
<body>
    
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="mt-3 text-white fw-bold"><?= $lang['processing'] ?></div>
    </div>

    <div class="p-3 d-flex align-items-center bg-dark border-bottom border-secondary">
        <?php 
            $baseFolder = dirname($_SERVER['SCRIPT_NAME']);
            if ($baseFolder === '/' || $baseFolder === '\\') $baseFolder = '';
            $homeLink = $baseFolder . '/' . $event['slug'];
        ?>
        <a href="<?= $homeLink ?>" class="text-white text-decoration-none me-3"><i class="fa-solid fa-arrow-left"></i></a>
        
        <span class="fw-bold text-white text-truncate" style="max-width: 150px;"><?= htmlspecialchars($event['title']) ?></span>
        
        <div class="ms-auto d-flex gap-2">
            <a href="?lang=tr" class="lang-btn <?= ($currentLang ?? 'tr') == 'tr' ? 'active' : '' ?>">TR</a>
            <a href="?lang=en" class="lang-btn <?= ($currentLang ?? 'tr') == 'en' ? 'active' : '' ?>">EN</a>
        </div>
    </div>

    <div class="container-center">
        <?php if($error): ?>
            <div class="alert alert-danger animate__animated animate__shakeX mb-4"><?= $error ?></div>
        <?php endif; ?>

        <?php if(!$isLoggedIn): ?>
            <div class="glass-card animate__animated animate__fadeInUp">
                <div class="mb-4"><i class="fa-solid fa-user-circle fa-4x text-white-50"></i></div>
                <h3 class="mb-3 fw-bold text-white"><?= $lang['welcome_user'] ?></h3>
                <p class="text-white-50 mb-4"><?= $lang['login_prompt'] ?></p>
                
                <form method="POST">
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control form-control-lg text-center" placeholder="<?= $lang['email'] ?>" required>
                    </div>
                    <button type="submit" name="login_guest" class="btn btn-main shadow-lg"><?= $lang['login_btn'] ?></button>
                </form>
                
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                    <a href="<?= $homeLink ?>/kayit" class="text-white text-decoration-none fw-bold small">
                        <?= $lang['register'] ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

        <?php else: ?>
            
            <?php if($message): ?>
                <div class="glass-card animate__animated animate__bounceIn">
                    <div class="mb-3"><i class="fa-solid fa-circle-check text-success fa-4x"></i></div>
                    <h3 class="fw-bold mb-3 text-white"><?= $lang['upload_great'] ?></h3>
                    <p class="text-white-50 mb-4"><?= $message ?></p>
                    <a href="" class="btn btn-outline-light w-100 py-3 fw-bold rounded-pill"><?= $lang['new_photo'] ?></a>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" id="uploadForm" onsubmit="document.getElementById('loadingOverlay').style.display='flex'">
                    <input type="hidden" name="upload_media" value="1">
                    
                    <h3 class="mb-4 fw-light text-white"><?= $lang['leave_memory'] ?></h3>
                    
                    <div class="upload-zone" id="dropZone">
                        <div id="placeholder">
                            <i class="fa-solid fa-camera fa-4x mb-3 upload-icon"></i>
                            <p class="m-0 upload-text"><?= $lang['upload_placeholder'] ?></p>
                        </div>
                        <img id="preview" class="preview-img" alt="Önizleme">
                    </div>
                    
                    <input type="file" name="media_file" id="fileInput" accept="image/*" required>
                    
                    <button type="submit" class="btn btn-main shadow-lg">
                        <i class="fa-solid fa-paper-plane me-2"></i> <?= $lang['send_btn'] ?>
                    </button>
                </form>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <?php if($isLoggedIn && empty($message)): ?>
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const preview = document.getElementById('preview');
        const placeholder = document.getElementById('placeholder');

        if(dropZone){
            dropZone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                        dropZone.classList.add('active');
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    </script>
    <?php endif; ?>

    <?php 
    $navBottomPath = __DIR__ . '/navbar_bottom.php';
    if(file_exists($navBottomPath)) include $navBottomPath; 
    ?>
</body>
</html>