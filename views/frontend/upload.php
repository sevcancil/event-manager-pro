<?php
// views/frontend/upload.php
$action = 'paylas';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!extension_loaded('gd')) die("GD Library eksik.");

// --- AYARLARI ÇEK ---
$settings = json_decode($event['settings_json'], true) ?? [];
// Admin panelinden seçilen renk varsa onu kullan, yoksa mavi yap
$primaryColor = $settings['primary_color'] ?? '#0d6efd';

$message = '';
$error = '';

// Flash Mesajı
if (isset($_SESSION['flash_success'])) {
    $message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Giriş İşlemi
if (isset($_POST['login_guest'])) {
    $email = trim($_POST['email']);
    $guest = $db->fetch("SELECT * FROM guests WHERE event_id = ? AND email = ?", [$event['id'], $email]);
    if ($guest) {
        $_SESSION['guest_id'] = $guest['id'];
        $_SESSION['guest_name'] = $guest['full_name'];
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $error = "Kayıt bulunamadı.";
    }
}

// Fotoğraf Yükleme
if (isset($_POST['upload_media']) && isset($_SESSION['guest_id'])) {
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $targetDir = __DIR__ . '/../../public/uploads/' . $event['slug'] . '/';
            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
            
            $newFilename = uniqid('img_') . '.jpg';
            $destination = $targetDir . $newFilename;

            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $destination)) {
                
                // --- FONKSİYONA AYARLARI DA GÖNDERİYORUZ ---
                if (processImage($destination, $settings)) {
                    
                    $dbPath = 'uploads/' . $event['slug'] . '/' . $newFilename;
                    $db->query("INSERT INTO media_uploads (event_id, guest_id, file_path, file_type, is_approved) VALUES (?, ?, ?, ?, ?)", 
                               [$event['id'], $_SESSION['guest_id'], $dbPath, 'image', 0]);

                    $_SESSION['flash_success'] = "Fotoğraf başarıyla gönderildi!";
                    header("Location: " . $_SERVER['REQUEST_URI']); 
                    exit;
                } else { $error = "İşleme hatası."; }
            } else { $error = "Yükleme hatası."; }
        } else { $error = "Sadece Resim."; }
    }
}

$isLoggedIn = isset($_SESSION['guest_id']);

/**
 * GÖRÜNTÜ İŞLEME MOTORU
 */
function processImage($filePath, $settings) {
    
    // 1. Çerçeve Yolunu Belirle
    // Varsayılan çerçeve
    $framePath = __DIR__ . '/../../public/assets/img/frame.png';

    // EĞER ADMIN PANELİNDEN ÖZEL ÇERÇEVE YÜKLENDİYSE ONU KULLAN
    if (isset($settings['custom_frame_path']) && !empty($settings['custom_frame_path'])) {
        $customFrame = __DIR__ . '/../../public/' . $settings['custom_frame_path'];
        if (file_exists($customFrame)) {
            $framePath = $customFrame;
        }
    }

    // Çerçeve yoksa işlem yapma, düz kaydet
    if (!file_exists($framePath)) return true;

    // 2. Çerçeveyi Yükle
    $frame = imagecreatefrompng($framePath);
    imagealphablending($frame, true);
    imagesavealpha($frame, true);
    $frameW = imagesx($frame);
    $frameH = imagesy($frame);

    // 3. Fotoğrafı Yükle
    $info = getimagesize($filePath);
    switch ($info['mime']) {
        case 'image/jpeg': $photo = imagecreatefromjpeg($filePath); break;
        case 'image/png':  $photo = imagecreatefrompng($filePath); break;
        default: return false;
    }

    // 4. Oryantasyon Düzeltme
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($filePath);
        if ($exif && isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: $photo = imagerotate($photo, 180, 0); break;
                case 6: $photo = imagerotate($photo, -90, 0); break;
                case 8: $photo = imagerotate($photo, 90, 0); break;
            }
        }
    }
    $photoW = imagesx($photo);
    $photoH = imagesy($photo);

    // 5. Orantılı Sığdırma (Aspect Fit - Siyah Kenarlık)
    $frameRatio = $frameW / $frameH;
    $photoRatio = $photoW / $photoH;
    $dstX = 0; $dstY = 0; $dstW = $frameW; $dstH = $frameH;

    if ($photoRatio > $frameRatio) {
        $dstW = $frameW;
        $dstH = floor($frameW / $photoRatio);
        $dstY = ($frameH - $dstH) / 2;
    } else {
        $dstH = $frameH;
        $dstW = floor($frameH * $photoRatio);
        $dstX = ($frameW - $dstW) / 2;
    }

    // 6. Birleştirme
    $finalImage = imagecreatetruecolor($frameW, $frameH);
    $black = imagecolorallocate($finalImage, 0, 0, 0);
    imagefill($finalImage, 0, 0, $black);

    imagecopyresampled($finalImage, $photo, $dstX, $dstY, 0, 0, $dstW, $dstH, $photoW, $photoH);
    imagecopy($finalImage, $frame, 0, 0, 0, 0, $frameW, $frameH);

    imagejpeg($finalImage, $filePath, 90);
    
    imagedestroy($frame);
    imagedestroy($photo);
    imagedestroy($finalImage);
    return true;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Anı Paylaş - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #111; color: white; min-height: 100vh; display: flex; flex-direction: column; }
        .container-center { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 20px; text-align: center; }
        
        /* ÖNEMLİ: Seçilen rengi burada CSS değişkeni olarak kullanabiliriz veya direkt PHP ile basarız */
        .upload-zone.active { border-color: <?= $primaryColor ?>; background: rgba(255,255,255,0.1); }
        .btn-main { background-color: <?= $primaryColor ?>; border: none; padding: 12px; font-size: 1.1rem; border-radius: 10px; width: 100%; font-weight: bold; color:white; }
        
        .upload-zone { border: 2px dashed rgba(255,255,255,0.3); border-radius: 20px; padding: 40px 20px; margin-bottom: 20px; background: rgba(255,255,255,0.05); cursor: pointer; }
        #fileInput { display: none; }
        .preview-img { max-width: 100%; max-height: 300px; border-radius: 10px; display: none; margin-bottom: 20px; }
        .glass-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); }
        .loading-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; display:none; flex-direction:column; align-items:center; justify-content:center; }
    </style>
