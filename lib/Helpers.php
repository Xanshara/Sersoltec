<?php
/**
 * SERSOLTEC - Helpers Class
 * Utility functions and helpers
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

namespace Sersoltec\Lib;

class Helpers {
    
    /**
     * Redirect to URL
     * 
     * @param string $url URL to redirect to
     * @param int $code HTTP status code
     * @return void
     */
    public static function redirect(string $url, int $code = 302): void {
        header("Location: $url", true, $code);
        exit;
    }
    
    /**
     * Redirect back to previous page
     * 
     * @param string $default Default URL if no referer
     * @return void
     */
    public static function back(string $default = '/'): void {
        $url = $_SERVER['HTTP_REFERER'] ?? $default;
        self::redirect($url);
    }
    
    /**
     * Get current URL
     * 
     * @return string
     */
    public static function currentUrl(): string {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return "$protocol://$host$uri";
    }
    
    /**
     * Check if current page matches path
     * 
     * @param string $path Path to check
     * @return bool
     */
    public static function isCurrentPage(string $path): bool {
        $currentPath = parse_url(self::currentUrl(), PHP_URL_PATH);
        return $currentPath === $path || strpos($currentPath, $path) === 0;
    }
    
    /**
     * Format price
     * 
     * @param float $price Price value
     * @param string $currency Currency symbol
     * @param int $decimals Number of decimals
     * @return string
     */
    public static function formatPrice(float $price, string $currency = '€', int $decimals = 2): string {
        return number_format($price, $decimals, ',', ' ') . ' ' . $currency;
    }
    
    /**
     * Format date
     * 
     * @param string $date Date string
     * @param string $format Output format
     * @return string
     */
    public static function formatDate(string $date, string $format = 'd.m.Y'): string {
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : $date;
    }
    
    /**
     * Format datetime
     * 
     * @param string $datetime Datetime string
     * @param string $format Output format
     * @return string
     */
    public static function formatDatetime(string $datetime, string $format = 'd.m.Y H:i'): string {
        return self::formatDate($datetime, $format);
    }
    
    /**
     * Time ago format (e.g., "5 minutes ago")
     * 
     * @param string $datetime Datetime string
     * @param string $lang Language (pl, en, es)
     * @return string
     */
    public static function timeAgo(string $datetime, string $lang = 'pl'): string {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        $translations = [
            'pl' => [
                'seconds' => 'sekund temu',
                'minute' => 'minutę temu',
                'minutes' => 'minut temu',
                'hour' => 'godzinę temu',
                'hours' => 'godzin temu',
                'day' => 'dzień temu',
                'days' => 'dni temu',
                'week' => 'tydzień temu',
                'weeks' => 'tygodni temu',
                'month' => 'miesiąc temu',
                'months' => 'miesięcy temu',
                'year' => 'rok temu',
                'years' => 'lat temu'
            ],
            'en' => [
                'seconds' => 'seconds ago',
                'minute' => 'a minute ago',
                'minutes' => 'minutes ago',
                'hour' => 'an hour ago',
                'hours' => 'hours ago',
                'day' => 'a day ago',
                'days' => 'days ago',
                'week' => 'a week ago',
                'weeks' => 'weeks ago',
                'month' => 'a month ago',
                'months' => 'months ago',
                'year' => 'a year ago',
                'years' => 'years ago'
            ]
        ];
        
        $t = $translations[$lang] ?? $translations['pl'];
        
        if ($diff < 60) return $diff . ' ' . $t['seconds'];
        if ($diff < 120) return $t['minute'];
        if ($diff < 3600) return floor($diff / 60) . ' ' . $t['minutes'];
        if ($diff < 7200) return $t['hour'];
        if ($diff < 86400) return floor($diff / 3600) . ' ' . $t['hours'];
        if ($diff < 172800) return $t['day'];
        if ($diff < 604800) return floor($diff / 86400) . ' ' . $t['days'];
        if ($diff < 1209600) return $t['week'];
        if ($diff < 2592000) return floor($diff / 604800) . ' ' . $t['weeks'];
        if ($diff < 5184000) return $t['month'];
        if ($diff < 31536000) return floor($diff / 2592000) . ' ' . $t['months'];
        if ($diff < 63072000) return $t['year'];
        
        return floor($diff / 31536000) . ' ' . $t['years'];
    }
    
    /**
     * Truncate string
     * 
     * @param string $text Text to truncate
     * @param int $length Max length
     * @param string $suffix Suffix (e.g., "...")
     * @return string
     */
    public static function truncate(string $text, int $length = 100, string $suffix = '...'): string {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
    }
    
    /**
     * Slugify string (URL-friendly)
     * 
     * @param string $text Text to slugify
     * @return string
     */
    public static function slugify(string $text): string {
        // Replace Polish characters
        $text = str_replace(
            ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż'],
            ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', 'a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'],
            $text
        );
        
        // Convert to lowercase
        $text = strtolower($text);
        
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        
        // Remove leading/trailing hyphens
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Generate random string
     * 
     * @param int $length String length
     * @param string $characters Character set
     * @return string
     */
    public static function randomString(int $length = 16, string $characters = 'alphanumeric'): string {
        $charsets = [
            'alphanumeric' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'alpha' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'numeric' => '0123456789',
            'hex' => '0123456789abcdef'
        ];
        
        $charset = $charsets[$characters] ?? $characters;
        $string = '';
        $max = strlen($charset) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $charset[random_int(0, $max)];
        }
        
        return $string;
    }
    
    /**
     * Array to CSV string
     * 
     * @param array $data Array data
     * @param string $delimiter CSV delimiter
     * @return string
     */
    public static function arrayToCsv(array $data, string $delimiter = ','): string {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row, $delimiter);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Dump and die (for debugging)
     * 
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    public static function dd(...$vars): void {
        echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 5px; font-family: monospace;">';
        
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n\n";
        }
        
        echo '</pre>';
        die();
    }
    
    /**
     * Get value from array with dot notation
     * 
     * @param array $array Array
     * @param string $key Key (supports dot notation, e.g., "user.name")
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function arrayGet(array $array, string $key, $default = null) {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
    
    /**
     * Check if array is associative
     * 
     * @param array $array Array to check
     * @return bool
     */
    public static function isAssoc(array $array): bool {
        if (empty($array)) {
            return false;
        }
        
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    /**
     * Convert bytes to human-readable format
     * 
     * @param int $bytes Bytes
     * @param int $precision Decimal precision
     * @return string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Generate UUID v4
     * 
     * @return string
     */
    public static function uuid(): string {
        $data = random_bytes(16);
        
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * Get environment variable with fallback
     * 
     * @param string $key Environment variable name
     * @param mixed $default Default value
     * @return mixed
     */
    public static function env(string $key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans
        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        if (strtolower($value) === 'null') return null;
        
        return $value;
    }
}
