<?php
// views/frontend/register.php

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

    if (empty($fullName) || empty($email)) {
        $error = "Lütfen isim ve e-posta alanlarını doldurun.";
    } else {
        $check = $db->fetch("SELECT id FROM guests WHERE event_id = ? AND email = ?", [$event['id'], $email]);
        
        if ($check) {
            $error = "Bu e-posta adresi ile zaten kayıt oluşturulmuş.";
        } else {
            try {
                $qrString = strtoupper(substr(md5($event['id'] . time() . rand()), 0, 10));

                $sql = "INSERT INTO guests (event_id, full_name, email, phone, qr_code) VALUES (?, ?, ?, ?, ?)";
                $db->query($sql, [$event['id'], $fullName, $email, $phone, $qrString]);
                
                // -------------------------------------------------------------
                // BURASI YENİ EKLENDİ: MAİL GÖNDERME İŞLEMİ
                // -------------------------------------------------------------
                // Dosya yolunu kontrol et: views/frontend/register.php içindeyiz,
                // bu yüzden iki üst klasöre çıkıp src'ye giriyoruz.
                $mailServicePath = __DIR__ . '/../../src/MailService.php';
                
                // ...
                if (file_exists($mailServicePath)) {
                    require_once $mailServicePath;
                    
                    // $qrString değişkeni zaten yukarıda oluşturulmuştu
                    // Yeni parametre olarak $qrString'i en sona ekliyoruz:
                    sendWelcomeEmail($email, $fullName, $event, $qrString);
                }
                // ...
                // -------------------------------------------------------------
                // MAİL İŞLEMİ BİTİŞ
                // -------------------------------------------------------------

                $_SESSION['guest_id'] = $db->lastInsertId();
                $_SESSION['guest_name'] = $fullName;
                $showTicket = true;
                $ticketData = [
                    'name' => $fullName,
                    'code' => $qrString,
                    'event_name' => $event['title'],
                    'date' => date('d.m.Y H:i', strtotime($event['event_date'])),
                    'location' => $event['location']
                ];

            } catch (Exception $e) {
                // Hata detayını görmek istersen: $e->getMessage()
                $error = "Kayıt sırasında teknik bir hata oluştu.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - <?= htmlspecialchars($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body { background-color: #f4f6f9; }
        .register-card { max-width: 500px; margin: 50px auto; border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        
        .ticket-visual { 
            border: 2px solid #eee; 
            border-radius: 15px; 
            background: #fff; 
            overflow: hidden; 
            position: relative;
        }
        .ticket-header {
            background-color: <?= $primaryColor ?>;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .ticket-body {
            padding: 20px;
            text-align: center;
        }
        .ticket-dashed-line {
            border-top: 2px dashed #ccc;
            margin: 20px 0;
            position: relative;
        }
        .ticket-dashed-line::before, .ticket-dashed-line::after {
            content: "";
            background-color: #f4f6f9;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            position: absolute;
            top: -11px;
        }
        .ticket-dashed-line::before { left: -30px; }
        .ticket-dashed-line::after { right: -30px; }
        
        /* QR Kod Ortala */
        #qrcode-container {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }
        /* Oluşan resim boyutu */
        #qrcode-container img {
            border: 5px solid #fff; /* Beyaz çerçeve */
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="register-card bg-white">
            
            <?php if (!$showTicket): ?>
            <div class="p-4 text-white text-center" style="background-color: <?= $primaryColor ?>;">
                <h4 class="m-0"><?= htmlspecialchars($event['title']) ?></h4>
                <small class="opacity-75">Kayıt Formu</small>
            </div>
            <?php endif; ?>

            <div class="p-4">
                
                <?php if ($showTicket): ?>
                    <div class="text-center animate__animated animate__fadeIn">
                        
                        <div class="mb-3">
                            <i class="fa-solid fa-circle-check text-success fa-3x"></i>
                            <h5 class="fw-bold mt-2">Kaydınız Başarılı!</h5>
                            <p class="text-muted small">Biletinizi PDF olarak indirip saklayınız.</p>
                        </div>

                        <div id="ticket-content" class="ticket-visual text-start">
                            <div class="ticket-header">
                                <h5 class="m-0 fw-bold"><?= htmlspecialchars($ticketData['event_name']) ?></h5>
                                <small><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($ticketData['location']) ?></small>
                            </div>
                            <div class="ticket-body">
                                <div class="row align-items-center">
                                    <div class="col-12 mb-3">
                                        <h6 class="text-uppercase text-muted small mb-1">MİSAFİR</h6>
                                        <h4 class="fw-bold text-dark"><?= htmlspecialchars($ticketData['name']) ?></h4>
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
                                <i class="fa-solid fa-file-pdf me-2"></i> Bileti PDF Olarak İndir
                            </button>
                            <a href="<?= $_SERVER['REQUEST_URI'] ?>" class="btn btn-outline-secondary">Kapat</a>
                        </div>
                    </div>

                    <script>
                        // QR Kodu tarayıcıda oluşturuyoruz
                        var qrCodeContainer = document.getElementById("qrcode-container");
                        if(qrCodeContainer){
                            new QRCode(qrCodeContainer, {
                                text: "<?= $ticketData['code'] ?>",
                                width: 140,
                                height: 140,
                                colorDark : "#000000",
                                colorLight : "#ffffff",
                                correctLevel : QRCode.CorrectLevel.H
                            });
                        }
                    </script>

                <?php else: ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-Posta Adresi <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="phone" class="form-control">
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" required id="kvkk">
                            <label class="form-check-label small text-muted" for="kvkk">
                                Kişisel verilerimin işlenmesini kabul ediyorum.
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" style="background-color: <?= $primaryColor ?>; border:none;">
                            KAYDI TAMAMLA
                        </button>
                        
                        <div class="text-center mt-3">
                            <a href="../<?= $event['slug'] ?>" class="text-decoration-none text-muted small">
                                <i class="fa-solid fa-arrow-left"></i> Ana Sayfaya Dön
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
                image:        { type: 'jpeg', quality: 1 }, // Kaliteyi artırdık
                html2canvas:  { scale: 2, useCORS: true },  // useCORS önemli
                jsPDF:        { unit: 'mm', format: 'a5', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

</body>
</html>