<?php
/**
 * =============================================================================
 * JSON Response Helper
 * =============================================================================
 * Provides consistent JSON API responses with proper HTTP status codes
 * and CORS headers for the frontend.
 * =============================================================================
 */

declare(strict_types=1);

namespace QuantumAirline\Core;

class Response
{
    /**
     * Common HTTP status codes
     */
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_CONFLICT = 409;
    public const HTTP_UNPROCESSABLE = 422;
    public const HTTP_INTERNAL_ERROR = 500;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    
    /**
     * Send JSON response and exit
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     */
    public static function json(mixed $data, int $statusCode = self::HTTP_OK): never
    {
        self::setHeaders($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send success response
     * 
     * @param mixed $data Response data
     * @param string|null $message Optional success message
     * @param int $statusCode HTTP status code
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = self::HTTP_OK
    ): never {
        $response = ['success' => true];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::json($response, $statusCode);
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array|null $details Additional error details
     */
    public static function error(
        string $message,
        int $statusCode = self::HTTP_BAD_REQUEST,
        ?array $details = null
    ): never {
        $response = [
            'success' => false,
            'error' => $message,
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        self::json($response, $statusCode);
    }
    
    /**
     * Send validation error response
     * 
     * @param array $errors Validation errors (field => message)
     */
    public static function validationError(array $errors): never
    {
        self::error(
            'Validation failed',
            self::HTTP_UNPROCESSABLE,
            ['validation_errors' => $errors]
        );
    }
    
    /**
     * Send not found response
     * 
     * @param string $resource Resource type that wasn't found
     */
    public static function notFound(string $resource = 'Resource'): never
    {
        self::error("{$resource} not found", self::HTTP_NOT_FOUND);
    }
    
    /**
     * Send conflict response (e.g., seat already booked)
     * 
     * @param string $message Conflict description
     */
    public static function conflict(string $message): never
    {
        self::error($message, self::HTTP_CONFLICT);
    }
    
    /**
     * Send server error response
     * 
     * @param string $message Error message
     * @param \Throwable|null $exception Optional exception for logging
     */
    public static function serverError(
        string $message = 'Internal server error',
        ?\Throwable $exception = null
    ): never {
        // Log the exception if provided (in production, use proper logging)
        if ($exception !== null) {
            error_log("Server Error: {$exception->getMessage()}");
            error_log("Stack trace: {$exception->getTraceAsString()}");
        }
        
        self::error($message, self::HTTP_INTERNAL_ERROR);
    }
    
    /**
     * Set response headers
     * 
     * @param int $statusCode HTTP status code
     */
    private static function setHeaders(int $statusCode): void
    {
        // Prevent output buffering issues
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        // Set HTTP status code
        http_response_code($statusCode);
        
        // Set content type
        header('Content-Type: application/json; charset=utf-8');
        
        // CORS headers (for frontend)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        
        // Cache control (no caching for API responses)
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
    
    /**
     * Handle CORS preflight request
     */
    public static function handleCors(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::setHeaders(self::HTTP_OK);
            exit;
        }
    }
    
    /**
     * Get JSON request body
     * 
     * @return array Parsed JSON data
     * @throws \RuntimeException On invalid JSON
     */
    public static function getJsonBody(): array
    {
        $body = file_get_contents('php://input');
        
        if (empty($body)) {
            return [];
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::error('Invalid JSON in request body', self::HTTP_BAD_REQUEST);
        }
        
        return $data ?? [];
    }
}
