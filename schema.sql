-- =============================================================================
-- Quantum-Secure Airline Booking System - Database Schema
-- PostgreSQL 14+ Required
-- =============================================================================

-- Drop existing tables (for clean setup)
DROP TABLE IF EXISTS bookings CASCADE;
DROP TABLE IF EXISTS seats CASCADE;
DROP TABLE IF EXISTS flights CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- =============================================================================
-- USERS TABLE
-- Stores user information including their Post-Quantum Cryptography public key
-- =============================================================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    -- Dilithium3 public key stored as hex string
    -- Used for verifying signatures on tickets issued to this user
    pqc_public_key TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Index for email lookups (login scenarios)
CREATE INDEX idx_users_email ON users(email);

COMMENT ON TABLE users IS 'User accounts with optional PQC public keys for signature verification';
COMMENT ON COLUMN users.pqc_public_key IS 'Dilithium3 public key (hex) for verifying user-specific signatures';

-- =============================================================================
-- FLIGHTS TABLE
-- Standard flight information
-- =============================================================================
CREATE TABLE flights (
    id SERIAL PRIMARY KEY,
    flight_number VARCHAR(10) NOT NULL,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    departure_time TIMESTAMP WITH TIME ZONE NOT NULL,
    arrival_time TIMESTAMP WITH TIME ZONE NOT NULL,
    price DECIMAL(10, 2) NOT NULL CHECK (price >= 0),
    aircraft_type VARCHAR(50) DEFAULT 'Quantum Jet Q-100',
    total_rows INTEGER DEFAULT 10,
    seats_per_row INTEGER DEFAULT 6,  -- A-F configuration
    status VARCHAR(20) DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'boarding', 'departed', 'arrived', 'cancelled')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Index for searching flights
CREATE INDEX idx_flights_departure ON flights(departure_time);
CREATE INDEX idx_flights_route ON flights(origin, destination);
CREATE INDEX idx_flights_status ON flights(status);

COMMENT ON TABLE flights IS 'Available flights with scheduling and pricing information';

-- =============================================================================
-- SEATS TABLE
-- Individual seats with booking status and optimistic locking
-- =============================================================================
CREATE TABLE seats (
    id SERIAL PRIMARY KEY,
    flight_id INTEGER NOT NULL REFERENCES flights(id) ON DELETE CASCADE,
    row_number INTEGER NOT NULL CHECK (row_number > 0),
    col_letter CHAR(1) NOT NULL CHECK (col_letter IN ('A', 'B', 'C', 'D', 'E', 'F')),
    class VARCHAR(20) DEFAULT 'economy' CHECK (class IN ('economy', 'business', 'first')),
    is_booked BOOLEAN DEFAULT FALSE,
    -- Optimistic locking version - incremented on each update
    -- Provides additional safety beyond SELECT ... FOR UPDATE
    lock_version INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- Each seat must be unique per flight
    UNIQUE(flight_id, row_number, col_letter)
);

-- Index for finding available seats on a flight
CREATE INDEX idx_seats_flight_available ON seats(flight_id, is_booked);
CREATE INDEX idx_seats_flight_id ON seats(flight_id);

COMMENT ON TABLE seats IS 'Individual seats with booking status and concurrency control';
COMMENT ON COLUMN seats.lock_version IS 'Optimistic locking version for concurrency control';

