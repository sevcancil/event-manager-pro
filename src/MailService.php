<?php
// src/MailService.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendWelcomeEmail($userEmail, $userName, $eventDetails, $ticketCode) {
    // Debug kapalı (0) ki sayfa yönlensin
    $mail = new PHPMailer(true);

    try {
        // --- 1. ADRES VE LİNK AYARLARI ---
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $domain = $_SERVER['HTTP_HOST'];
        // public klasörünü de yola ekliyoruz
        $baseUrl = "$protocol://$domain/public"; 

        $eventSlug = !empty($eventDetails['slug']) ? $eventDetails['slug'] : $eventDetails['id'];
        
        $ticketLink = "$baseUrl/$eventSlug/biletim?code=$ticketCode";
        $eventLink  = "$baseUrl/$eventSlug/program";

        // --- 2. SMTP AYARLARI (ÇALIŞAN AYARLAR) ---
        $mail->isSMTP();
        $mail->Host       = 'mail.domain.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@domain.com';    
        $mail->Password   = 'BURAYA_SIFRE_GIRILECEK'; // <-- Şifreni tekrar buraya yazmalısın!
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465;                        
        $mail->CharSet    = 'UTF-8';
        
        // Debug kapalı
        $mail->SMTPDebug  = 0; 

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('info@domain.com', 'Etkinlik Yönetimi'); 
        $mail->addAddress($userEmail, $userName);

        // --- 3. TAKVİM LİNKLERİ (GOOGLE & OUTLOOK) ---
        $gTitle = urlencode($eventDetails['title']);
        $gDetails = urlencode("Etkinlik Detayları: " . ($eventDetails['description'] ?? ''));
        $gLocation = urlencode($eventDetails['location']);
        
        $startTimestamp = strtotime($eventDetails['event_date']);
        if (!empty($eventDetails['event_end_date'])) {
            $endTimestamp = strtotime($eventDetails['event_end_date']);
        } else {
            $endTimestamp = strtotime($eventDetails['event_date'] . ' +4 hours');
        }

        $gStart = date('Ymd\THis', $startTimestamp);
        $gEnd   = date('Ymd\THis', $endTimestamp);
        $googleLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$gTitle&dates=$gStart/$gEnd&details=$gDetails&location=$gLocation&ctz=Europe/Istanbul";

        $oStart = gmdate('Y-m-d\TH:i:s\Z', $startTimestamp); 
        $oEnd   = gmdate('Y-m-d\TH:i:s\Z', $endTimestamp);
        $outlookWebLink = "https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent&startdt=$oStart&enddt=$oEnd&subject=$gTitle&body=$gDetails&location=$gLocation";

        // --- 4. ICS DOSYASI ---
        $icsContent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//EtkinlikYonetimi//TR\r\nMETHOD:PUBLISH\r\nBEGIN:VEVENT\r\nUID:" . md5(uniqid(mt_rand(), true)) . "@" . $domain . "\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\nDTSTART:" . gmdate('Ymd\THis\Z', $startTimestamp) . "\r\nDTEND:" . gmdate('Ymd\THis\Z', $endTimestamp) . "\r\nSUMMARY:" . $eventDetails['title'] . "\r\nDESCRIPTION:" . ($eventDetails['description'] ?? '') . "\r\nLOCATION:" . $eventDetails['location'] . "\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $mail->addStringAttachment($icsContent, 'invite.ics', 'base64', 'text/calendar');

        // --- 5. HTML İÇERİK (TASARIM) ---
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
                <a href='$ticketLink' style='background-color: #198754; color: white; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;'>🎫 Biletini Görüntüle</a>
            </div>

            <p style='text-align: center;'>Etkinlik programına gitmek için <a href='$eventLink'>tıklayın</a>.</p>

            <div style='background-color: #eef4fc; padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px;'>
                <p style='margin:0 0 10px 0; font-weight:bold; color:#333;'>Takviminize Ekleyin:</p>
                
                <a href='$googleLink' style='display:inline-block; margin:5px; color:#4285F4; text-decoration:none; font-weight:bold;'>
                    <span style='font-size:18px;'>G</span> Google Takvim
                </a> 
                | 
                <a href='$outlookWebLink' style='display:inline-block; margin:5px; color:#0078D4; text-decoration:none; font-weight:bold;'>
                    <span style='font-size:18px;'>O</span> Outlook Web
                </a>
                
                <p style='font-size: 11px; color: #666; margin-top: 10px;'>
                    * Outlook Masaüstü veya Apple Takvim için ekteki <strong>'invite.ics'</strong> dosyasını kullanabilirsiniz.
                </p>
            </div>
            <hr style='margin-top: 30px; border: 0; border-top: 1px solid #eee;'>
        </div>
        ";

        $mail->Body    = $mailContent;
        $mail->AltBody = "Merhaba $userName, Biletiniz: $ticketLink";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Hata olsa bile sessizce loga yaz, kullanıcıya hata gösterme
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}