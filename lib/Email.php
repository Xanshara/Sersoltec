<?php
/**
 * SERSOLTEC - Email Class
 * Email sending with templates and logging
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

namespace Sersoltec\Lib;

class Email {
    
    /**
     * From email address
     * @var string
     */
    private string $fromEmail;
    
    /**
     * From name
     * @var string
     */
    private string $fromName;
    
    /**
     * Reply-to email
     * @var string|null
     */
    private ?string $replyTo = null;
    
    /**
     * Logger instance
     * @var Logger|null
     */
    private ?Logger $logger = null;
    
    /**
     * Email templates directory
     * @var string
     */
    private string $templatesDir = '../email-templates';
    
    /**
     * Test mode (don't actually send emails)
     * @var bool
     */
    private bool $testMode = false;
    
    /**
     * Constructor
     * 
     * @param string $fromEmail From email address
     * @param string $fromName From name
     * @param Logger|null $logger Logger instance
     */
    public function __construct(string $fromEmail, string $fromName, ?Logger $logger = null) {
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->logger = $logger;
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $options Additional options (cc, bcc, attachments, etc.)
     * @return bool
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool {
        if ($this->testMode) {
            $this->log("TEST MODE: Email to $to with subject: $subject", true);
            return true;
        }
        
        // Validate email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->log("Invalid email address: $to", false);
            return false;
        }
        
        // Build headers
        $headers = $this->buildHeaders($options);
        
        // Send email
        $success = mail($to, $subject, $body, $headers);
        
        // Log result
        $this->log("Email to $to: $subject", $success);
        
        return $success;
    }
    
    /**
     * Send email using template
     * 
     * @param string $to Recipient email
     * @param string $template Template name
     * @param array $data Template data
     * @param array $options Additional options
     * @return bool
     */
    public function sendTemplate(string $to, string $template, array $data = [], array $options = []): bool {
        $templateFile = $this->templatesDir . '/' . $template . '.php';
        
        if (!file_exists($templateFile)) {
            $this->log("Template not found: $template", false);
            return false;
        }
        
        // Extract data for template
        extract($data);
        
        // Capture template output
        ob_start();
        include $templateFile;
        $body = ob_get_clean();
        
        // Extract subject from template if not provided
        $subject = $options['subject'] ?? $data['subject'] ?? 'Email from Sersoltec';
        
        return $this->send($to, $subject, $body, $options);
    }
    
    /**
     * Build email headers
     * 
     * @param array $options Email options
     * @return string
     */
    private function buildHeaders(array $options = []): string {
        $headers = [];
        
        // From
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        
        // Reply-To
        $replyTo = $options['reply_to'] ?? $this->replyTo;
        if ($replyTo) {
            $headers[] = "Reply-To: $replyTo";
        }
        
        // CC
        if (isset($options['cc'])) {
            $headers[] = "Cc: " . (is_array($options['cc']) ? implode(', ', $options['cc']) : $options['cc']);
        }
        
        // BCC
        if (isset($options['bcc'])) {
            $headers[] = "Bcc: " . (is_array($options['bcc']) ? implode(', ', $options['bcc']) : $options['bcc']);
        }
        
        // MIME
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        
        // Additional headers
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Send welcome email
     * 
     * @param string $to Recipient email
     * @param string $name User name
     * @param string $verificationLink Verification link
     * @return bool
     */
    public function sendWelcome(string $to, string $name, string $verificationLink): bool {
        return $this->sendTemplate($to, 'welcome', [
            'subject' => 'Witaj w Sersoltec!',
            'name' => $name,
            'verification_link' => $verificationLink
        ]);
    }
    
    /**
     * Send password reset email
     * 
     * @param string $to Recipient email
     * @param string $name User name
     * @param string $resetLink Reset link
     * @return bool
     */
    public function sendPasswordReset(string $to, string $name, string $resetLink): bool {
        return $this->sendTemplate($to, 'password-reset', [
            'subject' => 'Reset hasła - Sersoltec',
            'name' => $name,
            'reset_link' => $resetLink
        ]);
    }
    
    /**
     * Send order confirmation email
     * 
     * @param string $to Recipient email
     * @param array $orderData Order data
     * @return bool
     */
    public function sendOrderConfirmation(string $to, array $orderData): bool {
        return $this->sendTemplate($to, 'order-confirmation', array_merge([
            'subject' => 'Potwierdzenie zamówienia - Sersoltec'
        ], $orderData));
    }
    
    /**
     * Send contact form notification
     * 
     * @param string $to Admin email
     * @param array $formData Form data
     * @return bool
     */
    public function sendContactNotification(string $to, array $formData): bool {
        return $this->sendTemplate($to, 'contact-notification', array_merge([
            'subject' => 'Nowe zapytanie kontaktowe - Sersoltec'
        ], $formData));
    }
    
    /**
     * Create basic HTML email wrapper
     * 
     * @param string $content Email content
     * @param string $title Email title
     * @return string
     */
    public function wrapHtml(string $content, string $title = ''): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #1a4d2e 0%, #0f3d25 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #1a4d2e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .button:hover {
            background: #0f3d25;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SERSOLTEC</h1>
        </div>
        <div class="content">
            {$content}
        </div>
        <div class="footer">
            <p>&copy; 2024 Sersoltec. Wszystkie prawa zastrzeżone.</p>
            <p>
                <a href="https://sersoltec.eu">sersoltec.eu</a> | 
                <a href="mailto:contact@sersoltec.eu">contact@sersoltec.eu</a>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Set reply-to address
     * 
     * @param string $email Reply-to email
     * @return self
     */
    public function setReplyTo(string $email): self {
        $this->replyTo = $email;
        return $this;
    }
    
    /**
     * Set templates directory
     * 
     * @param string $dir Templates directory path
     * @return self
     */
    public function setTemplatesDir(string $dir): self {
        $this->templatesDir = rtrim($dir, '/');
        return $this;
    }
    
    /**
     * Enable test mode
     * 
     * @param bool $enabled Test mode flag
     * @return self
     */
    public function setTestMode(bool $enabled = true): self {
        $this->testMode = $enabled;
        return $this;
    }
    
    /**
     * Log email activity
     * 
     * @param string $message Log message
     * @param bool $success Success flag
     * @return void
     */
    private function log(string $message, bool $success): void {
        if ($this->logger) {
            if ($success) {
                $this->logger->info("EMAIL: $message");
            } else {
                $this->logger->error("EMAIL FAILED: $message");
            }
        }
    }
    
    /**
     * Validate email address
     * 
     * @param string $email Email address
     * @return bool
     */
    public static function isValid(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize email address
     * 
     * @param string $email Email address
     * @return string
     */
    public static function sanitize(string $email): string {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}
