<?php
/**
 * =============================================================================
 * Flights API Endpoint
 * =============================================================================
 * GET /api/flights.php - List all available flights
 * GET /api/flights.php?id=X - Get specific flight details
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use QuantumAirline\Core\Response;
use QuantumAirline\Repositories\FlightRepository;

// Handle CORS preflight
Response::handleCors();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    $flightRepo = new FlightRepository();
    
    // Check if specific flight requested
    if (isset($_GET['id'])) {
        $flightId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        
        if ($flightId === false || $flightId <= 0) {
            Response::error('Invalid flight ID', Response::HTTP_BAD_REQUEST);
        }
        
        $flight = $flightRepo->getFlightSummary($flightId);
        
        if ($flight === null) {
            Response::notFound('Flight');
        }
        
        Response::success($flight);
    }
    
    // List all available flights
    $flights = $flightRepo->findAllAvailable();
    
    // Format for response
    $formattedFlights = array_map(function ($flight) {
        return [
            'id' => (int) $flight['id'],
            'flight_number' => $flight['flight_number'],
            'origin' => $flight['origin'],
            'destination' => $flight['destination'],
            'departure_time' => $flight['departure_time'],
            'arrival_time' => $flight['arrival_time'],
            'price' => (float) $flight['price'],
            'aircraft_type' => $flight['aircraft_type'],
            'status' => $flight['status'],
            'available_seats' => (int) $flight['available_seats'],
            'total_seats' => (int) $flight['total_seats'],
        ];
    }, $flights);
    
    Response::success([
        'flights' => $formattedFlights,
        'count' => count($formattedFlights),
    ]);
    
} catch (\Throwable $e) {
    Response::serverError('Failed to fetch flights', $e);
}