</head>
<body>
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
        <div class="mt-3 text-white">Fotoğraf işleniyor...</div>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center bg-black border-bottom border-dark">
        <a href="../<?= $event['slug'] ?>" class="text-white text-decoration-none"><i class="fa-solid fa-arrow-left"></i> Geri</a>
        <span class="fw-bold text-truncate" style="max-width: 200px;"><?= htmlspecialchars($event['title']) ?></span>
        <?php if($isLoggedIn): ?>
            <span class="badge bg-secondary"><i class="fa-solid fa-user me-1"></i> <?= htmlspecialchars($_SESSION['guest_name']) ?></span>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
    </div>

    <div class="container-center">
        <?php if($error): ?>
            <div class="alert alert-danger animate__animated animate__shakeX"><?= $error ?></div>
        <?php endif; ?>

        <?php if(!$isLoggedIn): ?>
            <div class="glass-card animate__animated animate__fadeIn">
                <div class="mb-4"><span class="fa-stack fa-2x"><i class="fa-solid fa-circle fa-stack-2x text-white-50"></i><i class="fa-solid fa-camera fa-stack-1x text-dark"></i></span></div>
                <h3 class="mb-3 fw-bold">Hoş Geldiniz! 👋</h3>
                <p class="text-white-50 mb-4">Lütfen kayıt olduğunuz e-posta adresi ile giriş yapın.</p>
                <form method="POST">
                    <div class="mb-3"><input type="email" name="email" class="form-control form-control-lg bg-dark text-white border-secondary text-center" placeholder="E-Posta Adresi" required></div>
                    <button type="submit" name="login_guest" class="btn btn-main shadow-lg">Giriş Yap</button>
                </form>
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                    <a href="../<?= $event['slug'] ?>/kayit" class="text-white text-decoration-none fw-bold small">Kayıt Ol</a>
                </div>
            </div>
        <?php else: ?>
            <?php if($message): ?>
                <div class="glass-card animate__animated animate__bounceIn">
                    <div class="mb-3"><i class="fa-solid fa-wand-magic-sparkles text-warning fa-4x"></i></div>
                    <h3 class="fw-bold mb-3">Harika!</h3>
                    <p class="text-white-50 mb-4"><?= $message ?></p>
                    <a href="" class="btn btn-outline-light w-100 py-3 fw-bold rounded-pill">Yeni Fotoğraf</a>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" id="uploadForm" onsubmit="document.getElementById('loadingOverlay').style.display='flex'">
                    <input type="hidden" name="upload_media" value="1">
                    <h3 class="mb-4 fw-light">Bir Anı Bırak ✨</h3>
                    <div class="upload-zone" id="dropZone">
                        <div id="placeholder">
                            <i class="fa-solid fa-camera fa-4x mb-3 text-secondary"></i>
                            <p class="text-muted m-0">Fotoğraf Çek veya Seç</p>
                        </div>
                        <img id="preview" class="preview-img" alt="Önizleme">
                    </div>
                    <input type="file" name="media_file" id="fileInput" accept="image/*" required>
                    <button type="submit" class="btn btn-main shadow"><i class="fa-solid fa-paper-plane me-2"></i> GÖNDER</button>
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
    <?php include __DIR__ . '/navbar_bottom.php'; ?>
</body>
</html>