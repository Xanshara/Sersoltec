-- ============================================
-- Migracja systemu kont u¿ytkowników
-- Data: 2024
-- UWAGA: Najpierw tworzy tabelê users, potem resztê
-- ============================================

USE sersoltec_db;

-- ============================================
-- TABELA U¯YTKOWNIKÓW (users)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Aktualizacja tabeli orders
-- ============================================

-- Dodaj kolumnê user_id do istniej¹cej tabeli orders
ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL AFTER id;
ALTER TABLE orders ADD INDEX idx_user_id (user_id);

-- Dodaj klucz obcy (tylko jeœli nie istnieje)
SET @exist := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = 'sersoltec_db' 
    AND TABLE_NAME = 'orders' 
    AND CONSTRAINT_NAME = 'fk_orders_user' 
    AND CONSTRAINT_TYPE = 'FOREIGN KEY');

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Dodaj kolumnê total_amount do orders jeœli nie istnieje
SET @exist := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'sersoltec_db' 
    AND TABLE_NAME = 'orders' 
    AND COLUMN_NAME = 'total_amount');

SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0.00 AFTER products',
    'SELECT "Column total_amount already exists" AS message');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- Tabela pozycji zamówienia (order_items)
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela historii chatbota (chat_history)
-- ============================================
CREATE TABLE IF NOT EXISTS chat_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message TEXT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Przyk³adowe dane testowe - U¯YTKOWNIK TESTOWY
-- ============================================

-- SprawdŸ czy nie ma ju¿ u¿ytkownika testowego
SET @test_user_exists := (SELECT COUNT(*) FROM users WHERE email = 'test@sersoltec.eu');

-- Dodaj u¿ytkownika testowego jeœli nie istnieje
-- Login: test@sersoltec.eu
-- Has³o: test123
INSERT INTO users (first_name, last_name, email, password, role, created_at)
SELECT 'Test', 'User', 'test@sersoltec.eu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()
WHERE @test_user_exists = 0;

-- ============================================
-- Informacje o migracji
-- ============================================
SELECT 'Migracja zakoñczona pomyœlnie!' AS status;
SELECT COUNT(*) AS total_users FROM users;
SELECT COUNT(*) AS total_orders FROM orders;
SELECT 
    'Tabele utworzone/zaktualizowane:' AS info,
    'users (nowa), orders (zaktualizowana), order_items (nowa), chat_history (nowa)' AS tables;
SELECT 
    'U¿ytkownik testowy:' AS info,
    'Email: test@sersoltec.eu, Has³o: test123' AS credentials;
