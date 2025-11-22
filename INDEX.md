# 📦 SERSOLTEC - Pełny Projekt

## 📂 Struktura Plików

```
sersoltec/
│
├── 📄 config.php                    # Konfiguracja główna (DB, EMAIL, SITE)
├── 📄 index.php                     # Strona główna
├── 📄 .htaccess                     # Apache rewrite rules
├── 📄 SETUP.sql                     # SQL do tworzenia bazy danych
│
├── 📂 includes/                     # Komponenty wspólne
│   ├── header.php                   # Nagłówek z nawigacją
│   ├── footer.php                   # Stopka
│   └── translations.php             # Tłumaczenia (PL/EN/ES)
│
├── 📂 pages/                        # Podstrony
│   ├── products.php                 # Katalog produktów + filtry
│   ├── product-detail.php           # Szczegóły jednego produktu
│   ├── calculator.php               # Kalkulator ceny okien
│   └── contact.php                  # Formularz kontaktowy
│
├── 📂 assets/                       # Zasoby statyczne
│   ├── css/
│   │   ├── styles.css               # Główne style (2000+ linii)
│   │   └── responsive.css           # Style responsywne
│   ├── js/
│   │   └── main.js                  # JavaScript (400+ linii)
│   └── images/
│       ├── logo.svg                 # Logo w SVG
│       └── products/                # Zdjęcia produktów (folder pusty)
│
├── 📂 admin/                        # Panel administracyjny (pusty, do rozbudowy)
│
├── 📚 DOKUMENTACJA
│   ├── README.md                    # Pełna dokumentacja
│   ├── QUICK-START.md               # Szybki start (5 minut)
│   ├── INDEX.md                     # Ten plik
│   └── FILES-LIST.txt               # Lista plików
```

---

## 🎯 Funkcjonalności

### ✅ Zrealizowane

- [x] Multi-language support (PL/EN/ES)
- [x] Strona główna z hero section
- [x] Katalog produktów z filtrami
- [x] Szczegóły produktu
- [x] Kalkulator ceny okien
- [x] Formularz kontaktowy (z email)
- [x] Responsywny design
- [x] Minimalistyczny design (ciemnozielony)
- [x] Logo w SVG
- [x] Mobile-first approach
- [x] Bezpieczeństwo (prepared statements)
- [x] CSS animations
- [x] Smooth scrolling
- [x] SEO basic

### 📋 Do Zrobienia

- [ ] Panel administratora (CRUD produkty)
- [ ] System płatności (Stripe/PayPal)
- [ ] Koszyk zakupowy
- [ ] User accounts
- [ ] Opinie klientów
- [ ] Blog/artykuły
- [ ] Wyszukiwanie zaawansowane
- [ ] Eksport katalogów (PDF)
- [ ] Integracja z CRM
- [ ] Analytics (GA4)
- [ ] Chatbot
- [ ] Social media links

---

## 🛠️ Technologia

| Technologia | Wersja | Uwagi |
|------------|--------|-------|
| PHP | 7.4+ | OOP, Prepared Statements |
| MySQL | 5.7+ | UTF8MB4 |
| HTML5 | - | Semantic |
| CSS3 | - | Grid, Flexbox, Custom Properties |
| JavaScript | ES6+ | Vanilla JS (bez bibliotek) |
| SVG | - | Responsive Logo |

---

## 🎨 Design System

### Kolory
- **Primary:** `#1a4d2e` (ciemnozielony)
- **Primary Dark:** `#0f3d25` (ciemnozielony ciemny)
- **Accent:** `#8b9467` (złotawy)
- **Light Gray:** `#f8f8f8`
- **Text:** `#2c2c2c`

### Typography
- **Serif:** Georgia, Garamond (headings)
- **Sans:** System fonts (body)
- **Base Size:** 16px

### Spacing
- `--spacing-xs: 0.5rem`
- `--spacing-sm: 1rem`
- `--spacing-md: 1.5rem`
- `--spacing-lg: 2rem`
- `--spacing-xl: 3rem`
- `--spacing-xxl: 4rem`

---

## 📝 Zawartość Bazy Danych

### Tabele

| Tabela | Opis | Rekordów |
|--------|------|---------|
| `categories` | Kategorie produktów | 9 |
| `products` | Produkty | 12 (example) |
| `orders` | Zamówienia | 0 |
| `inquiries` | Zapytania | 0 |
| `window_calculations` | Historia obliczeń | 0 |
| `settings` | Ustawienia globalne | 8 |

