<?php
// views/frontend/landing.php

// 1. DİL AYARLARI
require_once __DIR__ . '/../../src/Language.php'; 

// --------------------------------------------------------------------------
// 2. TEMİZ LİNK OLUŞTURMA (CRITICAL FIX)
// --------------------------------------------------------------------------
// Script'in çalıştığı ana klasörü bul (Örn: /event-manager-pro/public)
$baseFolder = dirname($_SERVER['SCRIPT_NAME']); 

// Eğer kök dizindeyse slash hatasını önle
if ($baseFolder === '/' || $baseFolder === '\\') {
    $baseFolder = '';
}

// Etkinliğin Temiz Ana Sayfa Linki
// Örnek: http://localhost/proje/public/etkinlik-slug
$eventHomeUrl = $baseFolder . '/' . $event['slug'];

// Kayıt Linki (Artık bozulmaz)
$registerLink = $eventHomeUrl . '/kayit';

// Apple Takvim Linki
$appleLink = $eventHomeUrl . '?download=ics';


// --------------------------------------------------------------------------
// 3. ICS İNDİRME İŞLEMİ (APPLE/IOS)
// --------------------------------------------------------------------------
if (isset($_GET['download']) && $_GET['download'] === 'ics') {
    if (ob_get_level()) ob_end_clean();

    $startTs = strtotime($event['event_date']);
    $endTs   = !empty($event['event_end_date']) ? strtotime($event['event_end_date']) : strtotime($event['event_date'] . ' +4 hours');
    
    $dtStart = gmdate('Ymd\THis\Z', $startTs);
    $dtEnd   = gmdate('Ymd\THis\Z', $endTs);
    $now     = gmdate('Ymd\THis\Z');
    $uid     = md5(uniqid(mt_rand(), true)) . '@' . $_SERVER['HTTP_HOST'];

    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//EtkinlikYonetimi//TR\r\nMETHOD:PUBLISH\r\nBEGIN:VEVENT\r\nUID:$uid\r\nDTSTAMP:$now\r\nDTSTART:$dtStart\r\nDTEND:$dtEnd\r\nSUMMARY:" . $event['title'] . "\r\nDESCRIPTION:" . ($event['description'] ?? '') . "\r\nLOCATION:" . $event['location'] . "\r\nEND:VEVENT\r\nEND:VCALENDAR";

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="event-invite.ics"');
    echo $ics;
    exit;
}

// --------------------------------------------------------------------------
// 4. SAYFA AYARLARI & FORMATLAMA
// --------------------------------------------------------------------------
$settings = json_decode($event['settings_json'], true);
$primaryColor = $settings['primary_color'] ?? '#0d6efd';

// Resim Yolları
$bannerSrc = !empty($event['banner_image']) ? $baseFolder . '/' . $event['banner_image'] : '';
$logoSrc = (!empty($settings['custom_logo_path'])) ? $baseFolder . '/' . $settings['custom_logo_path'] : '';

// Tarih Formatı (Dile Göre)
$startStr = strtotime($event['event_date']);
$dateFormat = ($currentLang === 'en') ? 'M d, Y' : 'd.m.Y';
$timeFormat = 'H:i';

$dateDisplay = date("$dateFormat $timeFormat", $startStr); 

if (!empty($event['event_end_date'])) {
    $endStr = strtotime($event['event_end_date']);
    if (date('Ymd', $startStr) === date('Ymd', $endStr)) {
        // Aynı gün
        $dateDisplay = '<span>' . date($dateFormat, $startStr) . '</span>' . 
                       '<span class="mx-2 text-muted">|</span>' . 
                       '<span class="fw-bold">' . date($timeFormat, $startStr) . ' - ' . date($timeFormat, $endStr) . '</span>';
    } else {
        // Farklı gün
        $dateDisplay = '<div class="d-flex align-items-center justify-content-center flex-wrap gap-2">' . 
                       '<span>' . date("$dateFormat $timeFormat", $startStr) . '</span>' . 
                       '<i class="fa-solid fa-arrow-right-long text-muted" style="font-size: 0.8em;"></i>' . 
                       '<span>' . date("$dateFormat $timeFormat", $endStr) . '</span>' . 
                       '</div>';
    }
}

// Takvim Linkleri (Google & Outlook)
$endTimestamp = !empty($event['event_end_date']) ? strtotime($event['event_end_date']) : strtotime($event['event_date'] . ' +4 hours');

$gTitle = urlencode($event['title']);
$gDetails = urlencode($lang['event_details'] . ": " . ($event['description'] ?? ''));
$gLocation = urlencode($event['location']);

$gStart = date('Ymd\THis', $startStr);
$gEnd   = date('Ymd\THis', $endTimestamp);
$googleCalLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$gTitle&dates=$gStart/$gEnd&details=$gDetails&location=$gLocation&ctz=Europe/Istanbul";

