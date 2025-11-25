<?php
/**
 * SERSOLTEC - Security Class
 * CSRF protection, XSS prevention, and security utilities
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

namespace Sersoltec\Lib;

class Security {
    
    /**
     * CSRF token session key
     */
    private const CSRF_TOKEN_KEY = 'csrf_token';
    
    /**
     * CSRF token name
     */
    private const CSRF_TOKEN_NAME = '_token';
    
    /**
     * Logger instance
     * @var Logger|null
     */
    private ?Logger $logger = null;
    
    /**
     * Constructor
     * 
     * @param Logger|null $logger Logger instance
     */
    public function __construct(?Logger $logger = null) {
        $this->logger = $logger;
        $this->ensureSession();
    }
    
    /**
     * Ensure session is started
     * 
     * @return void
     */
    private function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start(); // @ suppresses warning if headers already sent
        }
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public function generateCsrfToken(): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::CSRF_TOKEN_KEY] = $token;
        return $token;
    }
    
    /**
     * Get current CSRF token (generate if doesn't exist)
     * 
     * @return string
     */
    public function getCsrfToken(): string {
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            return $this->generateCsrfToken();
        }
        return $_SESSION[self::CSRF_TOKEN_KEY];
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string|null $token Token to verify
     * @return bool
     */
    public function verifyCsrfToken(?string $token = null): bool {
        if ($token === null) {
            $token = $_POST[self::CSRF_TOKEN_NAME] ?? $_GET[self::CSRF_TOKEN_NAME] ?? null;
        }
        
        if ($token === null || !isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            $this->logSecurityEvent('CSRF token missing');
            return false;
        }
        
        $valid = hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
        
        if (!$valid) {
            $this->logSecurityEvent('CSRF token mismatch', [
                'expected' => substr($_SESSION[self::CSRF_TOKEN_KEY], 0, 10) . '...',
                'received' => substr($token, 0, 10) . '...'
            ]);
        }
        
        return $valid;
    }
    
    /**
     * Get CSRF token as hidden input field
     * 
     * @return string
     */
    public function csrfField(): string {
        $token = $this->getCsrfToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::CSRF_TOKEN_NAME,
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Get CSRF token as meta tag
     * 
     * @return string
     */
    public function csrfMeta(): string {
        $token = $this->getCsrfToken();
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Sanitize input (prevent XSS)
     * 
     * @param string $input Input string
     * @return string
     */
    public function sanitize(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize array recursively
     * 
     * @param array $data Input array
     * @return array
     */
    public function sanitizeArray(array $data): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $key = $this->sanitize($key);
            
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $this->sanitize($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request method is POST
     * 
     * @return bool
     */
    public function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Check if request method is GET
     * 
     * @return bool
     */
    public function isGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    public function getIp(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Check for proxy headers
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }
    
    /**
     * Get user agent
     * 
     * @return string
     */
    public function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
    
    /**
     * Check if IP is blacklisted
     * 
     * @param string $ip IP address
     * @return bool
     */
    public function isBlacklisted(string $ip): bool {
        // Could check against database or file
        // For now, return false
        return false;
    }
    
    /**
     * Rate limit check (simple implementation)
     * 
     * @param string $key Unique key (e.g., IP + action)
     * @param int $maxAttempts Max attempts allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if rate limit exceeded
     */
    public function isRateLimited(string $key, int $maxAttempts = 5, int $timeWindow = 60): bool {
        $sessionKey = 'rate_limit_' . md5($key);
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [
                'count' => 1,
                'start' => time()
            ];
            return false;
        }
        
        $data = $_SESSION[$sessionKey];
        $elapsed = time() - $data['start'];
        
        // Reset if time window passed
        if ($elapsed > $timeWindow) {
            $_SESSION[$sessionKey] = [
                'count' => 1,
                'start' => time()
            ];
            return false;
        }
        
        // Increment counter
        $_SESSION[$sessionKey]['count']++;
        
        // Check if limit exceeded
        if ($data['count'] >= $maxAttempts) {
            $this->logSecurityEvent('Rate limit exceeded', [
                'key' => $key,
                'attempts' => $data['count']
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Hash password
     * 
     * @param string $password Plain password
     * @return string
     */
    public function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     * 
     * @param string $password Plain password
     * @param string $hash Password hash
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate random token
     * 
     * @param int $length Token length (bytes)
     * @return string
     */
    public function generateToken(int $length = 32): string {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Encrypt string
     * 
     * @param string $data Data to encrypt
     * @param string $key Encryption key
     * @return string
     */
    public function encrypt(string $data, string $key): string {
        $cipher = 'aes-256-gcm';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Decrypt string
     * 
     * @param string $encrypted Encrypted data
     * @param string $key Encryption key
     * @return string|false
     */
    public function decrypt(string $encrypted, string $key) {
        $cipher = 'aes-256-gcm';
        $ivLength = openssl_cipher_iv_length($cipher);
        $tagLength = 16;
        
        $decoded = base64_decode($encrypted);
        $iv = substr($decoded, 0, $ivLength);
        $tag = substr($decoded, $ivLength, $tagLength);
        $ciphertext = substr($decoded, $ivLength + $tagLength);
        
        return openssl_decrypt(
            $ciphertext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILES array element
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Max file size in bytes
     * @return array ['valid' => bool, 'error' => string]
     */
    public function validateUpload(array $file, array $allowedTypes = [], int $maxSize = 5242880): array {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File too large'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }
        
        // Check for malicious content
        $content = file_get_contents($file['tmp_name']);
        if (preg_match('/<\?php|<script|javascript:/i', $content)) {
            $this->logSecurityEvent('Malicious file upload attempt', [
                'filename' => $file['name'],
                'type' => $mimeType
            ]);
            return ['valid' => false, 'error' => 'Suspicious file content'];
        }
        
        return ['valid' => true, 'error' => ''];
    }
    
    /**
     * Log security event
     * 
     * @param string $message Event message
     * @param array $context Additional context
     * @return void
     */
    private function logSecurityEvent(string $message, array $context = []): void {
        $context = array_merge($context, [
            'ip' => $this->getIp(),
            'user_agent' => $this->getUserAgent(),
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        
        if ($this->logger) {
            $this->logger->security($message, $context);
        } else {
            error_log("SECURITY: $message | " . json_encode($context));
        }
    }
    
    /**
     * Clean filename for safe storage
     * 
     * @param string $filename Original filename
     * @return string
     */
    public function cleanFilename(string $filename): string {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250);
            $filename = $name . '.' . $ext;
        }
        
        return $filename;
    }
}
