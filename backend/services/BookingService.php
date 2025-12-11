<?php
/**
 * =============================================================================
 * Booking Service
 * =============================================================================
 * Main business logic for creating quantum-secured bookings.
 * 
 * This service orchestrates the complete booking process:
 *   1. Begin database transaction
 *   2. Lock seat using SELECT ... FOR UPDATE (prevents double-booking)
 *   3. Generate QRNG booking reference
 *   4. Encrypt passport data using Kyber512 hybrid encryption
 *   5. Create and sign ticket data using Dilithium3
 *   6. Persist booking record
 *   7. Mark seat as booked
 *   8. Commit transaction
 * 
 * The Quantum Trinity:
 *   - IDENTITY (Dilithium3): Unforgeable ticket signatures
 *   - CONFIDENTIALITY (Kyber512): Quantum-safe passport encryption
 *   - RANDOMNESS (QRNG): True random booking references
 * =============================================================================
 */

declare(strict_types=1);

namespace QuantumAirline\Services;

use QuantumAirline\Core\Database;
use QuantumAirline\Core\QuantumBridge;
use QuantumAirline\Repositories\FlightRepository;
use QuantumAirline\Repositories\SeatRepository;
use QuantumAirline\Repositories\BookingRepository;
use QuantumAirline\Repositories\UserRepository;
use RuntimeException;

class BookingService
{
    private Database $db;
    private QuantumBridge $quantum;
    private FlightRepository $flightRepo;
    private SeatRepository $seatRepo;
    private BookingRepository $bookingRepo;
    private UserRepository $userRepo;
    
