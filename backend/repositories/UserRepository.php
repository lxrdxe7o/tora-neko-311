<?php
/**
 * =============================================================================
 * User Repository
 * =============================================================================
 * Data access layer for user operations.
 * =============================================================================
 */

declare(strict_types=1);

namespace QuantumAirline\Repositories;

use QuantumAirline\Core\Database;

class UserRepository
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find a user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, email, name, pqc_public_key, created_at FROM users WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Find a user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT id, email, name, pqc_public_key, created_at FROM users WHERE email = ?";
        return $this->db->fetchOne($sql, [$email]);
    }
    
    /**
     * Create a new user
     * 
     * @param string $email User email
     * @param string $name User name
     * @return int Created user ID
     */
    public function create(string $email, string $name): int
    {
        return $this->db->insert('users', [
            'email' => $email,
            'name' => $name,
        ]);
    }
    
    /**
     * Update user's PQC public key
     * 
     * @param int $userId User ID
     * @param string $publicKey Dilithium3 public key (hex)
     * @return bool True if update succeeded
     */
    public function updatePublicKey(int $userId, string $publicKey): bool
    {
        $sql = "UPDATE users SET pqc_public_key = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->query($sql, [$publicKey, $userId]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get the demo user (for this demonstration system)
     * 
     * @return array Demo user data
     */
    public function getDemoUser(): array
    {
        $user = $this->findById(1);
        
        if ($user === null) {
            // Create demo user if it doesn't exist
            $this->db->query(
                "INSERT INTO users (id, email, name) VALUES (1, 'demo@quantum-air.com', 'Demo User') ON CONFLICT (id) DO NOTHING"
            );
            $user = $this->findById(1);
        }
        
        return $user;
    }
}
