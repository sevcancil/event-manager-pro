<?php
// src/Database.php

class Database {
    private $pdo;
    private $error;

    public function __construct() {
        // Config dosyasını bir üst dizindeki config klasöründen çekiyoruz
        $config = require __DIR__ . '/../config/database.php';

        $dsn = "mysql:host=" . $config['host'] . ";dbname=" . $config['dbname'] . ";charset=" . $config['charset'];
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hata olursa patlat (try-catch ile yakalayacağız)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Veriyi dizi olarak getir ($row['name'])
            PDO::ATTR_EMULATE_PREPARES   => false,                // EN ÖNEMLİSİ: SQL Injection korumasını veritabanı seviyesinde yap
            PDO::ATTR_PERSISTENT         => true                  // Bağlantıyı kalıcı yap (Performans artışı)
        ];

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            // Güvenlik: Hata mesajını ekrana basma, log dosyasına yaz.
            // Kullanıcıya sadece "Bir sorun oluştu" de.
            error_log("Veritabanı Bağlantı Hatası: " . $e->getMessage());
            die("Sistem hatası. Lütfen yönetici ile iletişime geçin.");
        }
    }

    /**
     * Güvenli Sorgu Çalıştırma Metodu
     * @param string $sql SQL sorgusu (örn: SELECT * FROM users WHERE id = ?)
     * @param array $params Parametreler (örn: [5])
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Sorgu Hatası (" . $sql . "): " . $e->getMessage());
            die("İşlem gerçekleştirilemedi.");
        }
    }

    // Tek bir satır veri çekmek için (Örn: Giriş yapan kullanıcı bilgisi)
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    // Tüm satırları çekmek için (Örn: Etkinlik listesi)
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    // Insert işleminden sonra oluşan ID'yi döndürür
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    // Satır sayısını döndürür (Kayıt var mı yok mu kontrolü için)
    public function rowCount($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
}
?>