<?php
/**
 * =============================================================================
 * Flight Repository
 * =============================================================================
 * Data access layer for flight operations.
 * =============================================================================
 */

declare(strict_types=1);

namespace QuantumAirline\Repositories;

use QuantumAirline\Core\Database;

class FlightRepository
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all available flights (scheduled, not departed)
     * 
     * @return array List of flights with available seat counts
     */
    public function findAllAvailable(): array
    {
        $sql = "
            SELECT 
                f.id,
                f.flight_number,
                f.origin,
                f.destination,
                f.departure_time,
                f.arrival_time,
                f.price,
                f.aircraft_type,
                f.status,
                COUNT(s.id) FILTER (WHERE NOT s.is_booked) AS available_seats,
                COUNT(s.id) AS total_seats
            FROM flights f
            LEFT JOIN seats s ON f.id = s.flight_id
            WHERE f.status = 'scheduled'
              AND f.departure_time > NOW()
            GROUP BY f.id
            ORDER BY f.departure_time ASC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get a flight by ID
     * 
     * @param int $id Flight ID
     * @return array|null Flight data or null if not found
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                f.*,
                COUNT(s.id) FILTER (WHERE NOT s.is_booked) AS available_seats,
                COUNT(s.id) AS total_seats
            FROM flights f
            LEFT JOIN seats s ON f.id = s.flight_id
            WHERE f.id = ?
            GROUP BY f.id
        ";
        
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Get a flight by flight number
     * 
     * @param string $flightNumber Flight number (e.g., 'QA-101')
     * @return array|null Flight data or null if not found
     */
    public function findByFlightNumber(string $flightNumber): ?array
    {
        $sql = "
            SELECT 
                f.*,
                COUNT(s.id) FILTER (WHERE NOT s.is_booked) AS available_seats,
                COUNT(s.id) AS total_seats
            FROM flights f
            LEFT JOIN seats s ON f.id = s.flight_id
            WHERE f.flight_number = ?
            GROUP BY f.id
        ";
        
        return $this->db->fetchOne($sql, [$flightNumber]);
    }
    
    /**
     * Search flights by route
     * 
     * @param string $origin Origin airport/city
     * @param string $destination Destination airport/city
     * @return array List of matching flights
     */
    public function searchByRoute(string $origin, string $destination): array
    {
        $sql = "
            SELECT 
                f.*,
                COUNT(s.id) FILTER (WHERE NOT s.is_booked) AS available_seats,
                COUNT(s.id) AS total_seats
            FROM flights f
            LEFT JOIN seats s ON f.id = s.flight_id
            WHERE f.origin ILIKE ?
              AND f.destination ILIKE ?
              AND f.status = 'scheduled'
              AND f.departure_time > NOW()
            GROUP BY f.id
            ORDER BY f.departure_time ASC
        ";
        
        return $this->db->fetchAll($sql, ["%{$origin}%", "%{$destination}%"]);
    }
    
    /**
     * Get flight summary for display
     * 
     * @param int $id Flight ID
     * @return array|null Flight summary
     */
    public function getFlightSummary(int $id): ?array
    {
        $flight = $this->findById($id);
        
        if ($flight === null) {
            return null;
        }
        
        return [
            'id' => (int) $flight['id'],
            'flight_number' => $flight['flight_number'],
            'route' => $flight['origin'] . ' -> ' . $flight['destination'],
            'origin' => $flight['origin'],
            'destination' => $flight['destination'],
            'departure' => $flight['departure_time'],
            'arrival' => $flight['arrival_time'],
            'price' => (float) $flight['price'],
            'aircraft' => $flight['aircraft_type'],
            'available_seats' => (int) $flight['available_seats'],
            'total_seats' => (int) $flight['total_seats'],
        ];
    }
}
