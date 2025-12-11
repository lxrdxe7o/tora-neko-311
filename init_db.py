import mysql.connector
import sys
import time

def init_db():
    print("Connecting to MariaDB...")
    
    # Retry connection logic
    max_retries = 5
    conn = None
    for i in range(max_retries):
        try:
            conn = mysql.connector.connect(
                host="localhost",
                user="root",
                password=""
            )
            break
        except mysql.connector.Error as err:
            print(f"Connection failed (attempt {i+1}/{max_retries}): {err}")
            time.sleep(2)
            
    if not conn:
        print("Could not connect to MariaDB. Is XAMPP/MySQL running?")
        sys.exit(1)

    cursor = conn.cursor()

    try:
        print("Creating database 'airline_db'...")
        cursor.execute("DROP DATABASE IF EXISTS airline_db")
        cursor.execute("CREATE DATABASE airline_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")
        cursor.execute("USE airline_db")

        print("Creating table 'flights'...")
        cursor.execute("""
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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_flights_departure (departure_time),
                INDEX idx_flights_route (origin, destination)
            ) ENGINE=InnoDB
        """)

        print("Creating table 'seats'...")
        cursor.execute("""
            CREATE TABLE seats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                flight_id INT NOT NULL,
                row_num VARCHAR(2) NOT NULL,
                col_num VARCHAR(1) NOT NULL,
                class ENUM('economy', 'business', 'first') DEFAULT 'economy',
                is_booked TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_seat (flight_id, row_num, col_num),
                CONSTRAINT fk_seat_flight FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE,
                INDEX idx_seats_flight_available (flight_id, is_booked)
            ) ENGINE=InnoDB
        """)

        print("Creating table 'bookings'...")
        cursor.execute("""
            CREATE TABLE bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                seat_id INT NOT NULL,
                flight_id INT NOT NULL,
                pqc_ref VARCHAR(100) NOT NULL UNIQUE,
                passenger_name VARCHAR(255) NOT NULL,
                kyber_capsule TEXT NOT NULL,
                passport_enc TEXT NOT NULL,
                encryption_nonce TEXT,
                pqc_signature TEXT NOT NULL,
                ticket_data_hash TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_booking (seat_id),
                CONSTRAINT fk_booking_seat FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE RESTRICT,
                CONSTRAINT fk_booking_flight FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE RESTRICT,
                INDEX idx_bookings_ref (pqc_ref),
                INDEX idx_bookings_flight (flight_id)
            ) ENGINE=InnoDB
        """)

        print("Seeding flights...")
        flights_data = [
            ('QA-101', 'New York (JFK)', 'London (LHR)', '2025-03-15 08:00:00', '2025-03-15 20:00:00', 899.99, 'Quantum Jet Q-100'),
            ('QA-202', 'Tokyo (NRT)', 'Singapore (SIN)', '2025-03-16 14:30:00', '2025-03-16 21:00:00', 650.00, 'Quantum Jet Q-200'),
            ('QA-303', 'Dubai (DXB)', 'Sydney (SYD)', '2025-03-17 22:00:00', '2025-03-18 18:30:00', 1250.00, 'Quantum Jet Q-300'),
            ('QA-404', 'Los Angeles (LAX)', 'Paris (CDG)', '2025-03-18 10:00:00', '2025-03-19 06:30:00', 1100.00, 'Quantum Jet Q-100'),
            ('QA-505', 'Frankfurt (FRA)', 'Hong Kong (HKG)', '2025-03-19 13:00:00', '2025-03-20 07:00:00', 950.00, 'Quantum Jet Q-200')
        ]
        
        cursor.executemany("""
            INSERT INTO flights (flight_number, origin, destination, departure_time, arrival_time, price, aircraft_type) 
            VALUES (%s, %s, %s, %s, %s, %s, %s)
        """, flights_data)
        conn.commit()

        print("Seeding seats...")
        cursor.execute("SELECT id FROM flights")
        flight_ids = [row[0] for row in cursor.fetchall()]

        seat_data = []
        for f_id in flight_ids:
            for r in range(1, 11):
                # Determine class
                if r <= 2:
                    seat_class = 'first'
                elif r <= 4:
                    seat_class = 'business'
                else:
                    seat_class = 'economy'
                
                for c in ['A', 'B', 'C', 'D', 'E', 'F']:
                    seat_data.append((f_id, str(r), c, seat_class))
        
        cursor.executemany("""
            INSERT INTO seats (flight_id, row_num, col_num, class) VALUES (%s, %s, %s, %s)
        """, seat_data)
        conn.commit()

        print("Booking sample seats...")
        # Pre-book a few seats
        # Flight 1
        conn.cursor().execute("UPDATE seats SET is_booked = 1 WHERE flight_id = 1 AND row_num = '1' AND col_num = 'A'")
        conn.cursor().execute("UPDATE seats SET is_booked = 1 WHERE flight_id = 1 AND row_num = '1' AND col_num = 'B'")
        conn.commit()

        print("Database initialization complete!")

    except mysql.connector.Error as err:
        print(f"Database Error: {err}")
        sys.exit(1)
    finally:
        cursor.close()
        conn.close()

if __name__ == "__main__":
    init_db()
