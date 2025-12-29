SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+03:00"; -- Türkiye saati

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `event_db`
--

-- --------------------------------------------------------

--
-- 1. Tablo yapısı: `users`
-- (Yönetici ve Client Adminler)
--

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','client_admin') NOT NULL,
  `status` enum('active','passive') DEFAULT 'active',
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 2. Tablo yapısı: `events`
-- (GÜNCELLENDİ: Dinamik form ve KVKK alanları eklendi)
--

CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `event_date` datetime NOT NULL,
  `event_end_date` datetime DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text,
  `banner_image` varchar(255) DEFAULT NULL,
  `logo_image` varchar(255) DEFAULT NULL,
  `status` enum('active','draft','completed','archived') DEFAULT 'draft',
  `settings_json` json DEFAULT NULL, -- Tema renkleri vb.
  `form_schema` json DEFAULT NULL,   -- YENİ: Dinamik kayıt formu yapısı
  `kvkk_text` text DEFAULT NULL,     -- YENİ: Etkinliğe özel KVKK metni
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 3. Tablo yapısı: `guests`
-- (GÜNCELLENDİ: Kategori ve KVKK onay durumu eklendi)
--

CREATE TABLE `guests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'Misafir', -- YENİ: Misafir, Protokol, Basın vb.
  `guest_count` int DEFAULT '1',
  `qr_code` varchar(100) NOT NULL,
  `check_in_status` tinyint(1) DEFAULT '0',
  `check_in_at` datetime DEFAULT NULL,
  `kvkk_approved` tinyint(1) DEFAULT '0',   -- YENİ: KVKK onayı
  `custom_responses` json DEFAULT NULL,     -- YENİ: Dinamik form cevapları buraya
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 4. Tablo yapısı: `event_schedule`
--

CREATE TABLE `event_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `start_time` time NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT 'fa-clock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 5. Tablo yapısı: `media_uploads`
--

CREATE TABLE `media_uploads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `guest_id` int DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` enum('image','video') DEFAULT 'image',
  `note` text,
  `is_approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `guest_id` (`guest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 6. Tablo yapısı: `polls`
--

CREATE TABLE `polls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `question` varchar(255) NOT NULL,
  `status` enum('active','passive') DEFAULT 'passive',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 7. Tablo yapısı: `poll_options`
--

CREATE TABLE `poll_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `poll_id` int NOT NULL,
  `option_text` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 8. Tablo yapısı: `poll_votes`
--

CREATE TABLE `poll_votes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `poll_id` int NOT NULL,
  `option_id` int NOT NULL,
  `guest_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`),
  KEY `option_id` (`option_id`),
  KEY `guest_id` (`guest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 9. Tablo yapısı: `sessions`
--

CREATE TABLE `sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 10. Tablo yapısı: `session_logs`
--

CREATE TABLE `session_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `guest_id` int NOT NULL,
  `scanned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entry` (`session_id`,`guest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 11. Tablo yapısı: `mail_logs`
-- (YENİ: Gönderilen toplu maillerin kaydı)
--

CREATE TABLE `mail_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `sent_count` int DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;