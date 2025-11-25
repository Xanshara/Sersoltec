<?php
/**
 * SERSOLTEC v2.3c - Email Helper
 * Helper functions for sending password reset and welcome emails
 * ZMODYFIKOWANO: Dodano wsparcie dla wysyłki SMTP (PHPMailer)
 */

// Zapewnij, że ścieżka do vendor/autoload.php jest poprawna
// Jeśli EmailHelper.php jest w folderze 'includes/', ścieżka powinna być poprawiona na '../vendor/autoload.php'
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/email-translations.php';
// Upewnij się, że ten plik jest poprawnie ładowany
require_once __DIR__ . '/config.php';

class EmailHelper {
    
    private $siteUrl;
    private $siteName;
    private $siteEmail;
    private $lang;
    
    public function __construct($lang = 'pl') {
        $this->lang = $lang;
        $this->siteUrl = defined('SITE_URL') ? SITE_URL : 'http://lastchance.pl/sersoltec';
        $this->siteName = defined('SITE_NAME') ? SITE_NAME : 'SERSOLTEC';
        // Zmieniono, aby używać SMTP_USER jako domyślnego nadawcy, jeśli zdefiniowano
        $this->siteEmail = defined('SMTP_USER') ? SMTP_USER : (defined('SITE_EMAIL') ? SITE_EMAIL : 'noreply@sersoltec.eu');
    }
    
    /**
     * Send welcome email with verification link
     * * @param string $to Recipient email
     * @param string $name User name
     * @param string $verificationLink Verification link
     * @return bool
     */
    public function sendWelcome($to, $name, $verificationLink) {
        $data = [
            'site_name' => $this->siteName,
            'site_url' => $this->siteUrl,
            'site_email' => $this->siteEmail,
            'user_name' => $name,
            'user_email' => $to,
            'verification_link' => $verificationLink,
            'lang' => $this->lang,
        ];
        
        $subject = et('welcome', 'welcome_title', $this->lang);
        $html = loadEmailTemplate('welcome-email.html', $data, $this->lang);
        
        return $this->sendEmail($to, $subject, $html);
    }
    
    /**
     * Send password reset email
     * * @param string $to Recipient email
     * @param string $name User name
     * @param string $resetLink Reset link
     * @return bool
     */
    public function sendPasswordReset($to, $name, $resetLink) {
        $data = [
            'site_name' => $this->siteName,
            'site_url' => $this->siteUrl,
            'site_email' => $this->siteEmail,
            'user_name' => $name,
            'reset_link' => $resetLink,
            'lang' => $this->lang,
        ];
        
        $subject = et('password_reset', 'reset_title', $this->lang);
        $html = loadEmailTemplate('password-reset-email.html', $data, $this->lang);
        
        return $this->sendEmail($to, $subject, $html);
    }
    
    /**
     * Send confirmation email after password has been successfully changed
     * * @param string $to Recipient email
     * @param string $name User name
     * @return bool
     */
    public function sendPasswordChanged($to, $name) {
        $data = [
            'site_name' => $this->siteName,
            'site_url' => $this->siteUrl,
            'site_email' => $this->siteEmail,
            'user_name' => $name,
            'lang' => $this->lang,
        ];
        
        $subject = et('password_changed', 'changed_title', $this->lang);
        $html = loadEmailTemplate('password-changed-email.html', $data, $this->lang);
        
        return $this->sendEmail($to, $subject, $html);
    }
    
    // =========================================================================
    // NOWA METODA WYSYŁKI: Zgodna z konfiguracją SMTP lub fallbackiem do mail()
    // =========================================================================
    
    /**
     * Send email using SMTP (PHPMailer) or PHP mail() function (fallback)
     * * @param string $to Recipient
     * @param string $subject Subject
     * @param string $html HTML body
     * @return bool
     */
    private function sendEmail($to, $subject, $html) {
        
        // Sprawdzenie, czy USE_SMTP jest zdefiniowane i ustawione na true w config.php
        if (defined('USE_SMTP') && USE_SMTP === true) {
            
            $mail = new PHPMailer(true);
            
            try {
                // Ustawienia SMTP (PHPMailer)
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                
                // Konfiguracja Szyfrowania
                if (defined('SMTP_SECURE') && SMTP_SECURE === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
                } elseif (defined('SMTP_SECURE') && SMTP_SECURE === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 465;
                } else {
                    $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 25;
                }
                
                // Ustawienia wiadomości
                $mail->CharSet = 'UTF-8';
                $mail->setFrom(SMTP_USER, $this->siteName);
                $mail->addAddress($to);
                $mail->Subject = $subject;
                $mail->Body    = $html;
                $mail->isHTML(true);
                
                $mail->send();
                error_log("Email sent via SMTP to: $to - Subject: $subject");
                return true;
                
            } catch (Exception $e) {
                // W przypadku błędu SMTP zapisz go w logu
                error_log("Email failed via SMTP to: $to - Subject: $subject. Error: {$mail->ErrorInfo}");
                // Spróbuj użyć domyślnego PHP mail() jako awaryjne rozwiązanie
                return $this->sendWithPhpMailFallback($to, $subject, $html); 
            }

        } else {
            // Domyślna wysyłka PHP mail() jeśli SMTP nie jest włączone
            return $this->sendWithPhpMailFallback($to, $subject, $html);
        }
    }
    
    /**
     * Używa funkcji mail() jako awaryjnej (fallback) lub domyślnej
     * * @param string $to Recipient
     * @param string $subject Subject
     * @param string $html HTML body
     * @return bool
     */
    private function sendWithPhpMailFallback($to, $subject, $html) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            // Użycie siteEmail jako From (dla zgodności z oryginalnym kodem)
            'From: ' . $this->siteName . ' <' . $this->siteEmail . '>', 
            'Reply-To: ' . $this->siteEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $result = mail($to, $subject, $html, implode("\r\n", $headers));
        
        if ($result) {
            error_log("Email sent (fallback) to: $to - Subject: $subject");
        } else {
            error_log("Email failed (fallback) to: $to - Subject: $subject");
        }
        
        return $result;
    }
    
    // =========================================================================
    // POZOSTAŁE METODY KLASY
    // =========================================================================
    
    /**
     * Send email using PHP mail() function
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $html HTML body
     * @return bool
     */
    private function sendWithPhpMail($to, $subject, $html) {
        // Ta funkcja jest teraz nieużywana/zastąpiona przez sendEmail
        // Zostawiona dla kompatybilności, ale nie będzie wywoływana,
        // jeśli zmienisz jej wywołania w sendWelcome/sendPasswordReset.
        return $this->sendWithPhpMailFallback($to, $subject, $html);
    }
    
    /**
     * Set language
     * * @param string $lang Language code (pl, en, es)
     * @return self
     */
    public function setLanguage($lang) {
        $this->lang = $lang;
        return $this;
    }
}

/**
 * Get EmailHelper instance
 * * @param string $lang Language
 * @return EmailHelper
 */
function emailHelper($lang = null) {
    global $emailHelperInstance;
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    if (!isset($emailHelperInstance[$lang])) {
        $emailHelperInstance[$lang] = new EmailHelper($lang);
    }
    return $emailHelperInstance[$lang];
}