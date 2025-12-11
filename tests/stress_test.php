<?php
/**
 * =============================================================================
 * Stress Test - Concurrent Booking Simulation
 * =============================================================================
 * Simulates 20 concurrent users attempting to book the same seat.
 * Proves that PostgreSQL SELECT ... FOR UPDATE prevents double-booking.
 * 
 * Expected Result:
 *   - 1 successful booking
 *   - 19 failures (seat already booked / lock conflict)
 * 
 * Usage:
 *   php tests/stress_test.php [seat_id] [num_requests]
 * 
 * Example:
 *   php tests/stress_test.php 25 20
 * =============================================================================
 */

declare(strict_types=1);

// =============================================================================
// Configuration
// =============================================================================

// API endpoint (adjust if running on different host/port)
$apiUrl = 'http://localhost:8000/api/book.php';

// Default values
$seatId = (int) ($argv[1] ?? 25);        // Default to seat 25
$numRequests = (int) ($argv[2] ?? 20);   // Default to 20 concurrent requests

// Test passenger data template
$passengerTemplate = [
    'seat_id' => $seatId,
    'passenger_name' => 'Stress Test User %d',
    'passport_number' => 'TEST%06d',
];

// =============================================================================
// Display Banner
// =============================================================================

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
echo "║           QUANTUM AIRWAYS - CONCURRENT BOOKING STRESS TEST                ║\n";
echo "╠═══════════════════════════════════════════════════════════════════════════╣\n";
echo "║  This test simulates multiple users trying to book the SAME seat          ║\n";
echo "║  simultaneously. PostgreSQL's SELECT ... FOR UPDATE row locking           ║\n";
echo "║  should ensure only ONE booking succeeds.                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "Configuration:\n";
echo "  API URL:        {$apiUrl}\n";
echo "  Target Seat ID: {$seatId}\n";
echo "  Concurrent Req: {$numRequests}\n";
echo "\n";

// =============================================================================
// Verify cURL multi is available
// =============================================================================

if (!function_exists('curl_multi_init')) {
    die("ERROR: PHP cURL extension with multi support is required.\n");
}

// =============================================================================
// Prepare Requests
// =============================================================================

echo "Preparing {$numRequests} concurrent booking requests...\n\n";

$multiHandle = curl_multi_init();
$curlHandles = [];

for ($i = 1; $i <= $numRequests; $i++) {
    $postData = json_encode([
        'seat_id' => $passengerTemplate['seat_id'],
        'passenger_name' => sprintf($passengerTemplate['passenger_name'], $i),
        'passport_number' => sprintf($passengerTemplate['passport_number'], $i),
    ]);
    
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[$i] = $ch;
}

// =============================================================================
// Execute Requests Concurrently
// =============================================================================

echo "Launching concurrent requests...\n";
echo str_repeat("═", 75) . "\n\n";

$startTime = microtime(true);

// Start executing
$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

// =============================================================================
// Collect Results
// =============================================================================

$results = [
    'success' => [],
    'conflict' => [],
    'error' => [],
];

foreach ($curlHandles as $requestId => $ch) {
    $response = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error) {
        $results['error'][] = [
            'request_id' => $requestId,
            'error' => $error,
        ];
    } else {
        $data = json_decode($response, true);
        
        if ($httpCode === 201 && ($data['success'] ?? false)) {
            $results['success'][] = [
                'request_id' => $requestId,
                'booking_ref' => $data['booking']['booking_ref'] ?? 'N/A',
                'passenger' => $data['booking']['passenger_name'] ?? 'N/A',
            ];
        } else {
            $results['conflict'][] = [
                'request_id' => $requestId,
                'http_code' => $httpCode,
                'error' => $data['error'] ?? 'Unknown error',
            ];
        }
    }
    
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);

// =============================================================================
// Display Results
// =============================================================================

echo "RESULTS\n";
echo str_repeat("─", 75) . "\n\n";

// Successful bookings
echo "✓ SUCCESSFUL BOOKINGS: " . count($results['success']) . "\n";
if (!empty($results['success'])) {
    foreach ($results['success'] as $booking) {
        echo "    Request #{$booking['request_id']}: ";
        echo "Booking Ref: {$booking['booking_ref']} | ";
        echo "Passenger: {$booking['passenger']}\n";
    }
}
echo "\n";

// Conflicts (expected)
echo "✗ CONFLICTS (Expected): " . count($results['conflict']) . "\n";
if (!empty($results['conflict'])) {
    // Group by error type
    $errorCounts = [];
    foreach ($results['conflict'] as $conflict) {
        $errorKey = $conflict['error'];
        if (!isset($errorCounts[$errorKey])) {
            $errorCounts[$errorKey] = 0;
        }
        $errorCounts[$errorKey]++;
    }
    
    foreach ($errorCounts as $error => $count) {
        echo "    {$count}x: {$error}\n";
    }
}
echo "\n";

// Errors (unexpected)
if (!empty($results['error'])) {
    echo "⚠ UNEXPECTED ERRORS: " . count($results['error']) . "\n";
    foreach ($results['error'] as $error) {
        echo "    Request #{$error['request_id']}: {$error['error']}\n";
    }
    echo "\n";
}

// =============================================================================
// Summary
// =============================================================================

echo str_repeat("═", 75) . "\n";
echo "SUMMARY\n";
echo str_repeat("─", 75) . "\n";

$totalSuccess = count($results['success']);
$totalConflicts = count($results['conflict']);
$totalErrors = count($results['error']);
$totalProcessed = $totalSuccess + $totalConflicts + $totalErrors;

echo "  Total Requests:     {$numRequests}\n";
echo "  Total Processed:    {$totalProcessed}\n";
echo "  Successful:         {$totalSuccess}\n";
echo "  Conflicts:          {$totalConflicts}\n";
echo "  Errors:             {$totalErrors}\n";
echo "  Duration:           {$duration} ms\n";
echo "\n";

// Validation
$testPassed = ($totalSuccess === 1) && ($totalErrors === 0);

if ($testPassed) {
    echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ TEST PASSED - Row locking is working correctly!                        ║\n";
    echo "║    Only 1 booking succeeded out of {$numRequests} concurrent attempts.                 ║\n";
    echo "║    Double-booking has been prevented by SELECT ... FOR UPDATE.            ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
} elseif ($totalSuccess === 0) {
    echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⚠ NO BOOKINGS SUCCEEDED                                                  ║\n";
    echo "║    This could mean:                                                       ║\n";
    echo "║    - The seat was already booked before the test                          ║\n";
    echo "║    - The API endpoint is not accessible                                   ║\n";
    echo "║    - Database connection issues                                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
} elseif ($totalSuccess > 1) {
    echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✗ TEST FAILED - DOUBLE BOOKING DETECTED!                                 ║\n";
    echo "║    {$totalSuccess} bookings succeeded - this should not happen!                        ║\n";
    echo "║    Row locking may not be working correctly.                              ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
}

echo "\n";

// Return appropriate exit code
exit($testPassed ? 0 : 1);
