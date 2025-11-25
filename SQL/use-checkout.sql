-- ============================================
-- Migracja systemu kont u¿ytkowników
-- Data: 2024
-- UWAGA: Najpierw tworzy tabelê users, potem resztê
-- ============================================

USE sersoltec_db;

-- ============================================
-- TABELA U¯YTKOWNIKÓW (users)
-- ============================================
ALTER TABLE orders MODIFY user_id INT NULL DEFAULT NULL;
ALTER TABLE orders ADD full_name VARCHAR(255) NOT NULL AFTER user_id;
ALTER TABLE orders ADD email VARCHAR(255) NOT NULL AFTER full_name;
ALTER TABLE orders ADD phone VARCHAR(50) NOT NULL AFTER email;
ALTER TABLE orders ADD address TEXT NOT NULL AFTER phone;
ALTER TABLE orders ADD payment_method VARCHAR(50) NOT NULL AFTER total_amount;
ALTER TABLE orders MODIFY order_number VARCHAR(50) NULL;
ALTER TABLE orders MODIFY customer_name VARCHAR(255) NULL;
ALTER TABLE orders MODIFY customer_email VARCHAR(255) NULL;
ALTER TABLE order_items ADD product_name VARCHAR(255) NOT NULL AFTER product_id;
ALTER TABLE order_items ADD price_per_unit DECIMAL(10, 2) NOT NULL AFTER quantity;
ALTER TABLE order_items MODIFY price DECIMAL(10, 2) NULL;
ALTER TABLE order_items MODIFY subtotal DECIMAL(10, 2) NULL;