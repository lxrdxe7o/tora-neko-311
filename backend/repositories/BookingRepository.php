<?php
/**
 * =============================================================================
 * Booking Repository
 * =============================================================================
 * Data access layer for booking operations.
 * Handles storage of quantum-secured booking records.
 * =============================================================================
 */

declare(strict_types=1);

namespace QuantumAirline\Repositories;

use QuantumAirline\Core\Database;

class BookingRepository
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new booking record
     * 
     * @param array $data Booking data including quantum security fields
     * @return int The created booking ID
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO bookings (
                user_id,
                seat_id,
                flight_id,
                qrng_booking_ref,
                passenger_name,
                encrypted_passport_data,
                kyber_encapsulated_key,
                encryption_nonce,
                pqc_signature,
                pqc_public_key,
                ticket_data_hash,
                ticket_data_json,
                mock_mode,
                signature_algorithm,
                encryption_algorithm
            ) VALUES (
                :user_id,
                :seat_id,
                :flight_id,
                :qrng_booking_ref,
                :passenger_name,
                :encrypted_passport_data,
                :kyber_encapsulated_key,
                :encryption_nonce,
                :pqc_signature,
                :pqc_public_key,
                :ticket_data_hash,
                :ticket_data_json,
                :mock_mode,
                :signature_algorithm,
                :encryption_algorithm
            )
            RETURNING id
        ";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':seat_id' => $data['seat_id'],
            ':flight_id' => $data['flight_id'],
            ':qrng_booking_ref' => $data['qrng_booking_ref'],
            ':passenger_name' => $data['passenger_name'],
            ':encrypted_passport_data' => $data['encrypted_passport_data'],
            ':kyber_encapsulated_key' => $data['kyber_encapsulated_key'],
            ':encryption_nonce' => $data['encryption_nonce'],
            ':pqc_signature' => $data['pqc_signature'],
            ':pqc_public_key' => $data['pqc_public_key'],
            ':ticket_data_hash' => $data['ticket_data_hash'],
            ':ticket_data_json' => $data['ticket_data_json'] ?? null,
            ':mock_mode' => $data['mock_mode'] ? 'true' : 'false',
            ':signature_algorithm' => $data['signature_algorithm'] ?? 'Dilithium3',
            ':encryption_algorithm' => $data['encryption_algorithm'] ?? 'Kyber512-AES256GCM',
        ]);
        
        $result = $stmt->fetch();
        return (int) $result['id'];
    }
    
    /**
     * Find a booking by its quantum reference ID
     * 
     * @param string $bookingRef The QRNG-generated booking reference
     * @return array|null Booking data or null if not found
     */
    public function findByReference(string $bookingRef): ?array
    {
        $sql = "
            SELECT 
                b.*,
                s.row_number,
                s.col_letter,
                s.class AS seat_class,
                CONCAT(s.row_number, s.col_letter) AS seat_label,
                f.flight_number,
                f.origin,
                f.destination,
                f.departure_time,
                f.arrival_time,
                f.price,
                f.aircraft_type,
                u.email AS user_email,
                u.name AS user_name
            FROM bookings b
            JOIN seats s ON b.seat_id = s.id
            JOIN flights f ON b.flight_id = f.id
            JOIN users u ON b.user_id = u.id
            WHERE b.qrng_booking_ref = ?
        ";
        
        return $this->db->fetchOne($sql, [$bookingRef]);
    }
    
    /**
     * Find a booking by ID
     * 
     * @param int $id Booking ID
     * @return array|null Booking data or null if not found
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                b.*,
                s.row_number,
                s.col_letter,
                s.class AS seat_class,
                CONCAT(s.row_number, s.col_letter) AS seat_label,
                f.flight_number,
                f.origin,
                f.destination,
                f.departure_time,
                f.arrival_time,
                f.price
            FROM bookings b
            JOIN seats s ON b.seat_id = s.id
            JOIN flights f ON b.flight_id = f.id
            WHERE b.id = ?
        ";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Get all bookings for a user
     * 
     * @param int $userId User ID
     * @return array List of bookings
     */
    public function findByUserId(int $userId): array
    {
        $sql = "
            SELECT 
                b.id,
                b.qrng_booking_ref,
                b.passenger_name,
                b.created_at,
                b.mock_mode,
                CONCAT(s.row_number, s.col_letter) AS seat_label,
                s.class AS seat_class,
                f.flight_number,
                f.origin,
                f.destination,
                f.departure_time,
                f.price
            FROM bookings b
            JOIN seats s ON b.seat_id = s.id
            JOIN flights f ON b.flight_id = f.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
        ";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    /**
     * Check if a booking reference already exists
     * 
     * @param string $bookingRef Booking reference to check
     * @return bool True if exists
     */
    public function referenceExists(string $bookingRef): bool
    {
        $sql = "SELECT 1 FROM bookings WHERE qrng_booking_ref = ?";
        $result = $this->db->fetchOne($sql, [$bookingRef]);
        return $result !== null;
    }
    
    /**
     * Get booking count statistics
     * 
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_bookings,
                COUNT(*) FILTER (WHERE mock_mode = TRUE) as mock_mode_bookings,
                COUNT(*) FILTER (WHERE mock_mode = FALSE) as real_pqc_bookings,
                COUNT(DISTINCT flight_id) as flights_with_bookings,
                COUNT(DISTINCT user_id) as users_with_bookings
            FROM bookings
        ";
        
        return $this->db->fetchOne($sql) ?? [];
    }
}
