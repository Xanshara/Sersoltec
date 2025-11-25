<?php
/**
 * SERSOLTEC - Validator Class
 * Input validation and sanitization
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

namespace Sersoltec\Lib;

class Validator {
    
    /**
     * Validation errors
     * @var array
     */
    private array $errors = [];
    
    /**
     * Validated data
     * @var array
     */
    private array $validated = [];
    
    /**
     * Validation rules
     * @var array
     */
    private array $rules = [];
    
    /**
     * Custom error messages
     * @var array
     */
    private array $messages = [
        'required' => 'Pole {field} jest wymagane',
        'email' => 'Pole {field} musi być prawidłowym adresem email',
        'min' => 'Pole {field} musi mieć minimum {param} znaków',
        'max' => 'Pole {field} może mieć maksymalnie {param} znaków',
        'numeric' => 'Pole {field} musi być liczbą',
        'alpha' => 'Pole {field} może zawierać tylko litery',
        'alphanumeric' => 'Pole {field} może zawierać tylko litery i cyfry',
        'url' => 'Pole {field} musi być prawidłowym URL',
        'match' => 'Pole {field} musi być takie samo jak {param}',
        'in' => 'Pole {field} musi być jedną z wartości: {param}',
        'unique' => 'Wartość pola {field} już istnieje',
        'date' => 'Pole {field} musi być prawidłową datą',
        'phone' => 'Pole {field} musi być prawidłowym numerem telefonu',
        'regex' => 'Pole {field} ma nieprawidłowy format'
    ];
    
    /**
     * Validate data
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool
     */
    public function validate(array $data, array $rules): bool {
        $this->errors = [];
        $this->validated = [];
        $this->rules = $rules;
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesArray = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $value, $rule, $data);
            }
            
            // Add to validated data if no errors for this field
            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Apply single validation rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule name with optional parameter
     * @param array $allData All data (for match rule)
     * @return void
     */
    private function applyRule(string $field, $value, string $rule, array $allData): void {
        // Parse rule and parameter (e.g., "min:5" -> rule="min", param="5")
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $param = $parts[1] ?? null;
        
        // Skip validation if field is empty and not required
        if (empty($value) && $ruleName !== 'required') {
            return;
        }
        
        $valid = true;
        
        switch ($ruleName) {
            case 'required':
                $valid = !empty($value);
                break;
                
            case 'email':
                $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                break;
                
            case 'min':
                $valid = strlen($value) >= (int)$param;
                break;
                
            case 'max':
                $valid = strlen($value) <= (int)$param;
                break;
                
            case 'numeric':
                $valid = is_numeric($value);
                break;
                
            case 'alpha':
                $valid = ctype_alpha(str_replace(' ', '', $value));
                break;
                
            case 'alphanumeric':
                $valid = ctype_alnum(str_replace(' ', '', $value));
                break;
                
            case 'url':
                $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
                break;
                
            case 'match':
                $valid = $value === ($allData[$param] ?? null);
                break;
                
            case 'in':
                $allowed = explode(',', $param);
                $valid = in_array($value, $allowed);
                break;
                
            case 'date':
                $valid = strtotime($value) !== false;
                break;
                
            case 'phone':
                // Polish phone format: +48 123 456 789 or 123456789
                $valid = preg_match('/^(\+48)?[0-9]{9,11}$/', str_replace([' ', '-'], '', $value));
                break;
                
            case 'regex':
                $valid = preg_match($param, $value);
                break;
                
            case 'unique':
                // Format: unique:table,column
                $parts = explode(',', $param);
                $table = $parts[0];
                $column = $parts[1] ?? $field;
                $valid = !$this->checkUnique($table, $column, $value);
                break;
        }
        
        if (!$valid) {
            $this->addError($field, $ruleName, $param);
        }
    }
    
    /**
     * Check if value is unique in database
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param mixed $value Value to check
     * @return bool
     */
    private function checkUnique(string $table, string $column, $value): bool {
        try {
            $db = Database::getInstance();
            return $db->exists($table, "$column = ?", [$value]);
        } catch (\Exception $e) {
            error_log('Validator unique check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add validation error
     * 
     * @param string $field Field name
     * @param string $rule Rule name
     * @param string|null $param Rule parameter
     * @return void
     */
    private function addError(string $field, string $rule, ?string $param = null): void {
        $message = $this->messages[$rule] ?? 'Pole {field} jest nieprawidłowe';
        $message = str_replace('{field}', $field, $message);
        $message = str_replace('{param}', $param ?? '', $message);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function errors(): array {
        return $this->errors;
    }
    
    /**
     * Get first error for a field
     * 
     * @param string $field Field name
     * @return string|null
     */
    public function firstError(string $field): ?string {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Get all errors as flat array
     * 
     * @return array
     */
    public function allErrors(): array {
        $all = [];
        foreach ($this->errors as $fieldErrors) {
            $all = array_merge($all, $fieldErrors);
        }
        return $all;
    }
    
    /**
     * Get validated data
     * 
     * @return array
     */
    public function validated(): array {
        return $this->validated;
    }
    
    /**
     * Set custom error message
     * 
     * @param string $rule Rule name
     * @param string $message Error message
     * @return void
     */
    public function setMessage(string $rule, string $message): void {
        $this->messages[$rule] = $message;
    }
    
    /**
     * Sanitize string (remove HTML tags and special chars)
     * 
     * @param string $value Input value
     * @return string
     */
    public static function sanitize(string $value): string {
        $value = trim($value);
        $value = stripslashes($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return $value;
    }
    
    /**
     * Sanitize email
     * 
     * @param string $email Email address
     * @return string
     */
    public static function sanitizeEmail(string $email): string {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize URL
     * 
     * @param string $url URL
     * @return string
     */
    public static function sanitizeUrl(string $url): string {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitize integer
     * 
     * @param mixed $value Input value
     * @return int
     */
    public static function sanitizeInt($value): int {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     * 
     * @param mixed $value Input value
     * @return float
     */
    public static function sanitizeFloat($value): float {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
