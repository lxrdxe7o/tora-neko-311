<?php
/**
 * =============================================================================
 * Seats API Endpoint
 * =============================================================================
 * GET /api/seats.php?flight_id=X - Get seat map for a flight
 * =============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/bootstrap.php';

use QuantumAirline\Core\Response;
use QuantumAirline\Repositories\FlightRepository;
use QuantumAirline\Repositories\SeatRepository;

// Handle CORS preflight
Response::handleCors();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

try {
    // Validate flight_id parameter
    if (!isset($_GET['flight_id'])) {
        Response::error('Missing required parameter: flight_id', Response::HTTP_BAD_REQUEST);
    }
    
    $flightId = filter_var($_GET['flight_id'], FILTER_VALIDATE_INT);
    
    if ($flightId === false || $flightId <= 0) {
        Response::error('Invalid flight ID', Response::HTTP_BAD_REQUEST);
    }
    
    // Verify flight exists
    $flightRepo = new FlightRepository();
    $flight = $flightRepo->findById($flightId);
    
    if ($flight === null) {
        Response::notFound('Flight');
    }
    
    // Get seat map
    $seatRepo = new SeatRepository();
    $seatMap = $seatRepo->getSeatMap($flightId);
    
    // Calculate availability stats
    $availableCount = 0;
    $totalCount = 0;
    $classCounts = [
        'first' => ['available' => 0, 'total' => 0],
        'business' => ['available' => 0, 'total' => 0],
        'economy' => ['available' => 0, 'total' => 0],
    ];
    
    foreach ($seatMap as $row) {
        foreach ($row['seats'] as $seat) {
            $totalCount++;
            $class = $row['class'];
            $classCounts[$class]['total']++;
            
            if (!$seat['is_booked']) {
                $availableCount++;
                $classCounts[$class]['available']++;
            }
        }
    }
    
    Response::success([
        'flight' => [
            'id' => (int) $flight['id'],
            'flight_number' => $flight['flight_number'],
            'origin' => $flight['origin'],
            'destination' => $flight['destination'],
            'departure_time' => $flight['departure_time'],
            'price' => (float) $flight['price'],
        ],
        'seat_map' => $seatMap,
        'statistics' => [
            'total_seats' => $totalCount,
            'available_seats' => $availableCount,
            'booked_seats' => $totalCount - $availableCount,
            'by_class' => $classCounts,
        ],
        'legend' => [
            'columns' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'aisle_after' => 'C',
            'classes' => [
                'first' => 'Rows 1-2',
                'business' => 'Rows 3-4',
                'economy' => 'Rows 5-10',
            ],
        ],
    ]);
    
} catch (\Throwable $e) {
    Response::serverError('Failed to fetch seats', $e);
}
