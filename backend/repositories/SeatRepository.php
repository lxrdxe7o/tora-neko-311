<?php
/**
 * =============================================================================
 * Seat Repository
 * =============================================================================
 * Data access layer for seat operations with row-level locking support
 * for preventing double-booking.
 * 
 * Key Feature: SELECT ... FOR UPDATE
 *   This acquires an exclusive lock on the seat row, preventing other
 *   transactions from modifying it until the current transaction completes.
 *   This is the core mechanism for preventing race conditions in bookings.
 * =============================================================================
 */

declare(strict_types=1);

namespace QuantumAirline\Repositories;

use QuantumAirline\Core\Database;
use RuntimeException;

class SeatRepository
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all seats for a flight
     * 
     * @param int $flightId Flight ID
     * @return array List of seats grouped by class
     */
    public function findByFlightId(int $flightId): array
    {
        $sql = "
            SELECT 
                s.id,
                s.row_number,
                s.col_letter,
                s.class,
                s.is_booked,
                CONCAT(s.row_number, s.col_letter) AS seat_label
            FROM seats s
            WHERE s.flight_id = ?
            ORDER BY 
                CASE s.class 
                    WHEN 'first' THEN 1 
                    WHEN 'business' THEN 2 
                    ELSE 3 
                END,
                s.row_number,
                s.col_letter
        ";
        
        return $this->db->fetchAll($sql, [$flightId]);
    }
    
    /**
     * Get seat map organized for display
     * 
     * @param int $flightId Flight ID
     * @return array Seat map organized by row
     */
    public function getSeatMap(int $flightId): array
    {
        $seats = $this->findByFlightId($flightId);
        
        $seatMap = [];
        foreach ($seats as $seat) {
            $row = (int) $seat['row_number'];
            if (!isset($seatMap[$row])) {
                $seatMap[$row] = [
                    'row' => $row,
                    'class' => $seat['class'],
                    'seats' => [],
                ];
            }
            $seatMap[$row]['seats'][$seat['col_letter']] = [
                'id' => (int) $seat['id'],
                'col' => $seat['col_letter'],
                'label' => $seat['seat_label'],
                'is_booked' => (bool) $seat['is_booked'],
            ];
        }
        
        // Ensure seats are in column order (A-F)
        foreach ($seatMap as &$row) {
            ksort($row['seats']);
            $row['seats'] = array_values($row['seats']);
        }
        
        return array_values($seatMap);
    }
    
    /**
     * Get a seat by ID
     * 
     * @param int $id Seat ID
     * @return array|null Seat data or null if not found
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                s.*,
                f.flight_number,
                f.origin,
                f.destination,
                f.departure_time,
                f.price,
                CONCAT(s.row_number, s.col_letter) AS seat_label
            FROM seats s
            JOIN flights f ON s.flight_id = f.id
            WHERE s.id = ?
        ";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Lock a seat for booking (SELECT ... FOR UPDATE)
     * 
     * CRITICAL: This must be called within a transaction!
     * The lock is held until the transaction commits or rolls back.
     * 
     * @param int $seatId Seat ID to lock
     * @return array Locked seat data
     * @throws RuntimeException If seat not found or already booked
     */
    public function lockForBooking(int $seatId): array
    {
        // Ensure we're in a transaction
        if (!$this->db->inTransaction()) {
            throw new RuntimeException(
                "lockForBooking must be called within a transaction"
            );
        }
        
        // SELECT ... FOR UPDATE acquires an exclusive row lock
        // NOWAIT makes it fail immediately if the row is already locked
        // (prevents long waits in high-concurrency scenarios)
        $sql = "
            SELECT 
                s.*,
                f.flight_number,
                f.origin,
                f.destination,
                f.departure_time,
                f.price,
                CONCAT(s.row_number, s.col_letter) AS seat_label
            FROM seats s
            JOIN flights f ON s.flight_id = f.id
            WHERE s.id = ?
            FOR UPDATE OF s NOWAIT
        ";
        
        try {
            $seat = $this->db->fetchOne($sql, [$seatId]);
        } catch (\PDOException $e) {
            // Check if it's a lock failure (55P03 = lock_not_available)
            if (strpos($e->getMessage(), '55P03') !== false) {
                throw new RuntimeException(
                    "Seat is currently being booked by another user. Please try again.",
                    409
                );
            }
            throw $e;
        }
        
        if ($seat === null) {
            throw new RuntimeException("Seat not found", 404);
        }
        
        if ($seat['is_booked']) {
            throw new RuntimeException(
                "Seat {$seat['seat_label']} is already booked",
                409
            );
        }
        
        return $seat;
    }
    
    /**
     * Mark a seat as booked
     * 
     * Uses optimistic locking with lock_version to prevent race conditions
     * even if FOR UPDATE somehow fails.
     * 
     * @param int $seatId Seat ID
     * @param int $expectedVersion Expected lock_version (optimistic locking)
     * @return bool True if update succeeded
     * @throws RuntimeException If optimistic lock fails
     */
    public function markAsBooked(int $seatId, int $expectedVersion): bool
    {
        $sql = "
            UPDATE seats 
            SET 
                is_booked = TRUE,
                lock_version = lock_version + 1,
                updated_at = NOW()
            WHERE id = ? 
              AND lock_version = ?
              AND is_booked = FALSE
        ";
        
        $stmt = $this->db->query($sql, [$seatId, $expectedVersion]);
        $rowsAffected = $stmt->rowCount();
        
        if ($rowsAffected === 0) {
            throw new RuntimeException(
                "Seat booking failed - concurrent modification detected",
                409
            );
        }
        
        return true;
    }
    
    /**
     * Get available seats count for a flight
     * 
     * @param int $flightId Flight ID
     * @return int Number of available seats
     */
    public function getAvailableCount(int $flightId): int
    {
        $sql = "
            SELECT COUNT(*) as count 
            FROM seats 
            WHERE flight_id = ? AND is_booked = FALSE
        ";
        
        $result = $this->db->fetchOne($sql, [$flightId]);
        return (int) ($result['count'] ?? 0);
    }
    
    /**
     * Check if a specific seat is available
     * 
     * @param int $seatId Seat ID
     * @return bool True if seat is available
     */
    public function isAvailable(int $seatId): bool
    {
        $sql = "SELECT is_booked FROM seats WHERE id = ?";
        $result = $this->db->fetchOne($sql, [$seatId]);
        
        if ($result === null) {
            return false;
        }
        
        return !$result['is_booked'];
    }
}