    /**
     * Maximum attempts to generate unique booking reference
     */
    private const MAX_REF_ATTEMPTS = 5;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->quantum = new QuantumBridge();
        $this->flightRepo = new FlightRepository();
        $this->seatRepo = new SeatRepository();
        $this->bookingRepo = new BookingRepository();
        $this->userRepo = new UserRepository();
    }
    
    /**
     * Create a quantum-secured booking
     * 
     * This is the main entry point for the booking process.
     * 
     * @param int $userId User ID (for demo, typically 1)
     * @param int $seatId Selected seat ID
     * @param string $passengerName Passenger full name
     * @param string $passportNumber Passport number (will be encrypted)
     * @return array Complete booking result with quantum security info
     * @throws RuntimeException On booking failure
     */
    public function createBooking(
        int $userId,
        int $seatId,
        string $passengerName,
        string $passportNumber
    ): array {
        // Validate inputs
        $this->validateInputs($passengerName, $passportNumber);
        
        // Get user
        $user = $this->userRepo->findById($userId);
        if ($user === null) {
            throw new RuntimeException("User not found", 404);
        }
        
        // Begin transaction
        $this->db->beginTransaction();
        
        try {
            // =========================================================
            // STEP 1: Lock the seat (CRITICAL for preventing double-booking)
            // =========================================================
            // This acquires an exclusive row lock on the seat.
            // Any other transaction trying to book this seat will block
            // until this transaction completes.
            $seat = $this->seatRepo->lockForBooking($seatId);
            
            // Get flight info
            $flight = $this->flightRepo->findById((int) $seat['flight_id']);
            if ($flight === null) {
                throw new RuntimeException("Flight not found", 404);
            }
            
            // =========================================================
            // STEP 2: Generate QRNG booking reference
            // =========================================================
            // Uses simulated quantum Hadamard gate measurements to generate
            // a truly random booking reference ID.
            $bookingRef = $this->generateUniqueBookingRef();
            
            // =========================================================
            // STEP 3: Encrypt passport data using Kyber512
            // =========================================================
            // Kyber512 (NIST FIPS 203) provides quantum-safe key encapsulation.
            // The passport number is encrypted with AES-256-GCM using a key
            // that was exchanged via Kyber KEM.
            $encryption = $this->quantum->encrypt($passportNumber);
            
            // =========================================================
            // STEP 4: Create ticket data payload for signing
            // =========================================================
            $ticketData = $this->buildTicketData(
                $bookingRef,
                $flight,
                $seat,
                $passengerName,
                $userId
            );
            $ticketDataJson = json_encode($ticketData, JSON_THROW_ON_ERROR);
            
            // =========================================================
            // STEP 5: Sign ticket data using Dilithium3
            // =========================================================
            // Dilithium3 (NIST FIPS 204) provides quantum-safe digital signatures.
            // Even a quantum computer cannot forge this signature.
            $signature = $this->quantum->sign($ticketDataJson);
            
            // =========================================================
            // STEP 6: Create booking record
            // =========================================================
            $bookingId = $this->bookingRepo->create([
                'user_id' => $userId,
                'seat_id' => $seatId,
                'flight_id' => (int) $seat['flight_id'],
                'qrng_booking_ref' => $bookingRef,
                'passenger_name' => $passengerName,
                'encrypted_passport_data' => $encryption['ciphertext'],
                'kyber_encapsulated_key' => $encryption['encapsulated_key'],
                'encryption_nonce' => $encryption['nonce'],
                'pqc_signature' => $signature['signature'],
                'pqc_public_key' => $signature['public_key'],
                'ticket_data_hash' => $signature['data_hash'],
                'mock_mode' => $signature['mock_mode'] || $encryption['mock_mode'],
                'signature_algorithm' => $signature['algorithm'],
                'encryption_algorithm' => $encryption['algorithm'],
            ]);
            
            // =========================================================
            // STEP 7: Mark seat as booked
            // =========================================================
            $this->seatRepo->markAsBooked($seatId, (int) $seat['lock_version']);
            
            // =========================================================
            // STEP 8: Commit transaction
            // =========================================================
            $this->db->commit();
            
            // =========================================================
            // Return complete booking result
            // =========================================================
            return $this->buildBookingResponse(
                $bookingId,
                $bookingRef,
                $flight,
                $seat,
                $passengerName,
                $ticketData,
                $signature,
                $encryption
            );
            
        } catch (\Throwable $e) {
            // Rollback on any error
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Verify a ticket's quantum signature
     * 
     * @param string $bookingRef Booking reference
     * @return array Verification result
     */
    public function verifyTicket(string $bookingRef): array
    {
        // Get booking
        $booking = $this->bookingRepo->findByReference($bookingRef);
        
        if ($booking === null) {
            throw new RuntimeException("Booking not found", 404);
        }
        
        // Reconstruct ticket data for verification
        $ticketData = [
            'booking_ref' => $booking['qrng_booking_ref'],
            'flight_number' => $booking['flight_number'],
            'origin' => $booking['origin'],
            'destination' => $booking['destination'],
            'departure' => $booking['departure_time'],
            'seat' => $booking['seat_label'],
            'seat_class' => $booking['seat_class'],
            'passenger' => $booking['passenger_name'],
            'user_id' => (int) $booking['user_id'],
            'timestamp' => $booking['created_at'],
        ];
        
        $ticketDataJson = json_encode($ticketData, JSON_THROW_ON_ERROR);
        
        // Verify signature
        $verification = $this->quantum->verify(
            $ticketDataJson,
            $booking['pqc_signature'],
            $booking['pqc_public_key']
        );
        
        return [
            'valid' => $verification['valid'],
            'booking_ref' => $bookingRef,
            'ticket_data' => $ticketData,
            'signature_algorithm' => $booking['signature_algorithm'],
            'mock_mode' => (bool) $booking['mock_mode'],
            'verification_details' => $verification,
        ];
    }
    
    /**
     * Validate booking inputs
     */
    private function validateInputs(string $passengerName, string $passportNumber): void
    {
        $errors = [];
        
        if (empty(trim($passengerName))) {
            $errors['passenger_name'] = 'Passenger name is required';
        } elseif (strlen($passengerName) < 2) {
            $errors['passenger_name'] = 'Passenger name must be at least 2 characters';
        } elseif (strlen($passengerName) > 255) {
            $errors['passenger_name'] = 'Passenger name must not exceed 255 characters';
        }
        
        if (empty(trim($passportNumber))) {
            $errors['passport_number'] = 'Passport number is required';
        } elseif (strlen($passportNumber) < 5) {
            $errors['passport_number'] = 'Invalid passport number format';
        } elseif (strlen($passportNumber) > 20) {
            $errors['passport_number'] = 'Passport number too long';
        }
        
        if (!empty($errors)) {
            throw new RuntimeException(
                json_encode(['validation_errors' => $errors]),
                422
            );
        }
    }
    
    /**
     * Generate a unique QRNG booking reference
     */
    private function generateUniqueBookingRef(): string
    {
        for ($i = 0; $i < self::MAX_REF_ATTEMPTS; $i++) {
            $result = $this->quantum->generateBookingRef(8);
            $ref = $result['random_id'];
            
            // Ensure uniqueness
            if (!$this->bookingRepo->referenceExists($ref)) {
                return $ref;
            }
        }
        
        throw new RuntimeException(
            "Failed to generate unique booking reference after " . self::MAX_REF_ATTEMPTS . " attempts"
        );
    }
    
    /**
     * Build ticket data payload for signing
     */
    private function buildTicketData(
        string $bookingRef,
        array $flight,
        array $seat,
        string $passengerName,
        int $userId
    ): array {
        return [
            'booking_ref' => $bookingRef,
            'flight_number' => $flight['flight_number'],
            'origin' => $flight['origin'],
            'destination' => $flight['destination'],
            'departure' => $flight['departure_time'],
            'seat' => $seat['seat_label'],
            'seat_class' => $seat['class'],
            'passenger' => $passengerName,
            'user_id' => $userId,
            'timestamp' => date('c'),  // ISO 8601 timestamp
        ];
    }
    
    /**
     * Build the complete booking response
     */
    private function buildBookingResponse(
        int $bookingId,
        string $bookingRef,
        array $flight,
        array $seat,
        string $passengerName,
        array $ticketData,
        array $signature,
        array $encryption
    ): array {
        return [
            'success' => true,
            'booking' => [
                'id' => $bookingId,
                'booking_ref' => $bookingRef,
                'passenger_name' => $passengerName,
                'flight' => [
                    'number' => $flight['flight_number'],
                    'origin' => $flight['origin'],
                    'destination' => $flight['destination'],
                    'departure' => $flight['departure_time'],
                    'arrival' => $flight['arrival_time'],
                    'price' => (float) $flight['price'],
                ],
                'seat' => [
                    'label' => $seat['seat_label'],
                    'row' => (int) $seat['row_number'],
                    'col' => $seat['col_letter'],
                    'class' => $seat['class'],
                ],
            ],
            'quantum_security' => [
                'mock_mode' => $signature['mock_mode'] || $encryption['mock_mode'],
                'signature' => [
                    'algorithm' => $signature['algorithm'],
                    'preview' => substr($signature['signature'], 0, 64) . '...',
                    'full_length_bytes' => strlen($signature['signature']) / 2,
                    'public_key_preview' => substr($signature['public_key'], 0, 64) . '...',
                ],
                'encryption' => [
                    'algorithm' => $encryption['algorithm'],
                    'description' => 'Passport data encrypted with AES-256-GCM, key exchanged via Kyber512 KEM',
                ],
                'entropy' => [
                    'source' => 'QRNG-Hadamard',
                    'description' => 'Booking reference generated using quantum random number simulation',
                ],
            ],
            'ticket_data' => $ticketData,
            'message' => 'Booking confirmed with quantum-secure protection',
        ];
    }
    
    /**
     * Get quantum service health status
     */
    public function getQuantumStatus(): array
    {
        return $this->quantum->healthCheck();
    }
}
