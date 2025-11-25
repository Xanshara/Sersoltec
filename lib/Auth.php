<?php
/**
 * SERSOLTEC - Auth Class
 * User authentication and session management
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

namespace Sersoltec\Lib;

class Auth {
    
    /**
     * Session key for user ID
     */
    private const SESSION_USER_ID = 'user_id';
    
    /**
     * Session key for user role
     */
    private const SESSION_USER_ROLE = 'user_role';
    
    /**
     * Session key for last activity
     */
    private const SESSION_LAST_ACTIVITY = 'last_activity';
    
    /**
     * Session timeout (30 minutes)
     */
    private const SESSION_TIMEOUT = 1800;
    
    /**
     * Database instance
     * @var Database
     */
    private Database $db;
    
    /**
     * Current user data
     * @var array|null
     */
    private ?array $user = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
        $this->checkTimeout();
    }
    
    /**
     * Start session if not already started
     * 
     * @return void
     */
    private function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start(); // @ suppresses warning if headers already sent
        }
    }
    
    /**
     * Check session timeout
     * 
     * @return void
     */
    private function checkTimeout(): void {
        if (isset($_SESSION[self::SESSION_LAST_ACTIVITY])) {
            $elapsed = time() - $_SESSION[self::SESSION_LAST_ACTIVITY];
            
            if ($elapsed > self::SESSION_TIMEOUT) {
                $this->logout();
                return;
            }
        }
        
        $_SESSION[self::SESSION_LAST_ACTIVITY] = time();
    }
    
    /**
     * Attempt to login user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return bool
     */
    public function login(string $email, string $password): bool {
        try {
            $user = $this->db->fetchOne(
                'SELECT * FROM users WHERE email = ? AND active = 1',
                [$email]
            );
            
            if (!$user || !password_verify($password, $user['password'])) {
                $this->logFailedAttempt($email);
                return false;
            }
            
            // Check if account is locked
            if ($this->isAccountLocked($email)) {
                return false;
            }
            
            // Set session
            $_SESSION[self::SESSION_USER_ID] = $user['id'];
            $_SESSION[self::SESSION_USER_ROLE] = $user['role'] ?? 'user';
            $_SESSION[self::SESSION_LAST_ACTIVITY] = time();
            
            // Update last login
            $this->db->update(
                'users',
                ['last_login' => date('Y-m-d H:i:s')],
                'id = ?',
                [$user['id']]
            );
            
            // Clear failed attempts
            $this->clearFailedAttempts($email);
            
            $this->user = $user;
            
            return true;
            
        } catch (\PDOException $e) {
            error_log('Login failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout current user
     * 
     * @return void
     */
    public function logout(): void {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        $this->user = null;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public function check(): bool {
        return isset($_SESSION[self::SESSION_USER_ID]);
    }
    
    /**
     * Get current user
     * 
     * @return array|null
     */
    public function user(): ?array {
        if ($this->user !== null) {
            return $this->user;
        }
        
        if (!$this->check()) {
            return null;
        }
        
        try {
            $this->user = $this->db->fetchOne(
                'SELECT * FROM users WHERE id = ?',
                [$_SESSION[self::SESSION_USER_ID]]
            );
            
            return $this->user ?: null;
            
        } catch (\PDOException $e) {
            error_log('Failed to fetch user: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user ID
     * 
     * @return int|null
     */
    public function id(): ?int {
        return $_SESSION[self::SESSION_USER_ID] ?? null;
    }
    
    /**
     * Check if user has role
     * 
     * @param string $role Role name
     * @return bool
     */
    public function hasRole(string $role): bool {
        return ($_SESSION[self::SESSION_USER_ROLE] ?? null) === $role;
    }
    
    /**
     * Check if user is admin
     * 
     * @return bool
     */
    public function isAdmin(): bool {
        return in_array($_SESSION[self::SESSION_USER_ROLE] ?? null, ['admin', 'superadmin']);
    }
    
    /**
     * Register new user
     * 
     * @param array $data User data (email, password, name, etc.)
     * @return int|false User ID or false on failure
     */
    public function register(array $data) {
        try {
            // Check if email already exists
            if ($this->db->exists('users', 'email = ?', [$data['email']])) {
                return false;
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Set defaults
            $data['active'] = $data['active'] ?? 0; // Inactive until email verification
            $data['role'] = $data['role'] ?? 'user';
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Generate verification token
            $data['verification_token'] = bin2hex(random_bytes(32));
            
            return (int) $this->db->insert('users', $data);
            
        } catch (\PDOException $e) {
            error_log('Registration failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify user email with token
     * 
     * @param string $token Verification token
     * @return bool
     */
    public function verifyEmail(string $token): bool {
        try {
            $user = $this->db->fetchOne(
                'SELECT id FROM users WHERE verification_token = ? AND active = 0',
                [$token]
            );
            
            if (!$user) {
                return false;
            }
            
            $this->db->update(
                'users',
                [
                    'active' => 1,
                    'verification_token' => null,
                    'email_verified_at' => date('Y-m-d H:i:s')
                ],
                'id = ?',
                [$user['id']]
            );
            
            return true;
            
        } catch (\PDOException $e) {
            error_log('Email verification failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create password reset token
     * 
     * @param string $email User email
     * @return string|false Token or false on failure
     */
    public function createPasswordResetToken(string $email) {
        try {
            $user = $this->db->fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
            
            if (!$user) {
                return false;
            }
            
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            // Delete old tokens
            $this->db->delete('password_resets', 'email = ?', [$email]);
            
            // Insert new token
            $this->db->insert('password_resets', [
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
            
            return $token;
            
        } catch (\PDOException $e) {
            error_log('Password reset token creation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset password with token
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return bool
     */
    public function resetPassword(string $token, string $newPassword): bool {
        try {
            $reset = $this->db->fetchOne(
                'SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()',
                [$token]
            );
            
            if (!$reset) {
                return false;
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update('users', ['password' => $hashedPassword], 'email = ?', [$reset['email']]);
            
            // Mark token as used
            $this->db->update('password_resets', ['used' => 1], 'token = ?', [$token]);
            
            return true;
            
        } catch (\PDOException $e) {
            error_log('Password reset failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log failed login attempt
     * 
     * @param string $email User email
     * @return void
     */
    private function logFailedAttempt(string $email): void {
        try {
            $this->db->insert('login_attempts', [
                'email' => $email,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'attempted_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\PDOException $e) {
            error_log('Failed to log login attempt: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if account is locked (too many failed attempts)
     * 
     * @param string $email User email
     * @return bool
     */
    private function isAccountLocked(string $email): bool {
        try {
            $count = $this->db->count(
                'login_attempts',
                'email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
                [$email]
            );
            
            return $count >= 5; // Lock after 5 failed attempts in 15 minutes
            
        } catch (\PDOException $e) {
            error_log('Failed to check account lock: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear failed login attempts
     * 
     * @param string $email User email
     * @return void
     */
    private function clearFailedAttempts(string $email): void {
        try {
            $this->db->delete('login_attempts', 'email = ?', [$email]);
        } catch (\PDOException $e) {
            error_log('Failed to clear login attempts: ' . $e->getMessage());
        }
    }
}
