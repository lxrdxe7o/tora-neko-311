<?php
/**
 * =============================================================================
 * Database Configuration
 * =============================================================================
 * PostgreSQL connection settings for the Quantum-Secure Airline Booking System.
 * 
 * SECURITY NOTE:
 * In production, these values should be loaded from environment variables:
 *   - $_ENV['DB_HOST']
 *   - $_ENV['DB_PORT']
 *   - $_ENV['DB_NAME']
 *   - $_ENV['DB_USER']
 *   - $_ENV['DB_PASSWORD']
 * 
 * You can also create a database.local.php file (gitignored) to override these
 * settings without modifying this file.
 * =============================================================================
 */

// Check for local override file
$localConfig = __DIR__ . '/database.local.php';
if (file_exists($localConfig)) {
    return require $localConfig;
}

// Default configuration
return [
    // PostgreSQL connection parameters
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => getenv('DB_PORT') ?: 5432,
    'dbname'   => getenv('DB_NAME') ?: 'quantum_airline',
    'user'     => getenv('DB_USER') ?: 'postgres',
    'password' => getenv('DB_PASSWORD') ?: 'postgres',
    
    // PDO options
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Use persistent connections for better performance
        PDO::ATTR_PERSISTENT         => false,
    ],
    
    // Connection timeout (seconds)
    'timeout'  => 5,
];
