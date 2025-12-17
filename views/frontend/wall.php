<?php
// views/frontend/wall.php

require_once __DIR__ . '/../../src/Database.php';
$db = new Database();

// index.php üzerinden gelindiği için $event tanımlı olmalı.
if (!isset($event)) {
    http_response_code(404);
    exit;
}

// --- 1. AJAX MODU (Canlı Veri Çekme) ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    try {
        $sql = "SELECT media_uploads.*, guests.full_name 
                FROM media_uploads 
                LEFT JOIN guests ON media_uploads.guest_id = guests.id
                WHERE media_uploads.event_id = ? AND media_uploads.is_approved = 1 
                ORDER BY media_uploads.created_at DESC LIMIT 50";
                
        $photos = $db->fetchAll($sql, [$event['id']]);
        echo json_encode(['status' => 'success', 'data' => $photos]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// --- 2. HTML MODU ---
$settings = json_decode($event['settings_json'], true);
$primaryColor = $settings['primary_color'] ?? '#0d6efd';

// URL Ayarları
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$baseFolder = dirname($_SERVER['SCRIPT_NAME']);
if ($baseFolder === '/' || $baseFolder === '\\') $baseFolder = '';

// Kök URL
$baseUrl = "$protocol://$_SERVER[HTTP_HOST]$baseFolder";
$baseUrl = str_replace('\\', '/', $baseUrl);
if (substr($baseUrl, -1) != '/') $baseUrl .= '/';

// Paylaşım Linki
$shareLink = str_replace('/akis', '/paylas', "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$shareLink = strtok($shareLink, '?');

// --- ÇERÇEVE AYARLARI (GÜNCELLENMİŞ VERSİYON) ---
// Mantık: Önce admin panelinden yüklenen özele bak, yoksa sistem klasöründeki varsayılana bak.

// 1. Admin Panelinden Yüklenenler (Veritabanı)
$dbFrameH = $settings['custom_frame_h_path'] ?? '';
$dbFrameV = $settings['custom_frame_v_path'] ?? '';
$dbFrameOld = $settings['custom_frame_path'] ?? ''; // Eski tekli yüklenen varsa yedek

// 2. Sistem Klasöründeki Varsayılanlar
$sysFrameH = 'assets/img/frame_h.png';
$sysFrameV = 'assets/img/frame_v.png';
$sysFrameOld = 'assets/img/frame.png';

// 3. YATAY (Landscape) ÇERÇEVE BELİRLEME
if (!empty($dbFrameH)) {
    $urlFrameH = $baseUrl . $dbFrameH; // Admin'den yüklenen yatay
} elseif (file_exists(__DIR__ . '/../../public/' . $sysFrameH)) {
    $urlFrameH = $baseUrl . $sysFrameH; // Sistemdeki yatay
} elseif (!empty($dbFrameOld)) {
    $urlFrameH = $baseUrl . $dbFrameOld; // Eski usul tekli (yedek)
} elseif (file_exists(__DIR__ . '/../../public/' . $sysFrameOld)) {
    $urlFrameH = $baseUrl . $sysFrameOld; // Sistemdeki eski tekli
} else {
    $urlFrameH = ''; // Hiçbiri yok
}

// 4. DİKEY (Portrait) ÇERÇEVE BELİRLEME
if (!empty($dbFrameV)) {
    $urlFrameV = $baseUrl . $dbFrameV; // Admin'den yüklenen dikey
} elseif (file_exists(__DIR__ . '/../../public/' . $sysFrameV)) {
    $urlFrameV = $baseUrl . $sysFrameV; // Sistemdeki dikey
} elseif (!empty($dbFrameOld)) {
    $urlFrameV = $baseUrl . $dbFrameOld; // Eski usul tekli (yedek)
} elseif (file_exists(__DIR__ . '/../../public/' . $sysFrameOld)) {
    $urlFrameV = $baseUrl . $sysFrameOld; // Sistemdeki eski tekli
} else {
    $urlFrameV = ''; // Hiçbiri yok
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canlı Akış - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        body { background-color: #000; overflow: hidden; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; height: 100vh; display: flex; }
        
        #slider-area { flex: 1; position: relative; background: radial-gradient(circle, #222 0%, #000 100%); display: flex; align-items: center; justify-content: center; }
        
        .slide-item { display: none; width: 100%; height: 100%; position: absolute; top: 0; left: 0; align-items: center; justify-content: center; }
        .slide-item.active { display: flex; animation: fadeIn 1s; }
        
        /* Fotoğraf Konteyner */
        .photo-container { 
            position: relative; 
            display: inline-block; /* Resmin boyutuna göre şekil alması için */
            max-width: 90%; 
            max-height: 90vh; 
            border-radius: 5px; 
            box-shadow: 0 0 50px rgba(0,0,0,0.8); 
            line-height: 0; /* Alt boşlukları engeller */
        }
        
        .photo-container img.main-photo { 
            max-width: 100%; 
            max-height: 85vh; 
            object-fit: contain; 
            display: block; 
        }
        
        /* Çerçeve Katmanı (Overlay) */
        .frame-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            pointer-events: none;
            background-size: 100% 100%; /* Çerçeveyi sündürerek oturt */
            background-repeat: no-repeat;
            background-position: center;
        }
        
        .photo-caption { 
            position: absolute; bottom: 0; left: 0; right: 0; 
            background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            color: white; padding: 30px 20px 20px 20px; text-align: left; 
            z-index: 20; 
            line-height: 1.5; /* Yazı satır aralığını düzelt */
        }

        #sidebar { width: 350px; background: #111; border-left: 1px solid #333; display: flex; flex-direction: column; justify-content: space-between; padding: 40px 20px; text-align: center; color: white; z-index: 10; }
        .qr-box { background: white; padding: 15px; border-radius: 15px; margin: 20px auto; width: 200px; height: 200px; display: flex; align-items: center; justify-content: center; }
        
        .empty-state { color: #666; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; }
        
        .text-white-50 { color: rgba(255, 255, 255, 0.6) !important; }
    </style>
</head>
<body>

    <div id="slider-area">
        <div id="slides-container" style="width:100%; height:100%;">
            <div class="empty-state">
                <i class="fa-solid fa-spinner fa-spin fa-3x mb-3"></i>
                <p>Bağlanıyor...</p>
            </div>
        </div>
    </div>

    <div id="sidebar">
        <div>
            <h2 style="color: <?= $primaryColor ?>; font-weight: bold; margin-bottom: 10px;"><?= htmlspecialchars($event['title']) ?></h2>
            
            <p class="text-white-50 mb-0">
                <i class="fa-solid fa-location-dot me-1" style="color: <?= $primaryColor ?>"></i> 
                <?= htmlspecialchars($event['location']) ?>
            </p>
        </div>
        
        <div class="animate__animated animate__zoomIn">
            <p class="fs-5 mb-2">Anılarını Paylaşmak İçin<br><span style="color:<?= $primaryColor ?>; font-weight:bold;">QR Kodu Tara!</span></p>
            <div class="qr-box shadow">
                <div id="qrcode"></div>
            </div>
        </div>

        <div class="sidebar-footer text-white-50 small animate__animated animate__fadeInUp animate__delay-2s">
            Powered by <a href="https://sthteam.com/" target="_blank" class="text-white text-decoration-none fw-bold">STH Team</a>
        </div>
    </div>

    <script>
        const BASE_URL = "<?= $baseUrl ?>";
        const API_URL = window.location.href.split('?')[0] + '?ajax=1';
        
        // PHP'den gelen çerçeve URL'leri
        const FRAME_H_URL = "<?= $urlFrameH ?>";
        const FRAME_V_URL = "<?= $urlFrameV ?>";
        
        let photos = [];
        let currentIndex = 0;
        let slideInterval = null;
        let isFirstLoad = true;

        new QRCode(document.getElementById("qrcode"), {
            text: "<?= $shareLink ?>",
            width: 170, height: 170,
            colorDark : "#000000", colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        async function fetchPhotos() {
            try {
                const response = await fetch(API_URL);
                if (!response.ok) throw new Error("Sunucu hatası");
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    const newPhotos = result.data;
                    
                    if (JSON.stringify(newPhotos) !== JSON.stringify(photos)) {
                        photos = newPhotos;
                        renderSlides();
                        
                        if (isFirstLoad || !slideInterval) {
                            startSlider();
                            isFirstLoad = false;
                        }
                    }
                }
            } catch (error) {
                console.error("Hata:", error);
            }
        }

        function renderSlides() {
            const container = document.getElementById('slides-container');
            
            if (photos.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-images fa-4x mb-3" style="color: #333;"></i>
                        <h3 class="text-white-50">Henüz fotoğraf yok...</h3>
                        <p class="text-muted">İlk fotoğrafı gönderen sen ol!</p>
                    </div>`;
                return;
            }

            let html = '';
            photos.forEach((photo, index) => {
                const noteHtml = photo.note ? `<h4 class="m-0 fw-bold">${photo.note}</h4>` : '';
                const userHtml = photo.full_name ? `<small class="text-white-50 mt-1 d-block">- ${photo.full_name}</small>` : '';
                const fullImgPath = BASE_URL + photo.file_path;

                // Çerçeve DIV'ini boş oluşturuyoruz, resim yüklenince JS dolduracak
                // onload="adjustFrame(this)" bu işi yapacak sihirli kısım
                const frameDiv = (FRAME_H_URL || FRAME_V_URL) ? '<div class="frame-overlay"></div>' : '';

                html += `
                    <div class="slide-item" id="slide-${index}">
                        <div class="photo-container animate__animated animate__zoomIn">
                            <img src="${fullImgPath}" class="main-photo" alt="Photo" onload="adjustFrame(this)" onerror="this.style.display='none'">
                            ${frameDiv}
                            <div class="photo-caption">
                                ${noteHtml}
                                ${userHtml}
                            </div>
                        </div>
                    </div>`;
            });
            container.innerHTML = html;
            
            if (currentIndex >= photos.length) currentIndex = 0;
            showSlide(currentIndex);
        }

        // --- YENİ FONKSİYON: ÇERÇEVEYİ AYARLA ---
        window.adjustFrame = function(img) {
            const overlay = img.parentElement.querySelector('.frame-overlay');
            if (!overlay) return;

            // Resmin doğal boyutlarına bak (Yüklenince çalışır)
            // Eğer Genişlik > Yükseklik ise -> YATAY
            if (img.naturalWidth > img.naturalHeight) {
                if (FRAME_H_URL) {
                    overlay.style.backgroundImage = `url('${FRAME_H_URL}')`;
                }
            } else {
                // Değilse -> DİKEY
                if (FRAME_V_URL) {
                    overlay.style.backgroundImage = `url('${FRAME_V_URL}')`;
                }
            }
        };

        function startSlider() {
            if (slideInterval) clearInterval(slideInterval);
            slideInterval = setInterval(() => {
                if (photos.length > 0) {
                    currentIndex = (currentIndex + 1) % photos.length;
                    showSlide(currentIndex);
                }
            }, 5000); // 5 Saniyede bir değiş
        }

        function showSlide(index) {
            const slides = document.querySelectorAll('.slide-item');
            slides.forEach(s => s.style.display = 'none');
            const currentSlide = document.getElementById('slide-' + index);
            if (currentSlide) currentSlide.style.display = 'flex';
        }

        fetchPhotos();
        setInterval(fetchPhotos, 5000); 

    </script>
</body>
</html>