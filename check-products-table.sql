-- ================================================================
-- SPRAWDZENIE STRUKTURY TABELI PRODUCTS
-- ================================================================

-- 1. Sprawdź strukturę tabeli products
DESCRIBE products;

-- 2. Zobacz nazwy wszystkich kolumn
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'products'
ORDER BY ORDINAL_POSITION;

-- 3. Sprawdź przykładowe dane
SELECT * FROM products LIMIT 3;

-- 4. Sprawdź czy są produkty aktywne
SELECT COUNT(*) as total_products FROM products;
SELECT COUNT(*) as active_products FROM products WHERE active = 1;

-- ================================================================
-- JEŚLI TABELA NIE ISTNIEJE - UTWÓRZ JĄ
-- ================================================================

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(100) UNIQUE NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    active TINYINT(1) DEFAULT 1,
    category VARCHAR(100) NULL,
    stock INT DEFAULT 0,
    image_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (active),
    INDEX idx_category (category),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- PRZYKŁADOWE PRODUKTY (OPCJONALNIE)
-- ================================================================

-- INSERT INTO products (name, description, price, active, category) VALUES
-- ('Okno PCV 120x120', 'Okno PCV białe, dwuszybowe', 899.00, 1, 'okna-pvc'),
-- ('Okno Drewniane 100x150', 'Okno sosnowe, energooszczędne', 1299.00, 1, 'okna-drewniane'),
-- ('Drzwi Wewnętrzne DRE', 'Drzwi bezprzylgowe, kolor biały', 399.00, 1, 'drzwi-wewnetrzne'),
-- ('Panel Grzewczy 600W', 'Panel na podczerwień, montaż ścienny', 499.00, 1, 'panele-grzewcze'),
-- ('Folia Grzewcza 1m²', 'Folia do ogrzewania podłogowego', 89.00, 1, 'folie-grzewcze');

-- ================================================================
-- TESTY
-- ================================================================

-- Test różnych wariantów zapytania
SELECT id, name, price, description FROM products WHERE active = 1 LIMIT 5;

-- Jeśli powyższe nie działa, sprawdź te:
-- SELECT id, product_name as name, price, description FROM products WHERE active = 1 LIMIT 5;
-- SELECT id, title as name, price, description FROM products WHERE active = 1 LIMIT 5;
-- SELECT id, name_pl as name, price, description FROM products WHERE active = 1 LIMIT 5;
