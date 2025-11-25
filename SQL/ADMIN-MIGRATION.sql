-- =============================================
-- ADMIN PANEL - MIGRATION SQL
-- Dodaj tę tabelę do istniejącej bazy
-- =============================================

-- Tabela użytkowników admina
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(100) UNIQUE NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'superadmin') DEFAULT 'admin',
  `active` TINYINT DEFAULT 1,
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`username`),
  INDEX (`email`),
  INDEX (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Domyślny użytkownik admin
-- Login: admin
-- Hasło: admin123
-- ⚠️ ZMIEŃ HASŁO PO PIERWSZYM LOGOWANIU!
INSERT INTO `admin_users` (`username`, `email`, `password`, `role`, `active`) VALUES
('admin', 'admin@sersoltec.eu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 1)
ON DUPLICATE KEY UPDATE username = username;

-- =============================================
-- DONE!
-- =============================================
-- Możesz teraz zalogować się do panelu admin:
-- URL: /admin/login.php
-- Login: admin
-- Hasło: admin123
