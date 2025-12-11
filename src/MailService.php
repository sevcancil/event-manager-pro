<?php
// src/MailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendWelcomeEmail($userEmail, $userName, $eventDetails, $ticketCode) {
    $mail = new PHPMailer(true);

    try {
        // -----------------------------------------------------------------------
        // 1. OTOMATİK ADRES ALGILAMA (SİHİRLİ KISIM)
        // -----------------------------------------------------------------------
        // http mi https mi?
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        // Domain adı ne? (localhost veya siteadi.com)
        $domain = $_SERVER['HTTP_HOST'];
        
        // Eğer proje bir alt klasördeyse (örn: localhost/projem) burayı manuel düzeltebilirsin
        // Şimdilik ana domaini alıyoruz:
        $baseUrl = "$protocol://$domain"; 

        // ÖNEMLİ NOT: Eğer localhost/proje_adi içinde çalışıyorsan linkler kırık olabilir.
        // O yüzden Localhost'ta çalışırken proje klasörünü eklemek gerekebilir:
        // $baseUrl = "$protocol://$domain/PROJE_KLASOR_ADIN"; <-- Gerekirse bunu aç

        // 2. DİNAMİK LİNKLER
        // Bilet Sayfası (Kullanıcı koduyla gider)
        $ticketLink = "$baseUrl/frontend/ticket.php?code=$ticketCode";
        
        // Etkinlik Sayfası (Slug veya ID ile gider)
        // Eğer eventDetails içinde 'slug' varsa onu, yoksa ID'yi kullan
        $eventSlug = $eventDetails['slug'] ?? $eventDetails['id']; 
        $eventLink  = "$baseUrl/$eventSlug";

        // -----------------------------------------------------------------------
        // SUNUCU / GMAIL AYARLARI
        // -----------------------------------------------------------------------
        // NOT: Sunucuda GMAIL çalışmazsa, hosting firmanın verdiği info@ mailini kullanmalısın.
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';           
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sevcancil.dev@gmail.com';    // Gmail adresin
        $mail->Password   = 'slpm qpep nrct wcpm';      // 16 haneli uygulama şifresi
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;                        
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($mail->Username, 'Etkinlik Yönetimi'); 
        $mail->addAddress($userEmail, $userName);

        // -----------------------------------------------------------------------
        // TAKVİM LİNKLERİ
        // -----------------------------------------------------------------------
        $gTitle = urlencode($eventDetails['title']);
        $gDetails = urlencode("Etkinlik Detayları: " . $eventDetails['description']);
        $gLocation = urlencode($eventDetails['location']);
        
        $startTimestamp = strtotime($eventDetails['event_date']);
        $endTimestamp   = strtotime($eventDetails['event_date'] . ' +4 hours');

        // Google (Local Time)
        $gStart = date('Ymd\THis', $startTimestamp);
        $gEnd   = date('Ymd\THis', $endTimestamp);
        $googleLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$gTitle&dates=$gStart/$gEnd&details=$gDetails&location=$gLocation&ctz=Europe/Istanbul";

        // Outlook (UTC - ZULU)
        $oStart = gmdate('Y-m-d\TH:i:s\Z', $startTimestamp); 
        $oEnd   = gmdate('Y-m-d\TH:i:s\Z', $endTimestamp);
        $outlookLink = "https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent&startdt=$oStart&enddt=$oEnd&subject=$gTitle&body=$gDetails&location=$gLocation";

        // -----------------------------------------------------------------------
        // HTML İÇERİK
        // -----------------------------------------------------------------------
        $mail->isHTML(true);
        $mail->Subject = 'Kaydınız Başarıyla Alındı! - ' . $eventDetails['title'];
        
        $mailContent = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px;'>
            <h2 style='color: #0d6efd; text-align: center;'>Kayıt Başarılı! 🎉</h2>
            <p>Merhaba <strong>$userName</strong>,</p>
            <p><strong>{$eventDetails['title']}</strong> etkinliğine yeriniz ayırtıldı.</p>
            
            <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #0d6efd; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>📅 Tarih:</strong> {$eventDetails['event_date']}</p>
                <p style='margin: 5px 0;'><strong>📍 Yer:</strong> {$eventDetails['location']}</p>
            </div>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='$ticketLink' style='background-color: #198754; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>🎫 Biletini Görüntüle</a>
            </div>

            <p style='text-align: center;'>Veya etkinlik sayfasına gitmek için <a href='$eventLink'>tıklayın</a>.</p>

            <div style='text-align: center; margin-top: 20px;'>
                <a href='$googleLink' style='margin-right:10px; color:#4285F4; text-decoration:none;'>Google Takvime Ekle</a> | 
                <a href='$outlookLink' style='margin-left:10px; color:#0078D4; text-decoration:none;'>Outlook Takvime Ekle</a>
            </div>
            
            <hr style='margin-top: 30px; border: 0; border-top: 1px solid #eee;'>
        </div>
        ";

        $mail->Body    = $mailContent;
        $mail->AltBody = "Merhaba $userName, Biletiniz: $ticketLink";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Hata varsa ekrana bas (Localde test ederken)
        echo "Mail Gönderilemedi: " . $mail->ErrorInfo;
        die(); 
    }
}