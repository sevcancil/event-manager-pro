<?php
// views/frontend/register.php

// 1. DİL AYARLARINI YÜKLE (En Başta Olmalı)
require_once __DIR__ . '/../../src/Language.php'; 

// $event ve $db değişkenleri index.php'den geliyor.
$settings = json_decode($event['settings_json'], true);
$primaryColor = $settings['primary_color'] ?? '#0d6efd';

$message = '';
$error = '';
$showTicket = false;
$ticketData = [];

// Form Gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $company = trim($_POST['company'] ?? '');
    $title = trim($_POST['title'] ?? '');

    if (empty($fullName) || empty($email)) {
        $error = $lang['err_fill_fields']; // Dil değişkeni kullanıldı
    } else {
        $check = $db->fetch("SELECT id FROM guests WHERE event_id = ? AND email = ?", [$event['id'], $email]);
        
        if ($check) {
            $error = $lang['err_email_exists'];
        } else {
            try {
                $qrString = strtoupper(substr(md5($event['id'] . time() . rand()), 0, 10));

                $sql = "INSERT INTO guests (event_id, full_name, email, phone, company, title, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $db->query($sql, [$event['id'], $fullName, $email, $phone, $company, $title, $qrString]);
                
                // Mail Gönderme
                $mailServicePath = __DIR__ . '/../../src/MailService.php';
                if (file_exists($mailServicePath)) {
                    require_once $mailServicePath;
                    sendWelcomeEmail($email, $fullName, $event, $qrString);
                }

                $_SESSION['guest_id'] = $db->lastInsertId();
                $_SESSION['guest_name'] = $fullName;
                $showTicket = true;
                
                $ticketData = [
                    'name' => $fullName,
                    'company_info' => ($title && $company) ? "$title, $company" : ($company ?: $title),
                    'code' => $qrString,
                    'event_name' => $event['title'],
                    'date' => date('d.m.Y H:i', strtotime($event['event_date'])),
                    'location' => $event['location']
                ];

            } catch (Exception $e) {
                $error = $lang['err_technical'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['register'] ?> - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .register-card { max-width: 500px; margin: 50px auto; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; position: relative; }
        
        /* Dil Seçici Stili */
        .lang-switcher {
            position: absolute; top: 15px; right: 15px; z-index: 50;
        }
        .lang-btn {
            background: rgba(255,255,255,0.8); border: 1px solid #ddd;
            color: #333; padding: 4px 8px; border-radius: 20px; text-decoration: none; font-size: 0.75rem; font-weight: bold;
            transition: 0.2s;
        }
        .lang-btn:hover, .lang-btn.active { background: <?= $primaryColor ?>; color: white; border-color: <?= $primaryColor ?>; }

        /* Bilet Stilleri */
        .ticket-visual { border: 2px solid #eee; border-radius: 15px; background: #fff; overflow: hidden; position: relative; }
        .ticket-header { background-color: <?= $primaryColor ?>; color: white; padding: 20px; text-align: center; }
        .ticket-body { padding: 20px; text-align: center; }
        .ticket-dashed-line { border-top: 2px dashed #ccc; margin: 20px 0; position: relative; }
        .ticket-dashed-line::before, .ticket-dashed-line::after {
            content: ""; background-color: #f4f6f9; width: 20px; height: 20px; border-radius: 50%; position: absolute; top: -11px;
        }
        .ticket-dashed-line::before { left: -30px; }
        .ticket-dashed-line::after { right: -30px; }
        
        #qrcode-container { display: flex; justify-content: center; margin-bottom: 10px; }
        #qrcode-container img { border: 5px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <div class="container">
        <div class="register-card bg-white">
            
            <div class="lang-switcher">
                <a href="?lang=tr" class="lang-btn <?= $currentLang == 'tr' ? 'active' : '' ?>">TR</a>
                <a href="?lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">EN</a>
            </div>

            <?php if (!$showTicket): ?>
            <div class="p-4 text-white text-center" style="background-color: <?= $primaryColor ?>;">
                <h4 class="m-0"><?= htmlspecialchars($event['title']) ?></h4>
                <small class="opacity-75"><?= $lang['form_header'] ?></small>
            </div>
            <?php endif; ?>

            <div class="p-4">
                
                <?php if ($showTicket): ?>
                    <div class="text-center animate__animated animate__fadeIn">
                        <div class="mb-3">
                            <i class="fa-solid fa-circle-check text-success fa-3x"></i>
                            <h5 class="fw-bold mt-2"><?= $lang['success_title'] ?></h5>
                            <p class="text-muted small"><?= $lang['success_msg'] ?></p>
                        </div>

                        <div id="ticket-content" class="ticket-visual text-start">
                            <div class="ticket-header">
                                <h5 class="m-0 fw-bold"><?= htmlspecialchars($ticketData['event_name']) ?></h5>
                                <small><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($ticketData['location']) ?></small>
                            </div>
                            <div class="ticket-body">
                                <div class="row align-items-center">
                                    <div class="col-12 mb-3">
                                        <h6 class="text-uppercase text-muted small mb-1"><?= $lang['ticket_guest'] ?></h6>
                                        <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($ticketData['name']) ?></h4>
                                        <?php if(!empty($ticketData['company_info'])): ?>
                                            <p class="text-muted small m-0"><?= htmlspecialchars($ticketData['company_info']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12">
                                        <span class="badge bg-light text-dark border p-2">
                                            <i class="fa-regular fa-calendar me-1"></i> <?= $ticketData['date'] ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="ticket-dashed-line"></div>
                                <div id="qrcode-container"></div>
                                <code class="text-muted"><?= $ticketData['code'] ?></code>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-grid gap-2">
                            <button onclick="downloadPDF()" class="btn btn-dark btn-lg shadow-sm">
                                <i class="fa-solid fa-file-pdf me-2"></i> <?= $lang['download_pdf'] ?>
                            </button>
                            <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-outline-secondary"><?= $lang['close'] ?></a>
                        </div>
                    </div>

                    <script>
                        var qrCodeContainer = document.getElementById("qrcode-container");
                        if(qrCodeContainer){
                            new QRCode(qrCodeContainer, {
                                text: "<?= $ticketData['code'] ?>",
                                width: 140, height: 140, colorDark : "#000000", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H
                            });
                        }
                    </script>

                <?php else: ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label"><?= $lang['full_name'] ?> <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['email'] ?> <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= $lang['phone'] ?></label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= $lang['company'] ?></label>
                                <input type="text" name="company" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?= $lang['title'] ?></label>
                            <input type="text" name="title" class="form-control">
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" required id="kvkk">
                            <label class="form-check-label small text-muted" for="kvkk">
                                <?= $lang['kvkk_text'] ?>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" style="background-color: <?= $primaryColor ?>; border:none;">
                            <?= $lang['btn_submit'] ?>
                        </button>
                        
                        <div class="text-center mt-3">
                            <?php 
                                $baseFolder = dirname($_SERVER['SCRIPT_NAME']);
                                if ($baseFolder === '/' || $baseFolder === '\\') $baseFolder = '';
                                $homeLink = $baseFolder . '/' . $event['slug'];
                            ?>
                            <a href="<?= $homeLink ?>" class="text-decoration-none text-muted small">
                                <i class="fa-solid fa-arrow-left"></i> <?= $lang['btn_back'] ?>
                            </a>
                        </div>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            var element = document.getElementById('ticket-content');
            var opt = {
                margin:       10,
                filename:     'Etkinlik-Bileti.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a5', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>