-- =============================================================================
-- BOOKINGS TABLE
-- Stores quantum-secured booking records with encrypted data and signatures
-- =============================================================================
CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    seat_id INTEGER NOT NULL REFERENCES seats(id) ON DELETE RESTRICT,
    flight_id INTEGER NOT NULL REFERENCES flights(id) ON DELETE RESTRICT,
    
    -- Quantum Random Number Generator booking reference
    -- Generated using simulated Hadamard gate measurements
    qrng_booking_ref VARCHAR(16) UNIQUE NOT NULL,
    
    -- Passenger information
    passenger_name VARCHAR(255) NOT NULL,
    
    -- ==========================================================================
    -- KYBER512 HYBRID ENCRYPTION (Quantum-Safe Confidentiality)
    -- ==========================================================================
    -- Passport data encrypted with AES-256-GCM
    -- The AES key was exchanged using Kyber512 KEM
    encrypted_passport_data TEXT NOT NULL,
    
    -- Kyber512 encapsulated key (allows decryption with private key)
    kyber_encapsulated_key TEXT NOT NULL,
    
    -- AES-256-GCM nonce/IV for decryption
    encryption_nonce TEXT NOT NULL,
    
    -- ==========================================================================
    -- DILITHIUM3 DIGITAL SIGNATURE (Quantum-Safe Authenticity)
    -- ==========================================================================
    -- The signature covers the ticket data (booking_ref, flight, seat, passenger)
    -- Anyone can verify this signature using the public key
    pqc_signature TEXT NOT NULL,
    
    -- Public key used for this specific signature (for verification)
    pqc_public_key TEXT NOT NULL,
    
    -- SHA256 hash of the signed data (for quick integrity check)
    ticket_data_hash TEXT NOT NULL,
    
    -- ==========================================================================
    -- METADATA
    -- ==========================================================================
    -- Indicates if mock mode was used (for transparency)
    mock_mode BOOLEAN DEFAULT FALSE,
    
    -- Algorithms used (for future-proofing)
    signature_algorithm VARCHAR(50) DEFAULT 'Dilithium3',
    encryption_algorithm VARCHAR(50) DEFAULT 'Kyber512-AES256GCM',
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- Each seat can only be booked once
    UNIQUE(seat_id)
);

-- Index for booking reference lookups (ticket verification)
CREATE INDEX idx_bookings_ref ON bookings(qrng_booking_ref);
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_flight ON bookings(flight_id);

COMMENT ON TABLE bookings IS 'Quantum-secured booking records with encrypted passport data and digital signatures';
COMMENT ON COLUMN bookings.qrng_booking_ref IS 'Booking reference generated using quantum random number simulation';
COMMENT ON COLUMN bookings.encrypted_passport_data IS 'AES-256-GCM encrypted passport number';
COMMENT ON COLUMN bookings.kyber_encapsulated_key IS 'Kyber512 encapsulated key for AES key recovery';
COMMENT ON COLUMN bookings.pqc_signature IS 'Dilithium3 signature over ticket data';

-- =============================================================================
-- FUNCTIONS
-- =============================================================================

-- Function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Triggers for updated_at
CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_seats_updated_at
    BEFORE UPDATE ON seats
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Demo user (simulates logged-in user)
INSERT INTO users (id, email, name) VALUES 
    (1, 'demo@quantum-air.com', 'Demo User');

-- Reset sequence after explicit ID insert
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));

-- Sample flights
INSERT INTO flights (flight_number, origin, destination, departure_time, arrival_time, price, aircraft_type) VALUES 
    ('QA-101', 'New York (JFK)', 'London (LHR)', 
     '2025-03-15 08:00:00+00', '2025-03-15 20:00:00+00', 899.99, 'Quantum Jet Q-100'),
    ('QA-202', 'Tokyo (NRT)', 'Singapore (SIN)', 
     '2025-03-16 14:30:00+00', '2025-03-16 21:00:00+00', 650.00, 'Quantum Jet Q-200'),
    ('QA-303', 'Dubai (DXB)', 'Sydney (SYD)', 
     '2025-03-17 22:00:00+00', '2025-03-18 18:30:00+00', 1250.00, 'Quantum Jet Q-300'),
    ('QA-404', 'Los Angeles (LAX)', 'Paris (CDG)', 
     '2025-03-18 10:00:00+00', '2025-03-19 06:30:00+00', 1100.00, 'Quantum Jet Q-100'),
    ('QA-505', 'Frankfurt (FRA)', 'Hong Kong (HKG)', 
     '2025-03-19 13:00:00+00', '2025-03-20 07:00:00+00', 950.00, 'Quantum Jet Q-200');

