-- =============================================================================
-- Quantum-Secure Airline Booking System - MariaDB Schema
-- MariaDB 10.x+ Compatible (XAMPP)
-- Import via phpMyAdmin or: mysql -u root < schema_mariadb.sql
-- =============================================================================

-- Create database
CREATE DATABASE IF NOT EXISTS airline_db
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE airline_db;

-- Drop existing tables (for clean setup)
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS seats;
DROP TABLE IF EXISTS flights;

-- =============================================================================
-- FLIGHTS TABLE
-- Standard flight information
-- =============================================================================
CREATE TABLE flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_number VARCHAR(10) NOT NULL,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    aircraft_type VARCHAR(50) DEFAULT 'Quantum Jet Q-100',
    total_rows INT DEFAULT 10,
    seats_per_row INT DEFAULT 6,
    status ENUM('scheduled', 'boarding', 'departed', 'arrived', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Index for searching flights
CREATE INDEX idx_flights_departure ON flights(departure_time);
CREATE INDEX idx_flights_route ON flights(origin, destination);

-- =============================================================================
-- SEATS TABLE
-- Individual seats with booking status
-- Engine: InnoDB (Essential for row-level locking with FOR UPDATE)
-- =============================================================================
CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_id INT NOT NULL,
    row_num VARCHAR(2) NOT NULL,
    col_num VARCHAR(1) NOT NULL,
    class ENUM('economy', 'business', 'first') DEFAULT 'economy',
    is_booked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Each seat must be unique per flight
    UNIQUE KEY unique_seat (flight_id, row_num, col_num),
    
    -- Foreign key to flights
    CONSTRAINT fk_seat_flight FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Index for finding available seats on a flight
CREATE INDEX idx_seats_flight_available ON seats(flight_id, is_booked);

-- =============================================================================
-- BOOKINGS TABLE
-- Stores quantum-secured booking records with encrypted data and signatures
-- All cryptographic fields are TEXT to accommodate large PQC data
-- =============================================================================
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seat_id INT NOT NULL,
    flight_id INT NOT NULL,
    
    -- Quantum Random Number Generator booking reference
    pqc_ref VARCHAR(100) NOT NULL UNIQUE,
    
    -- Passenger information
    passenger_name VARCHAR(255) NOT NULL,
    
    -- KYBER512 HYBRID ENCRYPTION (Quantum-Safe Confidentiality)
    -- Must be TEXT - Kyber capsules are approximately 1KB
    kyber_capsule TEXT NOT NULL,
    
    -- AES-encrypted passport data
    passport_enc TEXT NOT NULL,
    
    -- AES nonce/IV for decryption
    encryption_nonce TEXT,
    
    -- DILITHIUM3 DIGITAL SIGNATURE (Quantum-Safe Authenticity)
    -- Must be TEXT - Dilithium signatures are approximately 3KB
    pqc_signature TEXT NOT NULL,
    
    -- SHA256 hash of the signed data (for quick integrity check)
    ticket_data_hash TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Each seat can only be booked once
    UNIQUE KEY unique_booking (seat_id),
    
    -- Foreign keys
    CONSTRAINT fk_booking_seat FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE RESTRICT,
    CONSTRAINT fk_booking_flight FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Index for booking reference lookups (ticket verification)
CREATE INDEX idx_bookings_ref ON bookings(pqc_ref);
CREATE INDEX idx_bookings_flight ON bookings(flight_id);

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Sample flights
INSERT INTO flights (flight_number, origin, destination, departure_time, arrival_time, price, aircraft_type) VALUES 
    ('QA-101', 'New York (JFK)', 'London (LHR)', 
     '2025-03-15 08:00:00', '2025-03-15 20:00:00', 899.99, 'Quantum Jet Q-100'),
    ('QA-202', 'Tokyo (NRT)', 'Singapore (SIN)', 
     '2025-03-16 14:30:00', '2025-03-16 21:00:00', 650.00, 'Quantum Jet Q-200'),
    ('QA-303', 'Dubai (DXB)', 'Sydney (SYD)', 
     '2025-03-17 22:00:00', '2025-03-18 18:30:00', 1250.00, 'Quantum Jet Q-300'),
    ('QA-404', 'Los Angeles (LAX)', 'Paris (CDG)', 
     '2025-03-18 10:00:00', '2025-03-19 06:30:00', 1100.00, 'Quantum Jet Q-100'),
    ('QA-505', 'Frankfurt (FRA)', 'Hong Kong (HKG)', 
     '2025-03-19 13:00:00', '2025-03-20 07:00:00', 950.00, 'Quantum Jet Q-200');

-- Generate seats for each flight using a procedure
DELIMITER //

CREATE PROCEDURE generate_seats()
BEGIN
    DECLARE f_id INT;
    DECLARE r INT;
    DECLARE c VARCHAR(1);
    DECLARE seat_class VARCHAR(10);
    DECLARE done INT DEFAULT FALSE;
    DECLARE flight_cursor CURSOR FOR SELECT id FROM flights;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN flight_cursor;
    
    flight_loop: LOOP
        FETCH flight_cursor INTO f_id;
        IF done THEN
            LEAVE flight_loop;
        END IF;
        
        SET r = 1;
        WHILE r <= 10 DO
            -- Determine class based on row
            IF r <= 2 THEN
                SET seat_class = 'first';
            ELSEIF r <= 4 THEN
                SET seat_class = 'business';
            ELSE
                SET seat_class = 'economy';
            END IF;
            
            -- Create seats A through F for each row
            INSERT INTO seats (flight_id, row_num, col_num, class) VALUES (f_id, r, 'A', seat_class);
            INSERT INTO seats (flight_id, row_num, col_num, class) VALUES (f_id, r, 'B', seat_class);
            INSERT INTO seats (flight_id, row_num, col_num, class) VALUES (f_id, r, 'C', seat_class);
            INSERT INTO seats (flight_id, row_num, col_num, class) VALUES (f_id, r, 'D', seat_class);
            INSERT INTO seats (flight_id, row_num, col_num, class) VALUES (f_id, r, 'E', seat_class);
            INSERT INTO seats (flight_id, row_num, col_num, class) VALUES (f_id, r, 'F', seat_class);
            
            SET r = r + 1;
        END WHILE;
    END LOOP;
    
    CLOSE flight_cursor;
END //

DELIMITER ;

-- Run the procedure to generate seats
CALL generate_seats();

-- Drop the procedure after use
DROP PROCEDURE generate_seats;

-- Pre-book some seats to make the demo more realistic
UPDATE seats SET is_booked = 1 WHERE flight_id = 1 AND row_num = '1' AND col_num = 'A';
UPDATE seats SET is_booked = 1 WHERE flight_id = 1 AND row_num = '1' AND col_num = 'B';
UPDATE seats SET is_booked = 1 WHERE flight_id = 1 AND row_num = '3' AND col_num = 'C';
UPDATE seats SET is_booked = 1 WHERE flight_id = 1 AND row_num = '5' AND col_num = 'D';
UPDATE seats SET is_booked = 1 WHERE flight_id = 1 AND row_num = '7' AND col_num = 'A';
UPDATE seats SET is_booked = 1 WHERE flight_id = 1 AND row_num = '7' AND col_num = 'F';

UPDATE seats SET is_booked = 1 WHERE flight_id = 2 AND row_num = '2' AND col_num = 'C';
UPDATE seats SET is_booked = 1 WHERE flight_id = 2 AND row_num = '4' AND col_num = 'B';
UPDATE seats SET is_booked = 1 WHERE flight_id = 2 AND row_num = '6' AND col_num = 'E';

UPDATE seats SET is_booked = 1 WHERE flight_id = 3 AND row_num = '1' AND col_num = 'A';
UPDATE seats SET is_booked = 1 WHERE flight_id = 3 AND row_num = '1' AND col_num = 'F';
UPDATE seats SET is_booked = 1 WHERE flight_id = 3 AND row_num = '2' AND col_num = 'A';
UPDATE seats SET is_booked = 1 WHERE flight_id = 3 AND row_num = '2' AND col_num = 'F';
UPDATE seats SET is_booked = 1 WHERE flight_id = 3 AND row_num = '8' AND col_num = 'C';
UPDATE seats SET is_booked = 1 WHERE flight_id = 3 AND row_num = '8' AND col_num = 'D';

-- =============================================================================
-- Verification query (uncomment to test)
-- =============================================================================
-- SELECT f.flight_number, f.origin, f.destination,
--        COUNT(*) AS total_seats,
--        SUM(CASE WHEN s.is_booked = 0 THEN 1 ELSE 0 END) AS available_seats
-- FROM flights f
-- JOIN seats s ON f.id = s.flight_id
-- GROUP BY f.id;
