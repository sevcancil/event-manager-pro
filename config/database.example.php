<?php
// ÖRNEK VERİTABANI AYAR DOSYASI
// Kurulum Adımları:
// 1. Bu dosyanın adını 'database.php' olarak değiştirin.
// 2. Aşağıdaki bilgileri kendi sunucu bilgilerinize göre güncelleyin.

return [
    'host'     => 'localhost',          // Veritabanı Sunucusu (Genelde localhost)
    'dbname'   => 'event_manager_db',   // Veritabanı Adı (database.sql içindeki ad)
    'username' => 'root',               // Veritabanı Kullanıcı Adı
    'password' => '',                   // Veritabanı Şifresi (XAMPP'te genelde boştur)
    'charset'  => 'utf8mb4'             // Karakter Seti (Türkçe karakterler için)
];