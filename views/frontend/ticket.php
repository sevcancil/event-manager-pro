<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// 1. DİL DOSYASI
require_once __DIR__ . '/../../src/Language.php';
require_once __DIR__ . '/../../src/Database.php';
$db = new Database();

if (!isset($_SESSION['guest_id'])) {
    header("Location: paylas");
    exit;
}

$guest = $db->fetch("SELECT * FROM guests WHERE id = ?", [$_SESSION['guest_id']]);
$settings = json_decode($event['settings_json'], true) ?? [];
$primaryColor = $settings['primary_color'] ?? '#0d6efd';
$action = 'biletim';
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['my_ticket'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        body { 
            background-color: #111; 
            color: white; 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            padding-top: 50px; 
            padding-bottom: 100px; 
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <h3 class="mb-4"><?= $lang['digital_ticket'] ?></h3>
        
        <div class="mb-4">
            <button onclick="downloadPDF()" class="btn btn-primary btn-lg rounded-pill shadow">
                <i class="fa-solid fa-download me-2"></i>PDF Olarak İndir
            </button>
        </div>
        
        <div id="ticket-card-display" class="card border-0 mx-auto text-dark shadow-lg" style="max-width: 320px; border-radius: 20px; overflow: hidden; background-color: white;">
            <div class="card-header text-white text-center py-3" style="background-color: <?= $primaryColor ?>">
                <h5 class="m-0"><?= htmlspecialchars($event['title']) ?></h5>
            </div>
            <div class="card-body bg-white text-center py-5">
                <div id="qrcode-display" class="d-flex justify-content-center mb-4"></div>
                
                <h4 class="fw-bold text-dark"><?= htmlspecialchars($guest['full_name']) ?></h4>
                <p class="text-muted m-0"><?= htmlspecialchars($guest['email']) ?></p>
                <div class="mt-3 badge bg-light text-dark border fs-6 px-3 py-2"><?= $guest['qr_code'] ?></div>
            </div>
            <div class="card-footer bg-light text-muted small">
                <?= $lang['scan_instruction'] ?>
            </div>
        </div>

        <div id="pdf-template" style="display: none;">
            <div class="pdf-card" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: white; color: black; font-family: sans-serif; text-align: center; border: 1px solid #ddd; box-shadow: 0 0 20px rgba(0,0,0,0.1);">
                <div style="background-color: <?= $primaryColor ?>; color: white; padding: 30px; border-radius: 5px 5px 0 0;">
                    <h2 style="margin: 0; font-size: 28px;"><?= htmlspecialchars($event['title']) ?></h2>
                </div>
                <div style="padding: 50px 40px; border-bottom: 1px solid #eee;">
                    <div id="qrcode-pdf-target" style="display: flex; justify-content: center; margin-bottom: 30px;"></div>
                    
                    <h1 style="margin: 0 0 10px 0; font-size: 32px; color: #000; font-weight: bold;"><?= htmlspecialchars($guest['full_name']) ?></h1>
                    <p style="color: #555; margin: 0 0 30px 0; font-size: 18px;"><?= htmlspecialchars($guest['email']) ?></p>
                    
                    <div style="background-color: #f8f9fa; padding: 15px 30px; display: inline-block; border: 2px dashed #ccc; font-weight: bold; font-size: 22px; color: #333; border-radius: 10px;">
                        KOD: <?= $guest['qr_code'] ?>
                    </div>
                </div>
                <div style="padding: 20px; background-color: #f8f9fa; color: #666; font-size: 14px;">
                    <?= $lang['scan_instruction'] ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        // 1. Ekranda Görünen QR Kod
        new QRCode(document.getElementById("qrcode-display"), {
            text: "<?= $guest['qr_code'] ?>",
            width: 150, height: 150,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        // 2. KESİN VE GARANTİLİ PDF ÇÖZÜMÜ: OVERLAY TEKNİĞİ
        function downloadPDF() {
            const btn = document.querySelector('button[onclick="downloadPDF()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Hazırlanıyor...';
            btn.disabled = true;

            // ADIM A: Ekranı kaplayan geçici bir "Overlay" oluştur.
            // Bu sayede tarayıcı içeriği "görünür alan" (viewport) içinde algılar ve tam çizer.
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'white'; // Temiz beyaz zemin
            overlay.style.zIndex = '99999'; // Her şeyin üstünde
            overlay.style.display = 'flex';
            overlay.style.justifyContent = 'center';
            overlay.style.alignItems = 'center';
            overlay.style.padding = '20px';
            overlay.style.overflowY = 'auto'; // İçerik sığmazsa kaydırılsın (kesilmemesi için)
            document.body.appendChild(overlay);

            // ADIM B: Şablonu overlay içine koy
            overlay.innerHTML = document.getElementById('pdf-template').innerHTML;

            // ADIM C: QR Kodu taze çiz (Daha büyük ve net)
            const pdfQrTarget = overlay.querySelector('#qrcode-pdf-target');
            new QRCode(pdfQrTarget, {
                text: "<?= $guest['qr_code'] ?>",
                width: 250, height: 250, 
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // Tarayıcının çizimi tamamlaması için kısa bir bekleme
            setTimeout(() => {
                const opt = {
                    margin:       10, 
                    filename:     'Giris-Bileti-<?= $guest['qr_code'] ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { 
                        scale: 2, 
                        useCORS: true,
                        scrollY: 0,
                        // Overlay kullandığımız için artık windowHeight zorlamasına gerek yok,
                        // çünkü eleman zaten görünür alanda (viewport) duruyor.
                    },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                // ÖNEMLİ: Render alırken overlay'in içindeki kartı hedef alıyoruz
                const elementToPrint = overlay.querySelector('.pdf-card');

                html2pdf().set(opt).from(elementToPrint).save().then(function(){
                    // İşlem bitince overlay'i kaldır ve eski ekrana dön
                    document.body.removeChild(overlay);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }, 800); // 0.8 saniye bekleme (Garanti olsun)
        }
    </script>

    <?php include __DIR__ . '/navbar_bottom.php'; ?>
</body>
</html>