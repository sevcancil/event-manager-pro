<?php
// views/frontend/timeline.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Language.php';

$db = new Database();
$settings = json_decode($event['settings_json'], true) ?? [];
$primaryColor = $settings['primary_color'] ?? '#0d6efd';
$schedule = $db->fetchAll("SELECT * FROM event_schedule WHERE event_id = ? ORDER BY start_time ASC", [$event['id']]);
$action = 'program';

function getLocalizedDate($datetime, $langCode) {
    $timestamp = strtotime($datetime);
    if ($langCode === 'en') {
        return date("M d, l", $timestamp);
    } else {
        $months = ["Jan" => "Ocak", "Feb" => "Şubat", "Mar" => "Mart", "Apr" => "Nisan", "May" => "Mayıs", "Jun" => "Haziran", "Jul" => "Temmuz", "Aug" => "Ağustos", "Sep" => "Eylül", "Oct" => "Ekim", "Nov" => "Kasım", "Dec" => "Aralık"];
        $days = ["Mon" => "Pazartesi", "Tue" => "Salı", "Wed" => "Çarşamba", "Thu" => "Perşembe", "Fri" => "Cuma", "Sat" => "Cumartesi", "Sun" => "Pazar"];
        return date("d", $timestamp) . " " . $months[date("M", $timestamp)] . ", " . $days[date("D", $timestamp)];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['program_flow'] ?> - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        /* SİYAH TEMA KONTRAST AYARLARI */
        body { 
            background-color: #111; 
            color: #ffffff; /* Tüm yazıları zorla beyaz yap */
            min-height: 100vh; 
            font-family: 'Segoe UI', sans-serif; 
            padding-bottom: 80px; 
        }
        
        .date-separator {
            background-color: #222; 
            border: 1px solid #444; /* Çerçeveyi belirginleştir */
            border-radius: 8px; 
            padding: 8px 15px; 
            margin: 25px 0 15px 0;
            display: inline-block; 
            font-weight: bold; 
            color: <?= $primaryColor ?>; 
            box-shadow: 0 4px 10px rgba(255,255,255,0.05); /* Hafif beyaz gölge */
        }
        
        .timeline-item { 
            /* Çizgiyi daha kalın ve parlak yap */
            border-left: 3px solid <?= $primaryColor ?>; 
            padding-left: 20px; 
            margin-left: 10px; 
            padding-bottom: 30px; 
            position: relative; 
        }
        .timeline-item:last-child { border-left: 3px solid transparent; }
        
        .timeline-item::before { 
            content: ''; position: absolute; left: -6.5px; top: 5px; width: 10px; height: 10px; 
            background: <?= $primaryColor ?>; 
            border-radius: 50%; 
            /* Noktaya parlama efekti ver */
            box-shadow: 0 0 15px <?= $primaryColor ?>, 0 0 5px #fff;
        }
        
        .time-badge { font-weight: bold; font-size: 0.9rem; color: #ddd; margin-bottom: 2px; display: block; }
        .item-title { font-weight: 700; color: #fff; margin-bottom: 5px; font-size: 1.1rem; }
        
        /* Açıklama metinlerini gri değil, kırık beyaz yap */
        .description-text { color: #ccc !important; font-size: 0.95rem; }
    </style>
</head>
<body>

    <div class="container py-4">
        <h4 class="mb-4 fw-bold text-center text-white">
            <i class="fa-solid fa-calendar-days me-2"></i><?= $lang['program_flow'] ?>
        </h4>
        
        <?php if(empty($schedule)): ?>
            <div class="text-center text-white-50 py-5 mt-5">
                <i class="fa-regular fa-calendar-xmark fa-4x mb-3 opacity-50"></i>
                <p class="fs-5"><?= $lang['no_flow'] ?></p>
            </div>
        <?php else: ?>
            <div class="mt-2">
                <?php 
                $currentDate = null;
                foreach($schedule as $index => $item): 
                    $timestamp = strtotime($item['start_time']);
                    $dateKey = date('Y-m-d', $timestamp);
                    $displayTime = date('H:i', $timestamp);
                    
                    if ($currentDate !== $dateKey): 
                        $currentDate = $dateKey;
                ?>
                    <div class="w-100 mb-4 mt-2 text-start">
                        <div class="date-separator animate__animated animate__fadeIn">
                            <i class="fa-regular fa-calendar me-2"></i>
                            <?= getLocalizedDate($item['start_time'], $currentLang) ?>
                        </div>
                    </div>
                <?php endif; ?>

                    <div class="timeline-item animate__animated animate__fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s;">
                        <span class="time-badge">
                            <i class="fa-regular fa-clock me-1 text-white-50" style="font-size:0.8em"></i> <?= $displayTime ?>
                        </span>
                        
                        <h5 class="item-title">
                            <i class="fa-solid <?= $item['icon'] ?> me-2" style="color: <?= $primaryColor ?>;"></i>
                            <?= htmlspecialchars($item['title']) ?>
                        </h5>
                        
                        <?php if(!empty($item['description'])): ?>
                            <div class="description-text d-block mt-1" style="line-height: 1.5;">
                                <?= nl2br(htmlspecialchars($item['description'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php 
    $navBottomPath = __DIR__ . '/navbar_bottom.php';
    if(file_exists($navBottomPath)) include $navBottomPath; 
    ?>

</body>
</html>