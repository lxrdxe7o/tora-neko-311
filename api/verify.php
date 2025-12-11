<?php
/**
 * =============================================================================
 * Ticket Verification API Endpoint
 * =============================================================================
 * POST /api/verify.php - Verify a ticket's quantum signature
 * 
 * Request Body:
 * {
 *     "booking_ref": "QX7A9B2C"
 * }
 * 
 * Response: Verification result with ticket details
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use QuantumAirline\Core\Response;
use QuantumAirline\Services\BookingService;

// Handle CORS preflight
Response::handleCors();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed. Use POST.', 405);
}

try {
    // Parse request body
    $data = Response::getJsonBody();
    
    // Validate booking reference
    if (!isset($data['booking_ref']) || empty(trim($data['booking_ref']))) {
        Response::validationError(['booking_ref' => 'Booking reference is required']);
    }
    
    $bookingRef = strtoupper(trim($data['booking_ref']));
    
    // Validate format (alphanumeric, 8 characters)
    if (!preg_match('/^[A-Z0-9]{6,16}$/', $bookingRef)) {
        Response::validationError(['booking_ref' => 'Invalid booking reference format']);
    }
    
    // Verify the ticket
    $bookingService = new BookingService();
    $result = $bookingService->verifyTicket($bookingRef);
    
    // Format response based on verification result
    if ($result['valid']) {
        Response::success([
            'verified' => true,
            'message' => 'Ticket signature is valid - quantum-secure authenticity confirmed',
            'booking_ref' => $result['booking_ref'],
            'ticket' => $result['ticket_data'],
            'security' => [
                'signature_algorithm' => $result['signature_algorithm'],
                'mock_mode' => $result['mock_mode'],
                'verification_method' => $result['mock_mode'] 
                    ? 'HMAC-SHA512 (Mock Mode)' 
                    : 'Dilithium3 Lattice-based Signature',
            ],
        ]);
    } else {
        Response::json([
            'success' => false,
            'verified' => false,
            'message' => 'Ticket signature verification FAILED - possible tampering detected',
            'booking_ref' => $result['booking_ref'],
            'security' => [
                'signature_algorithm' => $result['signature_algorithm'],
                'mock_mode' => $result['mock_mode'],
            ],
        ], Response::HTTP_OK);  // Still 200 - verification completed, just failed
    }
    
} catch (\RuntimeException $e) {
    if ($e->getCode() === 404) {
        Response::notFound('Booking');
    }
    Response::error($e->getMessage(), Response::HTTP_BAD_REQUEST);
    
} catch (\Throwable $e) {
    Response::serverError('Verification failed', $e);
}
