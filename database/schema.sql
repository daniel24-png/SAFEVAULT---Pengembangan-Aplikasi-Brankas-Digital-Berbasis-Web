-- ========================================================
-- SAFEVAULT - Database Schema (Versi 0.1)
-- Kompatibel dengan MySQL 5.7+ / MariaDB 10.4+ (XAMPP)
-- ========================================================

-- 1. Buat Database jika belum ada
CREATE DATABASE IF NOT EXISTS `safevault_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `safevault_db`;

-- 2. Buat Tabel Pengguna (users)
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(30) NOT NULL,
    `email` VARCHAR(254) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Buat Tabel Pencatatan Percobaan Login (Rate Limiting)
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `identifier` VARCHAR(254) NOT NULL,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ip_attempt` (`ip_address`, `attempted_at`),
    INDEX `idx_ident_attempt` (`identifier`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

