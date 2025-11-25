-- ================================================================
-- NAPRAWA BAZY DANYCH CHATBOTA - SERSOLTEC
-- ================================================================

-- 1. SPRAWDŹ OBECNĄ STRUKTURĘ TABELI INQUIRIES
DESCRIBE inquiries;

-- 2. OPCJA A: Jeśli tabela NIE MA kolumny 'name', dodaj ją
ALTER TABLE inquiries 
ADD COLUMN name VARCHAR(255) NULL AFTER id;

-- 3. OPCJA B: Jeśli tabela ma inną nazwę kolumny (np. 'full_name'), zmień nazwę
-- ALTER TABLE inquiries 
-- CHANGE COLUMN full_name name VARCHAR(255) NULL;

-- 4. OPCJA C: Jeśli chcesz utworzyć tabelę od nowa (UWAGA: usunie dane!)
/*
DROP TABLE IF EXISTS inquiries;
CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/

-- 5. Sprawdź czy tabela chat_history istnieje
CREATE TABLE IF NOT EXISTS chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    message TEXT NULL,
    response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Sprawdź strukturę tabeli products
DESCRIBE products;

-- 7. Upewnij się że products ma kolumnę 'active'
-- ALTER TABLE products 
-- ADD COLUMN active TINYINT(1) DEFAULT 1 AFTER description;

-- 8. Sprawdź dane
SELECT COUNT(*) as total_inquiries FROM inquiries;
SELECT COUNT(*) as total_products FROM products;
SELECT COUNT(*) as total_chat_history FROM chat_history;

-- 9. Zobacz ostatnie inquiries
SELECT id, name, email, subject, message, created_at 
FROM inquiries 
ORDER BY created_at DESC 
LIMIT 10;

-- ================================================================
-- TESTY
-- ================================================================

-- Test INSERT z kolumną 'name'
INSERT INTO inquiries (name, email, subject, message, ip_address) 
VALUES ('Test User', 'test@test.com', 'Test Subject', 'Test message', '127.0.0.1');

-- Sprawdź czy zadziałało
SELECT * FROM inquiries ORDER BY id DESC LIMIT 1;

-- Usuń test
DELETE FROM inquiries WHERE email = 'test@test.com' AND message = 'Test message';

-- ================================================================
-- INFORMACJE
-- ================================================================

-- Sprawdź wszystkie kolumny w inquiries
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_KEY
FROM 
    INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'inquiries'
ORDER BY 
    ORDINAL_POSITION;
