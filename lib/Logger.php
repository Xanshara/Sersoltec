<?php
/**
 * SERSOLTEC - Logger Class
 * System logging for errors, security events, and debugging
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

namespace Sersoltec\Lib;

class Logger {
    
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    const LEVEL_SECURITY = 'SECURITY';
    
    /**
     * Log directory
     * @var string
     */
    private string $logDir;
    
    /**
     * Log file paths
     * @var array
     */
    private array $logFiles = [
        'error' => 'error.log',
        'security' => 'security.log',
        'admin' => 'admin.log',
        'email' => 'email.log',
        'debug' => 'debug.log'
    ];
    
    /**
     * Enable/disable logging
     * @var bool
     */
    private bool $enabled = true;
    
    /**
     * Minimum log level
     * @var string
     */
    private string $minLevel = self::LEVEL_INFO;
    
    /**
     * Constructor
     * 
     * @param string $logDir Log directory path
     */
    public function __construct(string $logDir = '../logs') {
        $this->logDir = rtrim($logDir, '/');
        $this->ensureLogDirectory();
    }
    
    /**
     * Ensure log directory exists
     * 
     * @return void
     */
    private function ensureLogDirectory(): void {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Create .htaccess to protect logs
        $htaccess = $this->logDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }
    
    /**
     * Log debug message
     * 
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function debug(string $message, array $context = []): void {
        $this->log(self::LEVEL_DEBUG, $message, $context, 'debug');
    }
    
    /**
     * Log info message
     * 
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function info(string $message, array $context = []): void {
        $this->log(self::LEVEL_INFO, $message, $context, 'debug');
    }
    
    /**
     * Log warning message
     * 
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function warning(string $message, array $context = []): void {
        $this->log(self::LEVEL_WARNING, $message, $context, 'error');
    }
    
    /**
     * Log error message
     * 
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function error(string $message, array $context = []): void {
        $this->log(self::LEVEL_ERROR, $message, $context, 'error');
    }
    
    /**
     * Log critical error
     * 
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function critical(string $message, array $context = []): void {
        $this->log(self::LEVEL_CRITICAL, $message, $context, 'error');
        
        // Send email notification for critical errors
        $this->notifyCritical($message, $context);
    }
    
    /**
     * Log security event
     * 
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    public function security(string $message, array $context = []): void {
        $this->log(self::LEVEL_SECURITY, $message, $context, 'security');
    }
    
    /**
     * Log admin action
     * 
     * @param string $action Action performed
     * @param int $adminId Admin user ID
     * @param array $context Additional context
     * @return void
     */
    public function admin(string $action, int $adminId, array $context = []): void {
        $context['admin_id'] = $adminId;
        $this->log(self::LEVEL_INFO, "ADMIN: $action", $context, 'admin');
    }
    
    /**
     * Log email sent
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param bool $success Whether email was sent successfully
     * @return void
     */
    public function email(string $to, string $subject, bool $success = true): void {
        $status = $success ? 'SUCCESS' : 'FAILED';
        $message = "EMAIL $status: To=$to, Subject=$subject";
        $this->log(self::LEVEL_INFO, $message, [], 'email');
    }
    
    /**
     * Main log method
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @param string $logType Log file type
     * @return void
     */
    private function log(string $level, string $message, array $context, string $logType): void {
        if (!$this->enabled || !$this->shouldLog($level)) {
            return;
        }
        
        $logFile = $this->logDir . '/' . $this->logFiles[$logType];
        
        // Format log entry
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $user = $_SESSION['admin_username'] ?? $_SESSION['user_id'] ?? 'guest';
        
        $logEntry = sprintf(
            "[%s] [%s] [IP:%s] [User:%s] %s",
            $timestamp,
            $level,
            $ip,
            $user,
            $message
        );
        
        // Add context if provided
        if (!empty($context)) {
            $logEntry .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= PHP_EOL;
        
        // Write to file
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Rotate logs if needed
        $this->rotateLogs($logFile);
    }
    
    /**
     * Check if log level should be logged
     * 
     * @param string $level Log level
     * @return bool
     */
    private function shouldLog(string $level): bool {
        $levels = [
            self::LEVEL_DEBUG => 0,
            self::LEVEL_INFO => 1,
            self::LEVEL_WARNING => 2,
            self::LEVEL_ERROR => 3,
            self::LEVEL_CRITICAL => 4,
            self::LEVEL_SECURITY => 5
        ];
        
        return ($levels[$level] ?? 0) >= ($levels[$this->minLevel] ?? 0);
    }
    
    /**
     * Rotate log files if they exceed size limit
     * 
     * @param string $logFile Log file path
     * @param int $maxSize Max file size in bytes (default 5MB)
     * @return void
     */
    private function rotateLogs(string $logFile, int $maxSize = 5242880): void {
        if (!file_exists($logFile) || filesize($logFile) < $maxSize) {
            return;
        }
        
        // Keep last 5 rotated logs
        for ($i = 4; $i >= 0; $i--) {
            $old = $logFile . '.' . $i;
            $new = $logFile . '.' . ($i + 1);
            
            if (file_exists($old)) {
                if ($i === 4) {
                    unlink($old); // Delete oldest
                } else {
                    rename($old, $new);
                }
            }
        }
        
        // Rotate current log
        rename($logFile, $logFile . '.0');
    }
    
    /**
     * Send email notification for critical errors
     * 
     * @param string $message Error message
     * @param array $context Error context
     * @return void
     */
    private function notifyCritical(string $message, array $context): void {
        // This would integrate with Email class
        // For now, just use error_log to send to PHP error log
        error_log("CRITICAL ERROR: $message | Context: " . json_encode($context));
    }
    
    /**
     * Set minimum log level
     * 
     * @param string $level Minimum level
     * @return void
     */
    public function setMinLevel(string $level): void {
        $this->minLevel = $level;
    }
    
    /**
     * Enable/disable logging
     * 
     * @param bool $enabled Enable flag
     * @return void
     */
    public function setEnabled(bool $enabled): void {
        $this->enabled = $enabled;
    }
    
    /**
     * Get log contents
     * 
     * @param string $logType Log type (error, security, admin, email, debug)
     * @param int $lines Number of lines to read from end
     * @return array
     */
    public function read(string $logType = 'error', int $lines = 100): array {
        $logFile = $this->logDir . '/' . $this->logFiles[$logType];
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        return $this->tail($logFile, $lines);
    }
    
    /**
     * Read last N lines from file
     * 
     * @param string $file File path
     * @param int $lines Number of lines
     * @return array
     */
    private function tail(string $file, int $lines): array {
        $handle = @fopen($file, 'r');
        if (!$handle) {
            return [];
        }
        
        $buffer = [];
        $position = -1;
        
        fseek($handle, $position, SEEK_END);
        
        while (count($buffer) < $lines && fseek($handle, $position, SEEK_END) !== -1) {
            $char = fgetc($handle);
            
            if ($char === "\n") {
                $buffer[] = strrev($line ?? '');
                unset($line);
            } else {
                $line = ($line ?? '') . $char;
            }
            
            $position--;
        }
        
        fclose($handle);
        
        return array_reverse($buffer);
    }
    
    /**
     * Clear log file
     * 
     * @param string $logType Log type
     * @return bool
     */
    public function clear(string $logType = 'error'): bool {
        $logFile = $this->logDir . '/' . $this->logFiles[$logType];
        
        if (file_exists($logFile)) {
            return @unlink($logFile);
        }
        
        return true;
    }
    
    /**
     * Clear all logs older than N days
     * 
     * @param int $days Number of days
     * @return void
     */
    public function clearOld(int $days = 7): void {
        $cutoff = time() - ($days * 86400);
        
        foreach ($this->logFiles as $logFile) {
            $fullPath = $this->logDir . '/' . $logFile;
            
            if (file_exists($fullPath) && filemtime($fullPath) < $cutoff) {
                @unlink($fullPath);
            }
            
            // Clear rotated logs
            for ($i = 0; $i <= 5; $i++) {
                $rotated = $fullPath . '.' . $i;
                if (file_exists($rotated) && filemtime($rotated) < $cutoff) {
                    @unlink($rotated);
                }
            }
        }
    }
}
