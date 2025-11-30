-- =============================================
-- SERSOLTEC - STRUKTURA I MIGRACJA BAZY DANYCH
-- Skrypt połączony z plików: SETUP.sql, USER-MIGRATION.sql, 
-- ADMIN-MIGRATION.sql, use-checkout.sql, CART-MIGRATION-SIMPLE.sql, 
-- fix_old_orders.sql, fix-database.sql
-- =============================================

-- =============================================
-- 1. TWORZENIE BAZY DANYCH I UŻYCIE
-- =============================================
CREATE DATABASE IF NOT EXISTS `sersoltec_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sersoltec_db`;

-- =============================================
-- 2. TWORZENIE TABEL PODSTAWOWYCH (SETUP.sql)
-- =============================================

-- KATEGORIE PRODUKTÓW
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `name_pl` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_es` VARCHAR(255) NOT NULL,
  `description_pl` TEXT,
  `description_en` TEXT,
  `description_es` TEXT,
  `icon` VARCHAR(50),
  `order` INT DEFAULT 0,
  `active` TINYINT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRODUKTY
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `category_id` INT NOT NULL,
  `sku` VARCHAR(100) UNIQUE NOT NULL,
  `name_pl` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_es` VARCHAR(255) NOT NULL,
  `description_pl` TEXT,
  `description_en` TEXT,
  `description_es` TEXT,
  `specifications` JSON,
  `price_base` DECIMAL(10, 2),
  `price_min` DECIMAL(10, 2),
  `price_max` DECIMAL(10, 2),
  `unit` VARCHAR(50),
  `image` VARCHAR(255),
  `images` JSON,
  `weight` DECIMAL(10, 3),
  `active` TINYINT DEFAULT 1,
  `b2b_only` TINYINT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  INDEX (`category_id`),
  INDEX (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ZAMÓWIENIA (orders) - Początkowa struktura
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `order_number` VARCHAR(50) UNIQUE, -- Zmienione na NULL na start, bo jest modyfikowane później
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_email` VARCHAR(255) NOT NULL,
  `customer_phone` VARCHAR(20),
  `customer_company` VARCHAR(255),
  `customer_tax_id` VARCHAR(50),
  `message` TEXT,
  `products` JSON,
  `total_items` INT,
  `notes` TEXT,
  `ip_address` VARCHAR(45),
  `status` ENUM('pending', 'confirmed', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`created_at`),
  INDEX (`status`),
  INDEX (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KONTAKT / ZAPYTANIA (inquiries)
CREATE TABLE IF NOT EXISTS `inquiries` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `company` VARCHAR(255),
  `subject` VARCHAR(255),
  `message` TEXT NOT NULL,
  `product_id` INT,
  `ip_address` VARCHAR(45),
  `status` ENUM('new', 'read', 'replied') DEFAULT 'new',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
  INDEX (`created_at`),
  INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KALKULACJA OKIEN
CREATE TABLE IF NOT EXISTS `window_calculations` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `customer_email` VARCHAR(255),
  `width` DECIMAL(10, 2),
  `height` DECIMAL(10, 2),
  `type` VARCHAR(50),
  `material` VARCHAR(50),
  `glass_type` VARCHAR(50),
  `opening_type` VARCHAR(50),
  `quantity` INT,
  `estimated_price` DECIMAL(10, 2),
  `data` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- USTAWIENIA SKLEPU
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` LONGTEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- 3. TWORZENIE TABEL Z MIGRACJI (USER-MIGRATION.sql, ADMIN-MIGRATION.sql)
-- =============================================

-- TABELA UŻYTKOWNIKÓW (users)
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

-- TABELA UŻYTKOWNIKÓW ADMINA (admin_users)
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

-- TABELA POZYCJI ZAMÓWIENIA (order_items)
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

-- TABELA HISTORII CHATBOTA (chat_history)
CREATE TABLE IF NOT EXISTS chat_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    message TEXT NULL,
    response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================
-- 4. MODYFIKACJE I MIGRACJE STRUKTUR TABELI ORDERS
-- =============================================

-- USER-MIGRATION.sql: Dodaj kolumny user_id i total_amount
SET @exist_user_id := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'user_id');
SET @sqlstmt_user_id := IF(@exist_user_id = 0, 
    'ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL AFTER id; ALTER TABLE orders ADD INDEX idx_user_id (user_id);',
    'SELECT "Column user_id already exists" AS message_user_id');
