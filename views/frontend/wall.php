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

// Kök URL (Resimler için)
$baseUrl = "$protocol://$_SERVER[HTTP_HOST]$baseFolder";
$baseUrl = str_replace('\\', '/', $baseUrl);
if (substr($baseUrl, -1) != '/') $baseUrl .= '/';

// Paylaşım Linki
$shareLink = str_replace('/akis', '/paylas', "$protocol://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$shareLink = strtok($shareLink, '?');

// --- ÇERÇEVE AYARI (YENİ EKLENDİ) ---
$frameSrc = '';
if (!empty($settings['custom_frame_path'])) {
    // 1. Özel yüklenen çerçeve varsa onu kullan
    $frameSrc = $baseUrl . $settings['custom_frame_path'];
} elseif (file_exists(__DIR__ . '/../../public/assets/img/frame.png')) {
    // 2. Yoksa ve sistemde varsayılan çerçeve varsa onu kullan
    $frameSrc = $baseUrl . 'assets/img/frame.png';
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
        
        /* Fotoğraf Konteyner ve Çerçeve Ayarları */
        .photo-container { 
            position: relative; 
            max-width: 90%; 
            max-height: 90vh; 
            /* Border-radius çerçeve ile uyumsuzluk yapabilir, çerçeve kullanılıyorsa kaldıralım veya azaltalım */
            border-radius: 5px; 
            overflow: hidden; 
            box-shadow: 0 0 50px rgba(0,0,0,0.8); 
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
            pointer-events: none; /* Tıklamaları alta geçirir */
            background-size: 100% 100%; /* Çerçeveyi fotoğrafa tam oturt */
            background-repeat: no-repeat;
            background-position: center;
        }
        
        .photo-caption { 
            position: absolute; bottom: 0; left: 0; right: 0; 
            background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
            color: white; padding: 30px 20px 20px 20px; text-align: left; 
            z-index: 20; /* Çerçevenin üstünde görünsün */
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
        
        // PHP'den gelen çerçeve URL'sini JS değişkenine al
        const FRAME_SRC = "<?= $frameSrc ?>";
        
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

                // Çerçeve HTML'i (Varsa ekle)
                // Çerçeveyi CSS background olarak değil, img overlay olarak eklemek daha sağlıklı olabilir,
                // ama CSS background-image responsive yapı için daha kolaydır.
                // Burada style içine background-image olarak gömüyoruz.
                const frameHtml = FRAME_SRC ? `<div class="frame-overlay" style="background-image: url('${FRAME_SRC}');"></div>` : '';

                html += `
                    <div class="slide-item" id="slide-${index}">
                        <div class="photo-container animate__animated animate__zoomIn">
                            <img src="${fullImgPath}" class="main-photo" alt="Photo" onerror="this.src=''; this.alt='Resim Yüklenemedi'">
                            ${frameHtml}
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

        function startSlider() {
            if (slideInterval) clearInterval(slideInterval);
            slideInterval = setInterval(() => {
                if (photos.length > 0) {
                    currentIndex = (currentIndex + 1) % photos.length;
                    showSlide(currentIndex);
                }
            }, 5000);
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