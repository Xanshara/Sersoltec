-- =============================================
-- SERSOLTEC - Struktura Bazy Danych
-- Uruchom to w phpMyAdmin lub konsoli MySQL
-- =============================================

-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS `sersoltec_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sersoltec_db`;

-- =============================================
-- KATEGORII PRODUKTÓW
-- =============================================
CREATE TABLE `categories` (
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

-- =============================================
-- PRODUKTY
-- =============================================
CREATE TABLE `products` (
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

-- =============================================
-- ZAMÓWIENIA
-- =============================================
CREATE TABLE `orders` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `order_number` VARCHAR(50) UNIQUE NOT NULL,
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

-- =============================================
-- KONTAKT / ZAPYTANIA
-- =============================================
CREATE TABLE `inquiries` (
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

-- =============================================
-- KALKULACJA OKIEN
-- =============================================
CREATE TABLE `window_calculations` (
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

-- =============================================
-- USTAWIENIA SKLEPU
-- =============================================
CREATE TABLE `settings` (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` LONGTEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- WSTAWIANIE KATEGORII PRZYKŁADOWYCH
-- =============================================
INSERT INTO `categories` (`slug`, `name_pl`, `name_en`, `name_es`, `icon`, `order`) VALUES
('okna-pvc', 'Okna PVC', 'PVC Windows', 'Ventanas PVC', '🪟', 1),
('okna-drewniane', 'Okna Drewniane', 'Wooden Windows', 'Ventanas de Madera', '🪟', 2),
('panele-grzewcze', 'Panele Grzewcze', 'Heating Panels', 'Paneles Calefactores', '🔥', 3),
('folie-grzewcze', 'Folie Grzewcze', 'Heating Films', 'Películas Calefactoras', '🔥', 4),
('profile-pvc', 'Profile PVC', 'PVC Profiles', 'Perfiles PVC', '📐', 5),
('drzwi-wewnetrzne', 'Drzwi Wewnętrzne', 'Interior Doors', 'Puertas Interiores', '🚪', 6),
('drzwi-zewnetrzne', 'Drzwi Zewnętrzne', 'Exterior Doors', 'Puertas Exteriores', '🚪', 7),
('akcesoria', 'Akcesoria', 'Accessories', 'Accesorios', '⚙️', 8),
('projektowanie', 'Projektowanie', 'Design Services', 'Servicios de Diseño', '🎨', 9);

-- =============================================
-- PRODUKTY PRZYKŁADOWE
-- =============================================

-- Okna PVC
INSERT INTO `products` (`category_id`, `sku`, `name_pl`, `name_en`, `name_es`, `description_pl`, `description_en`, `description_es`, `specifications`, `price_base`, `unit`, `active`) VALUES
(1, 'PVC-WIN-001', 'Okno PVC 90x120 - Tilt & Turn', 'PVC Window 90x120 - Tilt & Turn', 'Ventana PVC 90x120 - Oscilobatiente', 'Nowoczesne okno PVC z funkcją uchylania i całkowitego otwarcia. Doskonała izolacja termiczna.', 'Modern PVC window with tilt and turn function. Excellent thermal insulation.', 'Moderna ventana PVC con función oscilobatiente. Excelente aislamiento térmico.', '{"width": "900mm", "height": "1200mm", "frames": 1, "glass": "Double", "u_value": "1.1"}', 450.00, 'szt', 1),
(1, 'PVC-WIN-002', 'Okno PVC 150x150 - Tilt & Turn', 'PVC Window 150x150 - Tilt & Turn', 'Ventana PVC 150x150 - Oscilobatiente', 'Duże okno PVC do salonu. Profilownie z wzmocnieniami. Wysoka nośność szyb.', 'Large PVC window for living room. Reinforced frames. High glass load capacity.', 'Gran ventana PVC para sala de estar. Marcos reforzados. Alta capacidad de carga de vidrio.', '{"width": "1500mm", "height": "1500mm", "frames": 1, "glass": "Triple", "u_value": "0.9"}', 850.00, 'szt', 1),
(1, 'PVC-WIN-003', 'Okno PVC Narożne 200x200', 'Corner PVC Window 200x200', 'Ventana PVC Esquina 200x200', 'Okno narożne do nowoczesnych wnętrz. Idealne do przestronnych przestrzeni. Minimalistyczne ramy.', 'Corner window for modern interiors. Ideal for spacious spaces. Minimalist frames.', 'Ventana esquinera para interiores modernos. Ideal para espacios amplios. Marcos minimalistas.', '{"width": "2000mm", "height": "2000mm", "frames": 2, "glass": "Triple", "u_value": "0.85"}', 1600.00, 'szt', 1);

-- Okna Drewniane
INSERT INTO `products` (`category_id`, `sku`, `name_pl`, `name_en`, `name_es`, `description_pl`, `description_en`, `description_es`, `specifications`, `price_base`, `unit`, `active`) VALUES
(2, 'WOOD-WIN-001', 'Okno Drewniane Sosnowe 90x120', 'Pine Wooden Window 90x120', 'Ventana de Madera de Pino 90x120', 'Tradycyjne okno drewniane z sosny. Naturalna izolacja. Piękny wygląd.', 'Traditional wooden window made of pine. Natural insulation. Beautiful appearance.', 'Ventana de madera tradicional de pino. Aislamiento natural. Hermosa apariencia.', '{"width": "900mm", "height": "1200mm", "wood": "Pine", "glass": "Double"}', 550.00, 'szt', 1),
(2, 'WOOD-WIN-002', 'Okno Drewniane Klejone Dąb 150x150', 'Glued Wooden Window Oak 150x150', 'Ventana de Madera Encolada Roble 150x150', 'Ekskluzywne okno z naturalnego drewna dębu. Prestiżowy wygląd. Najwyższa jakość.', 'Exclusive window made from natural oak wood. Prestigious appearance. Highest quality.', 'Ventana exclusiva de madera de roble natural. Apariencia prestigiosa. Máxima calidad.', '{"width": "1500mm", "height": "1500mm", "wood": "Oak", "glass": "Triple"}', 1200.00, 'szt', 1),
(2, 'WOOD-WIN-003', 'Okno Drewniane Laminowane 200x200', 'Laminated Wooden Window 200x200', 'Ventana de Madera Laminada 200x200', 'Nowoczesne okno drewniane z powłoką laminowaną. Łatwa konserwacja. Długowieczność.', 'Modern wooden window with laminated coating. Easy maintenance. Longevity.', 'Moderna ventana de madera con revestimiento laminado. Fácil mantenimiento. Longevidad.', '{"width": "2000mm", "height": "2000mm", "wood": "Laminated", "glass": "Triple"}', 2000.00, 'szt', 1);

-- Panele Grzewcze
INSERT INTO `products` (`category_id`, `sku`, `name_pl`, `name_en`, `name_es`, `description_pl`, `description_en`, `description_es`, `specifications`, `price_base`, `unit`, `active`) VALUES
(3, 'HEAT-PANEL-001', 'Panel Grzewczy 600W', 'Heating Panel 600W', 'Panel Calefactor 600W', 'Nowoczesny panel grzewczy do pomieszczeń średnich. Efektywny, cichy, bezpieczny.', 'Modern heating panel for medium rooms. Efficient, quiet, safe.', 'Panel calefactor moderno para habitaciones medianas. Eficiente, silencioso, seguro.', '{"power": "600W", "dimensions": "600x800mm", "weight": "3.5kg"}', 200.00, 'szt', 1),
(3, 'HEAT-PANEL-002', 'Panel Grzewczy 1000W', 'Heating Panel 1000W', 'Panel Calefactor 1000W', 'Panel grzewczy dla dużych pomieszczeń. Termostat wbudowany. Oszczędny w prądzie.', 'Heating panel for large rooms. Built-in thermostat. Power saving.', 'Panel calefactor para habitaciones grandes. Termostato incorporado. Ahorro de energía.', '{"power": "1000W", "dimensions": "800x1000mm", "weight": "5kg"}', 350.00, 'szt', 1),
(3, 'HEAT-PANEL-003', 'Panel Grzewczy Sufitowy 2000W', 'Ceiling Heating Panel 2000W', 'Panel Calefactor de Techo 2000W', 'Zaawansowany panel sufitowy do pomieszczeń komercyjnych i dużych pomieszczeń mieszkalnych.', 'Advanced ceiling panel for commercial spaces and large residential rooms.', 'Panel de techo avanzado para espacios comerciales y grandes salas residenciales.', '{"power": "2000W", "dimensions": "1200x1200mm", "weight": "8kg"}', 650.00, 'szt', 1);

-- Folie Grzewcze
INSERT INTO `products` (`category_id`, `sku`, `name_pl`, `name_en`, `name_es`, `description_pl`, `description_en`, `description_es`, `specifications`, `price_base`, `unit`, `active`) VALUES
(4, 'HEAT-FILM-001', 'Folia Grzewcza 50m x 0.5m', 'Heating Film 50m x 0.5m', 'Película Calefactora 50m x 0.5m', 'Elastyczna folia grzewcza do montażu pod podłogę. Efektywna, uniwersalna.', 'Flexible heating film for under-floor installation. Efficient, universal.', 'Película calefactora flexible para instalación bajo piso. Eficiente, universal.', '{"length": "50m", "width": "0.5m", "power": "100W/m2"}', 150.00, 'rol', 1),
(4, 'HEAT-FILM-002', 'Folia Grzewcza 100m x 1m', 'Heating Film 100m x 1m', 'Película Calefactora 100m x 1m', 'Duża rola folii grzewczej. Idealna do gruntów dużych powierzchni. Równomierny rozkład ciepła.', 'Large roll of heating film. Ideal for large floor areas. Even heat distribution.', 'Gran rollo de película calefactora. Ideal para grandes áreas de piso. Distribución uniforme del calor.', '{"length": "100m", "width": "1m", "power": "120W/m2"}', 350.00, 'rol', 1),
(4, 'HEAT-FILM-003', 'Folia Grzewcza Sufitowa Premium 30m', 'Premium Ceiling Heating Film 30m', 'Película Calefactora de Techo Premium 30m', 'Profesjonalna folia do montażu na suficie. Wysoka moc. Bezpieczna technologia.', 'Professional film for ceiling installation. High power. Safe technology.', 'Película profesional para instalación de techo. Alta potencia. Tecnología segura.', '{"length": "30m", "width": "1m", "power": "200W/m2"}', 450.00, 'rol', 1);

-- =============================================
-- USTAWIENIA DOMYŚLNE
-- =============================================
INSERT INTO `settings` (`key`, `value`) VALUES
('company_name', 'Sersoltec'),
('company_phone', '+34 XXX XXX XXX'),
('company_email', 'info@sersoltec.eu'),
('company_address', 'Valencia, Spain'),
('vat_id', 'ES12345678A'),
('currency', 'EUR'),
('tax_rate', '21');

-- =============================================
-- DONE!
-- =============================================
-- Baza danych jest gotowa!
-- Wstawiło się 12 produktów w 4 kategoriach.
