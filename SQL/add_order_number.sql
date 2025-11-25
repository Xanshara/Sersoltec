-- Dodanie kolumny order_number do tabeli orders

ALTER TABLE orders 

-- Utwórz indeks dla szybszego wyszukiwania
CREATE INDEX idx_order_number ON orders(order_number);

-- Opcjonalnie: wygeneruj order_number dla istniejących zamówień
UPDATE orders 
SET order_number = CONCAT('ORD-', DATE_FORMAT(created_at, '%Y%m%d'), '-', UPPER(SUBSTRING(MD5(CONCAT(id, RAND())), 1, 6)))
WHERE order_number IS NULL OR order_number = '';
