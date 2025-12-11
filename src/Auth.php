<?php
// src/Auth.php
require_once 'Database.php';

class Auth {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = new Database();
    }

    // Giriş İşlemi
    public function login($username, $password) {
        // Kullanıcıyı veritabanından bul
        $user = $this->db->fetch("SELECT * FROM users WHERE username = ?", [$username]);

        // Kullanıcı varsa VE şifre doğruysa (Hash kontrolü)
        if ($user && password_verify($password, $user['password'])) {
            
            // GÜVENLİK: Session Fixation saldırısını önle
            session_regenerate_id(true);

            // Session'a verileri yaz
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            return true;
        }

        return false;
    }

    // Çıkış İşlemi
    public function logout() {
        $_SESSION = []; // Tüm değişkenleri boşalt
        session_destroy(); // Oturumu yok et
    }

    // Kullanıcı giriş yapmış mı?
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    // Şu anki kullanıcının rolü ne? (super_admin / client_admin)
    public function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    // Sadece belirli role sahip olanları içeri al (Middleware mantığı)
    public function requireRole($requiredRole) {
        if (!$this->isLoggedIn() || $this->getRole() !== $requiredRole) {
            // Yetkisiz giriş! Login sayfasına at.
            header('Location: /event-manager-pro/public/index.php'); 
            exit;
        }
    }
}
?>