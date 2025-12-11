<?php
/**
 * =============================================================================
 * Database Connection Singleton
 * =============================================================================
 * Provides a single PDO connection to PostgreSQL with transaction support.
 * 
 * Features:
 *   - Singleton pattern ensures one connection per request
 *   - Transaction helper methods (begin, commit, rollback)
 *   - Automatic connection management
 *   - ACID-compliant operations
 * 
 * Usage:
 *   $db = Database::getInstance();
 *   $db->beginTransaction();
 *   try {
 *       // ... database operations
 *       $db->commit();
 *   } catch (Exception $e) {
 *       $db->rollback();
 *       throw $e;
 *   }
 * =============================================================================
 */

declare(strict_types=1);

namespace QuantumAirline\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    /**
     * Singleton instance
     */
    private static ?Database $instance = null;
    
    /**
     * PDO connection
     */
    private PDO $pdo;
    
    /**
     * Transaction depth (for nested transactions)
     */
    private int $transactionDepth = 0;
    
    /**
     * Configuration array
     */
    private array $config;
    
    /**
     * Private constructor (singleton pattern)
     */
    private function __construct()
    {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->connect();
    }
    
    /**
     * Prevent cloning (singleton pattern)
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization (singleton pattern)
     */
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }
    
    /**
     * Get the singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect(): void
    {
        $dsn = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s",
            $this->config['host'],
            $this->config['port'],
            $this->config['dbname']
        );
        
        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['password'],
                $this->config['options']
            );
            
            // Set connection timeout
            if (isset($this->config['timeout'])) {
                $this->pdo->setAttribute(
                    PDO::ATTR_TIMEOUT,
                    $this->config['timeout']
                );
            }
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Database connection failed: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Get the PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
    
    /**
     * Begin a transaction
     * 
     * Supports nested transactions using savepoints.
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionDepth === 0) {
            $result = $this->pdo->beginTransaction();
        } else {
            // Use savepoint for nested transaction
            $this->pdo->exec("SAVEPOINT trans_{$this->transactionDepth}");
            $result = true;
        }
        
        $this->transactionDepth++;
        return $result;
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): bool
    {
        if ($this->transactionDepth === 0) {
            throw new RuntimeException("No active transaction to commit");
        }
        
        $this->transactionDepth--;
        
        if ($this->transactionDepth === 0) {
            return $this->pdo->commit();
        } else {
            // Release savepoint for nested transaction
            $this->pdo->exec("RELEASE SAVEPOINT trans_{$this->transactionDepth}");
            return true;
        }
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback(): bool
    {
        if ($this->transactionDepth === 0) {
            throw new RuntimeException("No active transaction to rollback");
        }
        
        $this->transactionDepth--;
        
        if ($this->transactionDepth === 0) {
            return $this->pdo->rollBack();
        } else {
            // Rollback to savepoint for nested transaction
            $this->pdo->exec("ROLLBACK TO SAVEPOINT trans_{$this->transactionDepth}");
            return true;
        }
    }
    
    /**
     * Check if a transaction is active
     */
    public function inTransaction(): bool
    {
        return $this->transactionDepth > 0;
    }
    
    /**
     * Execute a query with prepared statement
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch a single row
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute an insert and return the last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders}) RETURNING id";
        $stmt = $this->query($sql, array_values($data));
        
        $result = $stmt->fetch();
        return (int) $result['id'];
    }
    
    /**
     * Execute an update
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $stmt = $this->query($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }
    
    /**
     * Get the last error info
     */
    public function getLastError(): array
    {
        return $this->pdo->errorInfo();
    }
}
