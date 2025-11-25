-- =============================================
-- MIGRACJA: Dodanie kolumny adresu do tabeli orders
-- WERSJA UPROSZCZONA (bez sprawdzania istnienia)
-- =============================================

USE `sersoltec_db`;

-- UWAGA: Jeśli kolumna już istnieje, ten skrypt wyrzuci błąd
-- To normalne - po prostu pomiń ten błąd lub usuń tę linię jeśli kolumna istnieje

-- Jeśli dostajesz błąd "Duplicate column name 'customer_address'"
-- to znaczy że kolumna już istnieje i możesz pominąć powyższą komendę

-- =============================================
-- Opcjonalne: Aktualizacja istniejących rekordów
-- =============================================
-- Ta operacja wyciąga adres z początku message jeśli tam został zapisany
UPDATE `orders` 
SET `customer_address` = SUBSTRING_INDEX(SUBSTRING_INDEX(message, '\n\n', 1), 'Adres: ', -1),
    `message` = TRIM(SUBSTRING(message, LOCATE('\n\n', message) + 2))
WHERE `customer_address` IS NULL 
  AND `message` LIKE 'Adres:%'
  AND LOCATE('\n\n', message) > 0;

-- =============================================
-- DONE!
-- =============================================
