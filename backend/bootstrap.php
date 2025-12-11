<?php
/**
 * =============================================================================
 * Application Bootstrap
 * =============================================================================
 * Handles autoloading and application initialization.
 * Include this file at the start of all API endpoints.
 * =============================================================================
 */

declare(strict_types=1);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Timezone
date_default_timezone_set('UTC');

/**
 * Simple PSR-4 style autoloader
 * Maps QuantumAirline namespace to backend directory
 */
spl_autoload_register(function (string $class): void {
    // Only handle our namespace
    $prefix = 'QuantumAirline\\';
    $baseDir = __DIR__ . '/';
    
    // Check if class uses our namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Map namespace to directory structure
    // QuantumAirline\Core\Database -> backend/core/Database.php
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Convert namespace parts to lowercase directory names
    $parts = explode('/', str_replace('\\', '/', $relativeClass));
    $className = array_pop($parts);
    $directory = implode('/', array_map('strtolower', $parts));
    
    $file = $baseDir . ($directory ? $directory . '/' : '') . $className . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Global exception handler for uncaught exceptions
 */
set_exception_handler(function (\Throwable $e): void {
    // Log the error
    error_log(sprintf(
        "Uncaught %s: %s in %s:%d\nStack trace:\n%s",
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));
    
    // Send JSON error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        // Include details in development (remove in production)
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ],
    ]);
    exit;
});

/**
 * Global error handler - convert errors to exceptions
 */
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
