<?php
// Ten plik załadowuje wszystkie potrzebne klasy z PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// KLUCZOWY ELEMENT: Ładowanie biblioteki
// Ścieżka do tego pliku jest prawidłowa, jeśli skrypt jest w tym samym folderze co folder vendor/
require 'vendor/autoload.php'; 

// -------- 1. DANE KONFIGURACYJNE OVH I WIADOMOŚCI --------

// DANE LOGOWANIA DO SKRZYNKI NOREPLY@SERSOLTEC.EU
$ovh_user = 'noreply@sersoltec.eu';            // <-- **Twój pełny adres e-mail OVH**
$ovh_password = 'Grunwaldzka50?';    // <-- **Hasło do skrzynki noreply@sersoltec.eu**

// DANE ODBIORCY I TREŚĆ WIADOMOŚCI
$odbiorca_email = 'email_uzytkownika_do_resetu@domena.pl'; // <-- **Adres e-mail użytkownika, który prosi o reset**
$odbiorca_imie = 'Jan Kowalski'; // Imię odbiorcy (opcjonalne)
$link_do_resetu = 'https://twojadomena.pl/reset.php?token=UNIKALNY_TOKEN'; // <-- **Twój dynamicznie generowany link z tokenem**

// -----------------------------------------------------------------

$mail = new PHPMailer(true);

try {
    // --- 2. Ustawienia Serwera SMTP (OVH) ---
    $mail->isSMTP();
    $mail->Host       = 'ssl0.ovh.net'; // Standardowy serwer SMTP OVH
    $mail->SMTPAuth   = true;
    $mail->Username   = $ovh_user;
    $mail->Password   = $ovh_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Wymagane szyfrowanie SSL
    $mail->Port       = 465; // Port dla SSL

    // --- 3. Konfiguracja Nadawcy i Odbiorcy ---
    $mail->setFrom($ovh_user, 'Sersoltec - Automatyczny System'); // Nadawca musi być zgodny z Username
    $mail->addAddress($odbiorca_email, $odbiorca_imie); // Dodaj odbiorcę

    // --- 4. Budowa Treści Wiadomości ---
    $mail->isHTML(true); // Ustawienie formatu HTML
    $mail->Subject = 'Resetowanie Hasła - Sersoltec';
    $mail->Body    = "Witaj $odbiorca_imie,<br><br>
                      Otrzymaliśmy prośbę o zresetowanie hasła. Kliknij w poniższy link, aby ustawić nowe hasło:<br><br>
                      <a href=\"$link_do_resetu\">$link_do_resetu</a><br><br>
                      Jeśli to nie Ty prosiłeś o reset, zignoruj tę wiadomość.<br><br>
                      Pozdrawiamy,<br>Zespół Sersoltec.";
    $mail->AltBody = "Link do resetowania hasła: $link_do_resetu. Jeśli to nie Ty prosiłeś o reset, zignoruj tę wiadomość."; // Wersja tekstowa

    // --- 5. Wysyłka ---
    $mail->send();
    echo 'Wiadomość została pomyślnie wysłana przez serwer OVH.';

} catch (Exception $e) {
    // W przypadku błędu
    echo "Wiadomość nie mogła zostać wysłana. Błąd Mailera: {$mail->ErrorInfo}";
}

?>