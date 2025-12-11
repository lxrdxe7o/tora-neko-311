<?php
/**
 * =============================================================================
 * Booking API Endpoint
 * =============================================================================
 * POST /api/book.php - Create a quantum-secured booking
 * 
 * Request Body:
 * {
 *     "seat_id": 15,
 *     "passenger_name": "John Quantum",
 *     "passport_number": "AB1234567"
 * }
 * 
 * Response: Full booking details with quantum security information
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use QuantumAirline\Core\Response;
use QuantumAirline\Services\BookingService;
use QuantumAirline\Repositories\UserRepository;

// Handle CORS preflight
Response::handleCors();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed. Use POST.', 405);
}

try {
    // Parse request body
    $data = Response::getJsonBody();
    
    // Validate required fields
    $errors = [];
    
    if (!isset($data['seat_id'])) {
        $errors['seat_id'] = 'Seat ID is required';
    } elseif (!is_numeric($data['seat_id']) || (int) $data['seat_id'] <= 0) {
        $errors['seat_id'] = 'Invalid seat ID';
    }
    
    if (!isset($data['passenger_name']) || empty(trim($data['passenger_name']))) {
        $errors['passenger_name'] = 'Passenger name is required';
    }
    
    if (!isset($data['passport_number']) || empty(trim($data['passport_number']))) {
        $errors['passport_number'] = 'Passport number is required';
    }
    
    if (!empty($errors)) {
        Response::validationError($errors);
    }
    
    // Get demo user (in a real system, this would come from authentication)
    $userRepo = new UserRepository();
    $user = $userRepo->getDemoUser();
    
    // Create booking
    $bookingService = new BookingService();
    
    $result = $bookingService->createBooking(
        (int) $user['id'],
        (int) $data['seat_id'],
        trim($data['passenger_name']),
        trim($data['passport_number'])
    );
    
    // Return success response
    Response::json($result, Response::HTTP_CREATED);
    
} catch (\RuntimeException $e) {
    $code = $e->getCode();
    
    // Handle specific error codes
    if ($code === 404) {
        Response::notFound($e->getMessage());
    } elseif ($code === 409) {
        Response::conflict($e->getMessage());
    } elseif ($code === 422) {
        // Validation error from service
        $details = json_decode($e->getMessage(), true);
        if ($details && isset($details['validation_errors'])) {
            Response::validationError($details['validation_errors']);
        }
        Response::error($e->getMessage(), Response::HTTP_UNPROCESSABLE);
    }
    
    Response::error($e->getMessage(), Response::HTTP_BAD_REQUEST);
    
} catch (\Throwable $e) {
    Response::serverError('Booking failed: ' . $e->getMessage(), $e);
}