PREPARE stmt_user_id FROM @sqlstmt_user_id;
EXECUTE stmt_user_id;
DEALLOCATE PREPARE stmt_user_id;

SET @exist_total_amount := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'total_amount');
SET @sqlstmt_total_amount := IF(@exist_total_amount = 0, 
    'ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2) DEFAULT 0.00 AFTER products',
    'SELECT "Column total_amount already exists" AS message_total_amount');
PREPARE stmt_total_amount FROM @sqlstmt_total_amount;
EXECUTE stmt_total_amount;
DEALLOCATE PREPARE stmt_total_amount;

-- Dodaj klucz obcy dla user_id (z USER-MIGRATION.sql)
SET @exist_fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'orders' 
    AND CONSTRAINT_NAME = 'fk_orders_user' 
    AND CONSTRAINT_TYPE = 'FOREIGN KEY');

SET @sqlstmt_fk := IF(@exist_fk = 0, 
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_orders_user already exists" AS message_fk');

PREPARE stmt_fk FROM @sqlstmt_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;

-- use-checkout.sql: Modyfikacje dla procesu Checkout
ALTER TABLE orders MODIFY user_id INT NULL DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS full_name VARCHAR(255) NOT NULL AFTER user_id;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL AFTER full_name;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NOT NULL AFTER email;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS address TEXT NOT NULL AFTER phone;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NOT NULL AFTER total_amount;
ALTER TABLE orders MODIFY order_number VARCHAR(50) NULL;
ALTER TABLE orders MODIFY customer_name VARCHAR(255) NULL;
ALTER TABLE orders MODIFY customer_email VARCHAR(255) NULL;

-- use-checkout.sql: Modyfikacje dla order_items (choć nowa tabela, to ma modyfikacje)
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS product_name VARCHAR(255) NOT NULL AFTER product_id;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS price_per_unit DECIMAL(10, 2) NOT NULL AFTER quantity;
ALTER TABLE order_items MODIFY price DECIMAL(10, 2) NULL; -- Zmieniono z NOT NULL na NULL
ALTER TABLE order_items MODIFY subtotal DECIMAL(10, 2) NULL; -- Zmieniono z NOT NULL na NULL

-- fix-database.sql: Zapewnienie kolumny 'name' w inquiries
SET @exist_inquiries_name := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inquiries' AND COLUMN_NAME = 'name');
SET @sqlstmt_inquiries_name := IF(@exist_inquiries_name = 0, 
    'ALTER TABLE inquiries ADD COLUMN name VARCHAR(255) NULL AFTER id',
    'SELECT "Column name already exists in inquiries" AS message_inquiries_name');
PREPARE stmt_inquiries_name FROM @sqlstmt_inquiries_name;
EXECUTE stmt_inquiries_name;
DEALLOCATE PREPARE stmt_inquiries_name;

-- CART-MIGRATION-SIMPLE.sql: Dodanie kolumny customer_address
SET @exist_address := (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_address');
SET @sqlstmt_address := IF(@exist_address = 0, 
    'ALTER TABLE `orders` ADD COLUMN `customer_address` TEXT AFTER `customer_tax_id`',
    'SELECT "Column customer_address already exists" AS message_customer_address');
PREPARE stmt_address FROM @sqlstmt_address;
EXECUTE stmt_address;
DEALLOCATE PREPARE stmt_address;


-- =============================================
-- 5. WSTAWIANIE DANYCH PRZYKŁADOWYCH I DOMYŚLNYCH
-- =============================================

-- USTAWIENIA DOMYŚLNE (SETUP.sql)
INSERT INTO `settings` (`key`, `value`) VALUES
('company_name', 'Sersoltec'),
('company_phone', '+34 XXX XXX XXX'),
('company_email', 'info@sersoltec.eu'),
('company_address', 'Valencia, Spain'),
('vat_id', 'ES12345678A'),
('currency', 'EUR'),
('tax_rate', '21')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- WSTAWIANIE KATEGORII PRZYKŁADOWYCH (SETUP.sql)
INSERT INTO `categories` (`slug`, `name_pl`, `name_en`, `name_es`, `icon`, `order`) VALUES
('okna-pvc', 'Okna PVC', 'PVC Windows', 'Ventanas PVC', '🪟', 1),
('okna-drewniane', 'Okna Drewniane', 'Wooden Windows', 'Ventanas de Madera', '🪟', 2),
('panele-grzewcze', 'Panele Grzewcze', 'Heating Panels', 'Paneles Calefactores', '🔥', 3),
('folie-grzewcze', 'Folie Grzewcze', 'Heating Films', 'Películas Calefactoras', '🔥', 4),
('profile-pvc', 'Profile PVC', 'PVC Profiles', 'Perfiles PVC', '📐', 5),
('drzwi-wewnetrzne', 'Drzwi Wewnętrzne', 'Interior Doors', 'Puertas Interiores', '🚪', 6),
('drzwi-zewnetrzne', 'Drzwi Zewnętrzne', 'Exterior Doors', 'Puertas Exteriores', '🚪', 7),
('akcesoria', 'Akcesoria', 'Accessories', 'Accesorios', '⚙️', 8),
('projektowanie', 'Projektowanie', 'Design Services', 'Servicios de Diseño', '🎨', 9)
ON DUPLICATE KEY UPDATE name_pl = VALUES(name_pl);

-- PRODUKTY PRZYKŁADOWE (SETUP.sql) - Pomijam ON DUPLICATE, bo SKU jest UNIQUE
-- Upewnij się, że masz ID kategorii: PVC=1, DREWNO=2, PANEL=3, FOLIA=4
SET @cat_pvc = (SELECT id FROM categories WHERE slug = 'okna-pvc' LIMIT 1);
SET @cat_drewno = (SELECT id FROM categories WHERE slug = 'okna-drewniane' LIMIT 1);
SET @cat_panel = (SELECT id FROM categories WHERE slug = 'panele-grzewcze' LIMIT 1);
SET @cat_folia = (SELECT id FROM categories WHERE slug = 'folie-grzewcze' LIMIT 1);

INSERT INTO `products` (`category_id`, `sku`, `name_pl`, `name_en`, `name_es`, `description_pl`, `description_en`, `description_es`, `specifications`, `price_base`, `unit`, `active`) VALUES
(COALESCE(@cat_pvc, 1), 'PVC-WIN-001', 'Okno PVC 90x120 - Tilt & Turn', 'PVC Window 90x120 - Tilt & Turn', 'Ventana PVC 90x120 - Oscilobatiente', 'Nowoczesne okno PVC z funkcją uchylania i całkowitego otwarcia. Doskonała izolacja termiczna.', 'Modern PVC window with tilt and turn function. Excellent thermal insulation.', 'Moderna ventana PVC con función oscilobatiente. Excelente aislamiento térmico.', '{"width": "900mm", "height": "1200mm", "frames": 1, "glass": "Double", "u_value": "1.1"}', 450.00, 'szt', 1),
(COALESCE(@cat_pvc, 1), 'PVC-WIN-002', 'Okno PVC 150x150 - Tilt & Turn', 'PVC Window 150x150 - Tilt & Turn', 'Ventana PVC 150x150 - Oscilobatiente', 'Duże okno PVC do salonu. Profilownie z wzmocnieniami. Wysoka nośność szyb.', 'Large PVC window for living room. Reinforced frames. High glass load capacity.', 'Gran ventana PVC para sala de estar. Marcos reforzados. Alta capacidad de carga de vidrio.', '{"width": "1500mm", "height": "1500mm", "frames": 1, "glass": "Triple", "u_value": "0.9"}', 850.00, 'szt', 1),
(COALESCE(@cat_pvc, 1), 'PVC-WIN-003', 'Okno PVC Narożne 200x200', 'Corner PVC Window 200x200', 'Ventana PVC Esquina 200x200', 'Okno narożne do nowoczesnych wnętrz. Idealne do przestronnych przestrzeni. Minimalistyczne ramy.', 'Corner window for modern interiors. Ideal for spacious spaces. Minimalist frames.', 'Ventana esquinera para interiores modernos. Ideal para espacios amplios. Marcos minimalistas.', '{"width": "2000mm", "height": "2000mm", "frames": 2, "glass": "Triple", "u_value": "0.85"}', 1600.00, 'szt', 1),
(COALESCE(@cat_drewno, 2), 'WOOD-WIN-001', 'Okno Drewniane Sosnowe 90x120', 'Pine Wooden Window 90x120', 'Ventana de Madera de Pino 90x120', 'Tradycyjne okno drewniane z sosny. Naturalna izolacja. Piękny wygląd.', 'Traditional wooden window made of pine. Natural insulation. Beautiful appearance.', 'Ventana de madera tradicional de pino. Aislamiento natural. Hermosa apariencia.', '{"width": "900mm", "height": "1200mm", "wood": "Pine", "glass": "Double"}', 550.00, 'szt', 1),
(COALESCE(@cat_drewno, 2), 'WOOD-WIN-002', 'Okno Drewniane Klejone Dąb 150x150', 'Glued Wooden Window Oak 150x150', 'Ventana de Madera Encolada Roble 150x150', 'Ekskluzywne okno z naturalnego drewna dębu. Prestiżowy wygląd. Najwyższa jakość.', 'Exclusive window made from natural oak wood. Prestigious appearance. Highest quality.', 'Ventana exclusiva de madera de roble natural. Apariencia prestigiosa. Máxima calidad.', '{"width": "1500mm", "height": "1500mm", "wood": "Oak", "glass": "Triple"}', 1200.00, 'szt', 1),
(COALESCE(@cat_drewno, 2), 'WOOD-WIN-003', 'Okno Drewniane Laminowane 200x200', 'Laminated Wooden Window 200x200', 'Ventana de Madera Laminada 200x200', 'Nowoczesne okno drewniane z powłoką laminowaną. Łatwa konserwacja. Długowieczność.', 'Modern wooden window with laminated coating. Easy maintenance. Longevity.', 'Moderna ventana de madera con revestimiento laminado. Fácil mantenimiento. Longevidad.', '{"width": "2000mm", "height": "2000mm", "wood": "Laminated", "glass": "Triple"}', 2000.00, 'szt', 1),
(COALESCE(@cat_panel, 3), 'HEAT-PANEL-001', 'Panel Grzewczy 600W', 'Heating Panel 600W', 'Panel Calefactor 600W', 'Nowoczesny panel grzewczy do pomieszczeń średnich. Efektywny, cichy, bezpieczny.', 'Modern heating panel for medium rooms. Efficient, quiet, safe.', 'Panel calefactor moderno para habitaciones medianas. Eficiente, silencioso, seguro.', '{"power": "600W", "dimensions": "600x800mm", "weight": "3.5kg"}', 200.00, 'szt', 1),
(COALESCE(@cat_panel, 3), 'HEAT-PANEL-002', 'Panel Grzewczy 1000W', 'Heating Panel 1000W', 'Panel Calefactor 1000W', 'Panel grzewczy dla dużych pomieszczeń. Termostat wbudowany. Oszczędny w prądzie.', 'Heating panel for large rooms. Built-in thermostat. Power saving.', 'Panel calefactor para habitaciones grandes. Termostato incorporado. Ahorro de energía.', '{"power": "1000W", "dimensions": "800x1000mm", "weight": "5kg"}', 350.00, 'szt', 1),
(COALESCE(@cat_panel, 3), 'HEAT-PANEL-003', 'Panel Grzewczy Sufitowy 2000W', 'Ceiling Heating Panel 2000W', 'Panel Calefactor de Techo 2000W', 'Zaawansowany panel sufitowy do pomieszczeń komercyjnych i dużych pomieszczeń mieszkalnych.', 'Advanced ceiling panel for commercial spaces and large residential rooms.', 'Panel de techo avanzado para espacios comerciales y grandes salas residenciales.', '{"power": "2000W", "dimensions": "1200x1200mm", "weight": "8kg"}', 650.00, 'szt', 1),
(COALESCE(@cat_folia, 4), 'HEAT-FILM-001', 'Folia Grzewcza 50m x 0.5m', 'Heating Film 50m x 0.5m', 'Película Calefactora 50m x 0.5m', 'Elastyczna folia grzewcza do montażu pod podłogę. Efektywna, uniwersalna.', 'Flexible heating film for under-floor installation. Efficient, universal.', 'Película calefactora flexible para instalación bajo piso. Eficiente, universal.', '{"length": "50m", "width": "0.5m", "power": "100W/m2"}', 150.00, 'rol', 1),
(COALESCE(@cat_folia, 4), 'HEAT-FILM-002', 'Folia Grzewcza 100m x 1m', 'Heating Film 100m x 1m', 'Película Calefactora 100m x 1m', 'Duża rola folii grzewczej. Idealna do gruntów dużych powierzchni. Równomierny rozkład ciepła.', 'Large roll of heating film. Ideal for large floor areas. Even heat distribution.', 'Gran rollo de película calefactora. Ideal para grandes áreas de piso. Distribución uniforme del calor.', '{"length": "100m", "width": "1m", "power": "120W/m2"}', 350.00, 'rol', 1),
(COALESCE(@cat_folia, 4), 'HEAT-FILM-003', 'Folia Grzewcza Sufitowa Premium 30m', 'Premium Ceiling Heating Film 30m', 'Película Calefactora de Techo Premium 30m', 'Profesjonalna folia do montażu na suficie. Wysoka moc. Bezpieczna technologia.', 'Professional film for ceiling installation. High power. Safe technology.', 'Película profesional para instalación de techo. Alta potencia. Tecnología segura.', '{"length": "30m", "width": "1m", "power": "200W/m2"}', 450.00, 'rol', 1)
ON DUPLICATE KEY UPDATE name_pl = VALUES(name_pl);


-- DOMYŚLNY UŻYTKOWNIK ADMIN (ADMIN-MIGRATION.sql)
INSERT INTO `admin_users` (`username`, `email`, `password`, `role`, `active`) VALUES
('admin', 'admin@sersoltec.eu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 1)
ON DUPLICATE KEY UPDATE username = username;

-- UŻYTKOWNIK TESTOWY (USER-MIGRATION.sql)
-- Login: test@sersoltec.eu, Hasło: test123
SET @test_user_exists := (SELECT COUNT(*) FROM users WHERE email = 'test@sersoltec.eu');
INSERT INTO users (first_name, last_name, email, password, role, created_at)
SELECT 'Test', 'User', 'test@sersoltec.eu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()
WHERE @test_user_exists = 0;


-- =============================================
-- 6. NAPRAWY I AKTUALIZACJA ISTNIEJĄCYCH DANYCH
-- =============================================

-- fix_old_orders.sql: Wygeneruj order_number dla starych zamówień, które go nie mają
UPDATE orders 
SET order_number = CONCAT('ORD-', DATE_FORMAT(created_at, '%Y%m%d'), '-', UPPER(SUBSTRING(MD5(CONCAT(id, UNIX_TIMESTAMP())), 1, 6)))
WHERE order_number IS NULL OR order_number = '';

-- CART-MIGRATION-SIMPLE.sql / CART-MIGRATION-SIMPLE2.sql: Aktualizacja adresu
-- Ta operacja wyciąga adres z początku message jeśli tam został zapisany
UPDATE `orders` 
SET `customer_address` = SUBSTRING_INDEX(SUBSTRING_INDEX(message, '\n\n', 1), 'Adres: ', -1),
    `message` = TRIM(SUBSTRING(message, LOCATE('\n\n', message) + 2))
WHERE `customer_address` IS NULL 
  AND `message` LIKE 'Adres:%'
  AND LOCATE('\n\n', message) > 0;

-- =============================================
-- 7. INFORMACJE KOŃCOWE I TESTY
-- =============================================

SELECT 'Migracja zakończona pomyślnie!' AS status;
SELECT COUNT(*) AS total_users FROM users;
SELECT COUNT(*) AS total_orders FROM orders;
SELECT COUNT(*) AS total_admin_users FROM admin_users;
SELECT 
    'Tabele utworzone/zaktualizowane:' AS info,
    'categories, products, orders, inquiries, window_calculations, settings, users, order_items, chat_history, admin_users' AS tables;
SELECT 
    'Użytkownik testowy:' AS info,
    'Email: test@sersoltec.eu, Hasło: test123' AS credentials;
SELECT 
    'Użytkownik admin:' AS info,
    'Login: admin, Hasło: admin123' AS credentials;

-- Przykład testu INSERT z fix-database.sql
INSERT INTO inquiries (name, email, subject, message, ip_address) 
VALUES ('Test User Insert', 'test@test.com', 'Test Subject', 'Test message', '127.0.0.1');
SELECT * FROM inquiries ORDER BY id DESC LIMIT 1;
DELETE FROM inquiries WHERE email = 'test@test.com' AND message = 'Test message';

-- Sprawdzenie ostatnich zamówień (fix_old_orders.sql)
SELECT id, order_number, full_name, email, created_at FROM orders ORDER BY id DESC LIMIT 20;

-- DONE!