### Kategorie
1. Okna PVC
2. Okna Drewniane
3. Panele Grzewcze
4. Folie Grzewcze
5. Profile PVC
6. Drzwi Wewnętrzne
7. Drzwi Zewnętrzne
8. Akcesoria
9. Projektowanie

---

## 🌐 Multi-Language

Wszystkie stringi UI są w `includes/translations.php`:

```php
$translations = [
    'pl' => [ /* polskie napisy */ ],
    'en' => [ /* angielskie napisy */ ],
    'es' => [ /* hiszpańskie napisy */ ]
];
```

Przełączanie: `?lang=pl|en|es`

---

## 📧 Formularz Kontaktowy

Wysyła email na: `CONTACT_EMAIL` (zdefiniowane w `config.php`)

Pola:
- Imię i Nazwisko (wymagane)
- Email (wymagane, walidacja)
- Telefon (opcjonalne)
- Firma (opcjonalne)
- NIP (opcjonalne)
- Temat (opcjonalne)
- Wiadomość (wymagane)

---

## 🧮 Kalkulator Ceny Okien

Parametry:
- Szerokość (mm)
- Wysokość (mm)
- Typ okna (1/2/3 skrzydła)
- Materiał (PVC/Wood/Aluminium)
- Szyba (podwójna/potrójna)
- Otwarcie (uchyl-obracane/nieruchome/przesuwne)
- Ilość sztuk

Wzór: `cena = base_price * m2 * material_factor * glass_factor * opening_factor * qty`

Wynik zapisywany do bazy (`window_calculations`)

---

## 🔐 Bezpieczeństwo

### Implementacji:
- Prepared statements (ochrona SQL Injection)
- Input sanitization (`sanitize()`)
- Email validation
- HTTPS ready
- No direct file execution
- Error logging

### Do Dodania:
- Rate limiting
- CAPTCHA
- 2FA dla admina
- Token CSRF
- Password hashing (bcrypt)

---

## 📱 Responsywność

Breakpoints:
- **Mobile:** 480px
- **Tablet:** 768px
- **Laptop:** 1024px
- **Desktop:** 1400px+

Mobile-first approach.

---

## 🚀 Deploy Checklist

```bash
# 1. Zmień dane w config.php
nano config.php

# 2. Zaimportuj bazę
mysql -u root -p < SETUP.sql

# 3. Zmień uprawnienia
chmod 755 assets/
chmod 644 assets/*.css assets/*.js
chmod 644 *.php

# 4. Ustaw .htaccess
a2enmod rewrite
systemctl restart apache2

# 5. Włącz HTTPS
# (certbot dla Let's Encrypt)
certbot certonly --apache -d sersoltec.eu

# 6. Redirect na HTTPS (w .htaccess)
# (odkomentuj sekcję)

# 7. Backup
mysqldump -u root -p sersoltec_db > backup.sql

# 8. Monitoring
tail -f /var/log/apache2/error.log
```

---

## 📞 Support

**Główny contact:** info@sersoltec.eu  
**Telefon:** +34 666 666 666  
**Adres:** Valencia, Spain

---

## 📊 Statystyki Projektu

| Metryka | Wartość |
|---------|---------|
| Pliki PHP | 8 |
| Pliki CSS | 2 |
| Pliki JS | 1 |
| Linie kodu | ~3000 |
| Liczba funkcji | 20+ |
| Liczba klasy CSS | 50+ |
| Szablony (sections) | 15+ |
| Obsługiwane języki | 3 |
| Kategorie produktów | 9 |
| Przykładowe produkty | 12 |

---

## 🎓 Nauka i Rozwój

Użyte koncepty:
- MVC pattern (separation of concerns)
- DRY principle (reusable components)
- Mobile-first CSS
- Semantic HTML5
- PDO prepared statements
- Session management
- JSON data structures
- Form validation
- Email integration
- Responsive web design

---

## 📄 Licencja

Proprietary - Sersoltec S.L.

---

## 👨‍💻 Autор

Stworzony: 2025-11-19  
Wersja: 1.0.0  
Ostatnia aktualizacja: 2025-11-19

---

## 🔄 Wersjonowanie

### v1.0.0 (Bieżąca)
- [x] Strona główna
- [x] Katalog produktów
- [x] Kalkulator
- [x] Formularz kontaktowy
- [x] Multi-language
- [x] Responsywny design

### v1.1.0 (Planowana)
- [ ] Panel admina
- [ ] User accounts
- [ ] Opinie klientów
- [ ] Blog

### v2.0.0 (Planowana)
- [ ] Pełny e-commerce
- [ ] Płatności online
- [ ] API REST
- [ ] Mobile app

---

**Projekt gotowy do wdrażania! 🚀**
