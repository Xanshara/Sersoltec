<?php
/**
 * SERSOLTEC - Database Class
 * Singleton PDO wrapper with connection pooling, error handling, and query building
 * 
 * @package Sersoltec\Lib
 * @version 2.0.0
 */

namespace Sersoltec\Lib;

use PDO;
use PDOException;
use PDOStatement;

class Database {
    
    /**
     * Singleton instance
     * @var Database|null
     */
    private static ?Database $instance = null;
    
    /**
     * PDO connection
     * @var PDO|null
     */
    private ?PDO $pdo = null;
    
    /**
     * Configuration
     * @var array
     */
    private array $config;
    
    /**
     * Query log for debugging
     * @var array
     */
    private array $queryLog = [];
    
    /**
     * Enable query logging
     * @var bool
     */
    private bool $logQueries = false;
    
    /**
     * Private constructor (Singleton)
     * 
     * @param array $config Database configuration
     */
    private function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }
    
    /**
     * Get singleton instance
     * 
     * @param array|null $config Configuration (required for first call)
     * @return Database
     * @throws \RuntimeException
     */
    public static function getInstance(?array $config = null): Database {
        if (self::$instance === null) {
            if ($config === null) {
                throw new \RuntimeException('Database configuration required for first initialization');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     * 
     * @return void
     * @throws PDOException
     */
    private function connect(): void {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['database'],
                $this->config['charset'] ?? 'utf8mb4'
            );
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );
            
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new PDOException('Could not connect to database');
        }
    }
    
    /**
     * Get PDO instance (for backward compatibility)
     * 
     * @return PDO
     */
    public function getPdo(): PDO {
        return $this->pdo;
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return PDOStatement
     * @throws PDOException
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($this->logQueries) {
                $this->queryLog[] = [
                    'sql' => $sql,
                    'params' => $params,
                    'time' => microtime(true)
                ];
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            error_log('Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }
    
    /**
     * Fetch all results
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetch single row
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array|false
     */
    public function fetchOne(string $sql, array $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch single column value
     * 
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return mixed
     */
    public function fetchColumn(string $sql, array $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Insert record and return last insert ID
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return string Last insert ID
     * @throws PDOException
     */
    public function insert(string $table, array $data): string {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update records
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause (e.g., "id = ?")
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     * @throws PDOException
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "$column = ?";
        }
        
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $sets),
            $where
        );
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete records
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (e.g., "id = ?")
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     * @throws PDOException
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Check if record exists
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters
     * @return bool
     */
    public function exists(string $table, string $where, array $params = []): bool {
        $sql = sprintf('SELECT 1 FROM %s WHERE %s LIMIT 1', $table, $where);
        $result = $this->fetchColumn($sql, $params);
        return $result !== false;
    }
    
    /**
     * Count records
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (optional)
     * @param array $params Parameters (optional)
     * @return int
     */
    public function count(string $table, string $where = '1=1', array $params = []): int {
        $sql = sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table, $where);
        return (int) $this->fetchColumn($sql, $params);
    }
    
    /**
     * Begin transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     * 
     * @return bool
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool
     */
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }
    
    /**
     * Enable query logging
     * 
     * @param bool $enable
     * @return void
     */
    public function enableQueryLog(bool $enable = true): void {
        $this->logQueries = $enable;
    }
    
    /**
     * Get query log
     * 
     * @return array
     */
    public function getQueryLog(): array {
        return $this->queryLog;
    }
    
    /**
     * Clear query log
     * 
     * @return void
     */
    public function clearQueryLog(): void {
        $this->queryLog = [];
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