-- Generate seats for each flight
-- Rows 1-2: First Class, Rows 3-4: Business, Rows 5-10: Economy
DO $$
DECLARE
    f_id INTEGER;
    r INTEGER;
    c CHAR(1);
    seat_class VARCHAR(20);
BEGIN
    FOR f_id IN SELECT id FROM flights LOOP
        FOR r IN 1..10 LOOP
            -- Determine class based on row
            IF r <= 2 THEN
                seat_class := 'first';
            ELSIF r <= 4 THEN
                seat_class := 'business';
            ELSE
                seat_class := 'economy';
            END IF;
            
            -- Create seats A through F for each row
            FOREACH c IN ARRAY ARRAY['A', 'B', 'C', 'D', 'E', 'F'] LOOP
                INSERT INTO seats (flight_id, row_number, col_letter, class)
                VALUES (f_id, r, c, seat_class);
            END LOOP;
        END LOOP;
    END LOOP;
END $$;

-- Pre-book some seats to make the demo more realistic
-- (Some random seats are already taken)
UPDATE seats SET is_booked = TRUE WHERE id IN (
    -- Flight 1: Some scattered bookings
    (SELECT id FROM seats WHERE flight_id = 1 AND row_number = 1 AND col_letter = 'A'),
    (SELECT id FROM seats WHERE flight_id = 1 AND row_number = 1 AND col_letter = 'B'),
    (SELECT id FROM seats WHERE flight_id = 1 AND row_number = 3 AND col_letter = 'C'),
    (SELECT id FROM seats WHERE flight_id = 1 AND row_number = 5 AND col_letter = 'D'),
    (SELECT id FROM seats WHERE flight_id = 1 AND row_number = 7 AND col_letter = 'A'),
    (SELECT id FROM seats WHERE flight_id = 1 AND row_number = 7 AND col_letter = 'F'),
    -- Flight 2: Different pattern
    (SELECT id FROM seats WHERE flight_id = 2 AND row_number = 2 AND col_letter = 'C'),
    (SELECT id FROM seats WHERE flight_id = 2 AND row_number = 4 AND col_letter = 'B'),
    (SELECT id FROM seats WHERE flight_id = 2 AND row_number = 6 AND col_letter = 'E'),
    -- Flight 3: More bookings
    (SELECT id FROM seats WHERE flight_id = 3 AND row_number = 1 AND col_letter = 'A'),
    (SELECT id FROM seats WHERE flight_id = 3 AND row_number = 1 AND col_letter = 'F'),
    (SELECT id FROM seats WHERE flight_id = 3 AND row_number = 2 AND col_letter = 'A'),
    (SELECT id FROM seats WHERE flight_id = 3 AND row_number = 2 AND col_letter = 'F'),
    (SELECT id FROM seats WHERE flight_id = 3 AND row_number = 8 AND col_letter = 'C'),
    (SELECT id FROM seats WHERE flight_id = 3 AND row_number = 8 AND col_letter = 'D')
);

-- =============================================================================
-- VERIFICATION QUERIES (for testing)
-- =============================================================================

-- View flight summary with available seats
-- SELECT 
--     f.flight_number,
--     f.origin || ' -> ' || f.destination AS route,
--     f.departure_time,
--     COUNT(*) FILTER (WHERE NOT s.is_booked) AS available_seats,
--     COUNT(*) AS total_seats
-- FROM flights f
-- JOIN seats s ON f.id = s.flight_id
-- GROUP BY f.id
-- ORDER BY f.departure_time;

-- View seat map for a flight
-- SELECT 
--     row_number,
--     STRING_AGG(
--         CASE WHEN is_booked THEN 'X' ELSE col_letter END,
--         ' ' ORDER BY col_letter
--     ) AS seats
-- FROM seats
-- WHERE flight_id = 1
-- GROUP BY row_number
-- ORDER BY row_number;
