-- Wygeneruj order_number dla starych zamówień które go nie mają

UPDATE orders 
SET order_number = CONCAT('ORD-', DATE_FORMAT(created_at, '%Y%m%d'), '-', UPPER(SUBSTRING(MD5(CONCAT(id, UNIX_TIMESTAMP())), 1, 6)))
WHERE order_number IS NULL OR order_number = '';

-- Sprawdź rezultat
SELECT id, order_number, full_name, email, created_at FROM orders ORDER BY id DESC LIMIT 20;