$oStart = gmdate('Y-m-d\TH:i:s\Z', $startStr);
$oEnd   = gmdate('Y-m-d\TH:i:s\Z', $endTimestamp);
$outlookLink = "https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent&startdt=$oStart&enddt=$oEnd&subject=$gTitle&body=$gDetails&location=$gLocation";
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
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
        
        .hero-banner {
            max-height: 250px; width: auto; max-width: 100%; object-fit: contain; 
            border: 4px solid rgba(255,255,255,0.3); border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2); background: rgba(0,0,0,0.1);
        }
        
        .hero-logo {
            max-height: 80px; background: white; padding: 10px; border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px;
        }

        .calendar-option {
            text-decoration: none; color: #333; display: flex; align-items: center; padding: 15px;
            border: 1px solid #eee; border-radius: 10px; margin-bottom: 10px; transition: all 0.2s;
        }
        .calendar-option:hover {
            background-color: #f8f9fa; border-color: <?= $primaryColor ?>; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .calendar-icon { width: 40px; text-align: center; font-size: 1.5rem; }

        /* Dil Seçici Stili */
        .lang-switcher {
            position: absolute; top: 20px; right: 20px; z-index: 50;
        }
        .lang-btn {
            background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4);
            color: white; padding: 5px 10px; border-radius: 20px; text-decoration: none; font-size: 0.85rem; backdrop-filter: blur(5px);
            transition: 0.3s;
        }
        .lang-btn:hover, .lang-btn.active { background: white; color: #333; }
    </style>
</head>
<body>

    <div class="hero-section">
        
        <div class="lang-switcher">
            <a href="?lang=tr" class="lang-btn <?= $currentLang == 'tr' ? 'active' : '' ?>">TR</a>
            <a href="?lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">EN</a>
        </div>

        <?php if($logoSrc): ?> <img src="<?= $logoSrc ?>" class="hero-logo mb-3" alt="Logo"> <?php endif; ?>
        <?php if($bannerSrc): ?>
            <div class="mb-4 animate__animated animate__fadeInDown">
                <img src="<?= $bannerSrc ?>" class="hero-banner" alt="Etkinlik Banner">
            </div>
        <?php endif; ?>

        <h1 class="display-5 fw-bold mt-2"><?= htmlspecialchars($event['title']) ?></h1>
        <p class="lead opacity-75 mb-4"><?= htmlspecialchars($event['location']) ?></p>
        
        <div class="d-flex justify-content-center pb-3" id="countdown">
            <div class="countdown-box"><h3 id="days">00</h3><small><?= $lang['days'] ?></small></div>
            <div class="countdown-box"><h3 id="hours">00</h3><small><?= $lang['hours'] ?></small></div>
            <div class="countdown-box"><h3 id="minutes">00</h3><small><?= $lang['minutes'] ?></small></div>
            <div class="countdown-box"><h3 id="seconds">00</h3><small><?= $lang['seconds'] ?></small></div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <div class="event-card p-4 text-center">
                    
                    <div class="bg-white rounded-4 py-2 px-4 mb-4 shadow-sm border d-inline-block">
                        <div class="text-dark m-0 d-flex align-items-center justify-content-center" style="font-size: 0.95rem; font-weight: 500;">
                            <i class="fa-regular fa-calendar text-primary me-2"></i> 
                            <div><?= $dateDisplay ?></div>
                        </div>
                    </div>

                    <p class="mb-4 text-start">
                        <?= nl2br(htmlspecialchars($event['description'] ?? $lang['welcome_msg'])) ?>
                    </p>

                    <div class="d-grid gap-2">
                        <a href="<?= $registerLink ?>" class="btn btn-primary btn-lg shadow-sm fw-bold" style="background-color: <?= $primaryColor ?>; border:none;">
                            <i class="fa-solid fa-ticket me-2"></i> <?= $lang['register'] ?>
                        </a>
                        
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#calendarModal">
                            <i class="fa-regular fa-calendar-plus me-2"></i> <?= $lang['add_calendar'] ?>
                        </button>
                    </div>
                </div>

                <div class="text-center mt-4 text-muted small">
                    <?= $lang['powered_by'] ?> <a href="https://sthteam.com/" target="_blank" class="text-decoration-none text-muted fw-bold">STH Team</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="calendarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><?= $lang['add_calendar'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3"><?= $lang['choose_app'] ?></p>
                    
                    <a href="<?= $googleCalLink ?>" target="_blank" class="calendar-option">
                        <div class="calendar-icon text-primary"><i class="fa-brands fa-google"></i></div>
                        <div>
                            <div class="fw-bold"><?= $lang['google_cal'] ?></div>
                            <div class="small text-muted"><?= $lang['google_hint'] ?></div>
                        </div>
                    </a>

                    <a href="<?= $appleLink ?>" class="calendar-option">
                        <div class="calendar-icon text-dark"><i class="fa-brands fa-apple"></i></div>
                        <div>
                            <div class="fw-bold"><?= $lang['apple_cal'] ?></div>
                            <div class="small text-muted"><?= $lang['apple_hint'] ?></div>
                        </div>
                    </a>

                    <a href="<?= $outlookLink ?>" target="_blank" class="calendar-option">
                        <div class="calendar-icon text-info"><i class="fa-brands fa-microsoft"></i></div>
                        <div>
                            <div class="fw-bold"><?= $lang['outlook_cal'] ?></div>
                            <div class="small text-muted"><?= $lang['outlook_hint'] ?></div>
                        </div>
                    </a>
                    
                    <a href="<?= $appleLink ?>" class="calendar-option mb-0">
                        <div class="calendar-icon text-secondary"><i class="fa-solid fa-file-arrow-down"></i></div>
                        <div>
                            <div class="fw-bold"><?= $lang['download_ics'] ?></div>
                            <div class="small text-muted"><?= $lang['file_hint'] ?></div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const eventDate = new Date("<?= $event['event_date'] ?>").getTime();
        const timer = setInterval(function() {
            const now = new Date().getTime();
            const distance = eventDate - now;
            if (distance < 0) {
                clearInterval(timer);
                document.getElementById("countdown").innerHTML = "<div class='alert alert-light text-dark w-100 fw-bold'><?= $lang['event_started'] ?></div>";
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