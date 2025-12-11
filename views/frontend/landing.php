<?php
// views/frontend/landing.php
// $event değişkeni index.php tarafından buraya gönderildi.
$settings = json_decode($event['settings_json'], true);
$primaryColor = $settings['primary_color'] ?? '#0d6efd';

// Google Takvim Linki (Düzeltilmiş)
$gTitle = urlencode($event['title']);
$gDetails = urlencode("Etkinlik Detayları: " . $event['description']);
$gLocation = urlencode($event['location']);

// 1. DEĞİŞİKLİK: 'gmdate' yerine 'date' yapıyoruz ve sondaki '\Z' ifadesini siliyoruz.
$gStart = date('Ymd\THis', strtotime($event['event_date']));
$gEnd = date('Ymd\THis', strtotime($event['event_date'] . ' +4 hours'));

// 2. DEĞİŞİKLİK: Linkin en sonuna '&ctz=Europe/Istanbul' ekliyoruz.
$googleCalLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$gTitle&dates=$gStart/$gEnd&details=$gDetails&location=$gLocation&ctz=Europe/Istanbul";
// --- RESİM YOLLARINI DÜZELTME (GARANTİLİ YÖNTEM) ---
// Şu anki scriptin çalıştığı klasörü bul (örn: /event-manager-pro/public)
$baseFolder = dirname($_SERVER['SCRIPT_NAME']);

// Banner Yolu
$bannerSrc = '';
if (!empty($event['banner_image'])) {
    $bannerSrc = $baseFolder . '/' . $event['banner_image'];
}

// Logo Yolu
$logoSrc = '';
if (isset($settings['custom_logo_path']) && !empty($settings['custom_logo_path'])) {
    $logoSrc = $baseFolder . '/' . $settings['custom_logo_path'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-section { 
            background: linear-gradient(135deg, <?= $primaryColor ?> 0%, #333 100%); 
            color: white; padding: 40px 20px 80px 20px; text-align: center; border-radius: 0 0 50% 50% / 20px; 
            position: relative;
        }
        .countdown-box { background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; margin: 10px; min-width: 80px; backdrop-filter: blur(5px); }
        .event-card { margin-top: -60px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; position: relative; z-index: 10; }
        
        /* Banner Resmi Ayarı */
        .hero-banner {
            max-height: 250px; 
            width: auto; 
            max-width: 100%;
            object-fit: contain; 
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            background: rgba(0,0,0,0.1);
        }
        
        /* Logo Ayarı */
        .hero-logo {
            max-height: 80px;
            background: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="hero-section">
        
        <?php if($logoSrc): ?>
             <img src="<?= $logoSrc ?>" class="hero-logo mb-3" alt="Logo">
        <?php endif; ?>

        <?php if($bannerSrc): ?>
            <div class="mb-4 animate__animated animate__fadeInDown">
                <img src="<?= $bannerSrc ?>" class="hero-banner" alt="Etkinlik Banner">
            </div>
        <?php endif; ?>

        <h1 class="display-5 fw-bold mt-2"><?= htmlspecialchars($event['title']) ?></h1>
        <p class="lead opacity-75 mb-4"><?= htmlspecialchars($event['location']) ?></p>
        
        <div class="d-flex justify-content-center pb-3" id="countdown">
            <div class="countdown-box"><h3 id="days">00</h3><small>GÜN</small></div>
            <div class="countdown-box"><h3 id="hours">00</h3><small>SAAT</small></div>
            <div class="countdown-box"><h3 id="minutes">00</h3><small>DK</small></div>
            <div class="countdown-box"><h3 id="seconds">00</h3><small>SN</small></div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <div class="event-card p-4 text-center">
                    
                    <h5 class="text-muted mb-3 mt-2">
                        <i class="fa-regular fa-calendar me-2"></i> 
                        <?= date('d.m.Y - H:i', strtotime($event['event_date'])) ?>
                    </h5>

                    <p class="mb-4 text-start">
                        <?= nl2br(htmlspecialchars($event['description'] ?? 'Sizi aramızda görmekten mutluluk duyarız!')) ?>
                    </p>

                    <div class="d-grid gap-2">
                        <a href="<?= $_SERVER['REQUEST_URI'] ?>/kayit" class="btn btn-primary btn-lg shadow-sm" style="background-color: <?= $primaryColor ?>; border:none;">
                            <i class="fa-solid fa-ticket me-2"></i> Kayıt Ol
                        </a>
                        
                        <a href="<?= $googleCalLink ?>" target="_blank" class="btn btn-outline-secondary">
                            <i class="fa-regular fa-calendar-plus me-2"></i> Takvime Ekle
                        </a>
                    </div>
                </div>

                <div class="text-center mt-4 text-muted small">
                    Powered by <a href="https://sthteam.com/" target="_blank" class="text-decoration-none text-muted fw-bold">STH Team</a>
                </div>

            </div>
        </div>
    </div>

    <script>
        const eventDate = new Date("<?= $event['event_date'] ?>").getTime();
        
        const timer = setInterval(function() {
            const now = new Date().getTime();
            const distance = eventDate - now;

            if (distance < 0) {
                clearInterval(timer);
                document.getElementById("countdown").innerHTML = "<div class='alert alert-light text-dark w-100 fw-bold'>🎉 Etkinlik Başladı!</div>";
                return;
            }

            document.getElementById("days").innerText = Math.floor(distance / (1000 * 60 * 60 * 24));
            document.getElementById("hours").innerText = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            document.getElementById("minutes").innerText = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            document.getElementById("seconds").innerText = Math.floor((distance % (1000 * 60)) / 1000);
        }, 1000);
    </script>

</body>
</html>