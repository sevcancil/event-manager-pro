-- Veritabanı Oluşturma
CREATE DATABASE IF NOT EXISTS event_manager_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event_manager_db;

-- 1. Kullanıcılar (Adminler)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'client_admin') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Etkinlikler
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- Hangi müşteriye ait
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE, -- URL dostu ad (örn: sth-yilbasi)
    event_date DATETIME NOT NULL,
    location VARCHAR(255),
    description TEXT,
    banner_image VARCHAR(255) NULL, -- Yeni eklenen Banner
    status ENUM('active', 'draft', 'completed', 'archived') DEFAULT 'draft',
    settings_json JSON NULL, -- Renkler, Logo vb. burada tutulur
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Misafirler (Guests)
CREATE TABLE guests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(20),
    qr_code VARCHAR(100) NOT NULL UNIQUE, -- Benzersiz Bilet Kodu
    check_in_status TINYINT(1) DEFAULT 0, -- 0: Gelmedi, 1: İçeride
    check_in_at DATETIME DEFAULT NULL,    -- Giriş saati
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- 4. Medya Yüklemeleri (Fotoğraflar)
CREATE TABLE media_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    guest_id INT NULL, -- Hangi misafir yükledi
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video') DEFAULT 'image',
    note TEXT NULL, -- Fotoğraf notu
    is_approved TINYINT(1) DEFAULT 0, -- 0: Onay Bekliyor, 1: Yayında
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL
);

-- 5. Anket Sistemi (Polls)
CREATE TABLE polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    question VARCHAR(255) NOT NULL,
    status ENUM('active', 'passive') DEFAULT 'passive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE poll_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_text VARCHAR(100) NOT NULL,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
);

CREATE TABLE poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    option_id INT NOT NULL,
    guest_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE,
    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
);

-- 6. Etkinlik Programı (Schedule)
CREATE TABLE event_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    start_time TIME NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fa-clock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Varsayılan Super Admin (Şifre: 123456)
INSERT INTO users (username, password, role, full_name) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Süper Yönetici');