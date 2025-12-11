#!/usr/bin/env python3
"""
=============================================================================
Split-Stack Quantum Booking System - Flask API Server
=============================================================================
Runs on port 5000, connects to XAMPP's MariaDB on port 3306.
Implements the "Quantum Trinity": Kyber, Dilithium, and QRNG simulation.
=============================================================================
"""

import os
import sys
import json
import secrets
import hashlib
import base64
from datetime import datetime
from flask import Flask, request, jsonify
from flask_cors import CORS

# Add quantum_service to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'quantum_service'))

# Try to import real quantum libraries, fall back to simulation
try:
    from cryptography.hazmat.primitives.ciphers.aead import AESGCM
    CRYPTO_AVAILABLE = True
except ImportError:
    CRYPTO_AVAILABLE = False
    print("Warning: cryptography library not available, using basic simulation")

# Database connector
try:
    import mysql.connector
    DB_AVAILABLE = True
except ImportError:
    DB_AVAILABLE = False
    print("ERROR: mysql-connector-python not installed. Run: pip install mysql-connector-python")

# =============================================================================
# Flask App Configuration
# =============================================================================
app = Flask(__name__)

# CORS Configuration - Allow Apache on port 80 to communicate with this server
CORS(app, origins=[
    "http://localhost",
    "http://localhost:80",
    "http://127.0.0.1",
    "http://127.0.0.1:80",
    "http://localhost:5500",  # VS Code Live Server
    "http://127.0.0.1:5500",
    "http://localhost:8080",  # Python http.server
    "http://127.0.0.1:8080"
])

# =============================================================================
# Database Configuration (XAMPP MariaDB)
# =============================================================================
DB_CONFIG = {
    'host': 'localhost',
    'port': 3306,
    'user': 'root',
    'password': '',  # XAMPP default has no password
    'database': 'airline_db',
    'autocommit': False  # We handle transactions manually
}


def get_db_connection():
    """Create a new database connection."""
    if not DB_AVAILABLE:
        raise RuntimeError("Database connector not available")
    return mysql.connector.connect(**DB_CONFIG)


# =============================================================================
# QUANTUM TRINITY: Simulation Functions
# =============================================================================

def generate_quantum_entropy() -> str:
    """
    Quantum Random Number Generator (QRNG) Simulation.
    Generates a cryptographically secure "Quantum" Booking Reference.
    Format: QREF-XXXX-XXXX-XXXX
    """
    # Use system entropy (cryptographically secure)
    random_bytes = secrets.token_bytes(12)
    hex_str = random_bytes.hex().upper()
    
    # Format as QREF-XXXX-XXXX-XXXX
    ref = f"QREF-{hex_str[:4]}-{hex_str[4:8]}-{hex_str[8:12]}"
    return ref


def kyber_encrypt(passport_data: str) -> dict:
    """
    Kyber-512 Key Encapsulation Mechanism (KEM) Simulation.
    In a real implementation, this would use liboqs Kyber512.
    Here we simulate with AES-256-GCM.
    
    Returns:
        dict with 'capsule' (simulated KEM ciphertext), 
        'encrypted_data', and 'nonce'
    """
    # Generate a random 256-bit AES key
    aes_key = secrets.token_bytes(32)
    
    # Generate a random nonce for AES-GCM
    nonce = secrets.token_bytes(12)
    
    if CRYPTO_AVAILABLE:
        # Real AES-GCM encryption
        aesgcm = AESGCM(aes_key)
        encrypted = aesgcm.encrypt(nonce, passport_data.encode('utf-8'), None)
        encrypted_hex = encrypted.hex()
    else:
        # Simple XOR simulation (NOT SECURE - demo only)
        data_bytes = passport_data.encode('utf-8')
        key_extended = (aes_key * ((len(data_bytes) // 32) + 1))[:len(data_bytes)]
        encrypted_bytes = bytes(a ^ b for a, b in zip(data_bytes, key_extended))
        encrypted_hex = encrypted_bytes.hex()
    
    # Simulate Kyber capsule (in reality this would be the KEM ciphertext)
    # Kyber512 ciphertexts are ~768 bytes, we simulate with random data
    capsule_data = secrets.token_bytes(768)
    
    # Embed the AES key in a way that can be "recovered" with the private key
    # In mock mode, we just store the key XOR'd with a fixed pattern
    mock_key_mask = hashlib.sha256(b"QUANTUM_MOCK_KYBER_KEY").digest()
    masked_key = bytes(a ^ b for a, b in zip(aes_key, mock_key_mask))
    
    # Combine into capsule
    capsule = base64.b64encode(capsule_data + masked_key).decode('ascii')
    
    return {
        'capsule': capsule,
        'encrypted_data': encrypted_hex,
        'nonce': nonce.hex()
    }


def dilithium_sign(booking_ref: str, seat_id: int, flight_id: int, passenger_name: str) -> dict:
    """
    Dilithium-3 Digital Signature Simulation.
    In a real implementation, this would use liboqs Dilithium3.
    Here we simulate with HMAC-SHA512.
    
    Returns:
        dict with 'signature', 'data_hash', and 'algorithm'
    """
    # Create the message to sign
    message = f"{booking_ref}|{seat_id}|{flight_id}|{passenger_name}"
    message_bytes = message.encode('utf-8')
    
    # Generate a mock "private key" based on the message
    # In reality, this would be a proper Dilithium keypair
    mock_private_key = hashlib.sha256(b"QUANTUM_DILITHIUM_PRIVATE_KEY").digest()
    
    # Create HMAC-SHA512 signature (simulating Dilithium)
    import hmac
    signature_bytes = hmac.new(
        mock_private_key,
        message_bytes,
        hashlib.sha512
    ).digest()
    
    # Dilithium3 signatures are ~3.3KB, we extend our signature to simulate
    padding = secrets.token_bytes(3000)  # Random padding to simulate size
    full_signature = base64.b64encode(signature_bytes + padding).decode('ascii')
    
    # Data hash for quick integrity check
    data_hash = hashlib.sha256(message_bytes).hexdigest()
    
    return {
        'signature': full_signature,
        'data_hash': data_hash,
        'algorithm': 'Dilithium3-Simulation (HMAC-SHA512)',
        'message_preview': message[:50] + '...' if len(message) > 50 else message
    }


def dilithium_verify(booking_ref: str, seat_id: int, flight_id: int, passenger_name: str, signature: str, data_hash: str) -> bool:
    """
    Verify a Dilithium-3 signature (simulation).
    
    Returns:
        bool indicating if signature is valid
    """
    # Recreate the message
    message = f"{booking_ref}|{seat_id}|{flight_id}|{passenger_name}"
    message_bytes = message.encode('utf-8')
    
    # Verify hash first
    expected_hash = hashlib.sha256(message_bytes).hexdigest()
    if expected_hash != data_hash:
        return False
    
    # Verify signature
    mock_private_key = hashlib.sha256(b"QUANTUM_DILITHIUM_PRIVATE_KEY").digest()
    import hmac
    expected_sig = hmac.new(
        mock_private_key,
        message_bytes,
        hashlib.sha512
    ).digest()
    
    # Extract the actual signature from the stored value (first 64 bytes after base64 decode)
    try:
        stored_sig = base64.b64decode(signature)[:64]
        return hmac.compare_digest(expected_sig, stored_sig)
    except Exception:
        return False


# =============================================================================
# API ENDPOINTS
# =============================================================================

@app.route('/api/flights', methods=['GET'])
def get_flights():
    """Get all available flights with seat counts."""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        cursor.execute("""
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
                COUNT(s.id) as total_seats,
                SUM(CASE WHEN s.is_booked = 0 THEN 1 ELSE 0 END) as available_seats
            FROM flights f
            LEFT JOIN seats s ON f.id = s.flight_id
            GROUP BY f.id
            ORDER BY f.departure_time
        """)
        
        flights = cursor.fetchall()
        
        # Convert datetime objects to ISO format strings
        for flight in flights:
            if flight['departure_time']:
                flight['departure_time'] = flight['departure_time'].isoformat()
            if flight['arrival_time']:
                flight['arrival_time'] = flight['arrival_time'].isoformat()
            flight['price'] = float(flight['price'])
        
        cursor.close()
        conn.close()
        
        return jsonify({
            'success': True,
            'data': {
                'flights': flights,
                'count': len(flights)
            }
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/seats/<int:flight_id>', methods=['GET'])
def get_seats(flight_id):
    """Get seat map for a specific flight."""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # Get flight info
        cursor.execute("""
            SELECT id, flight_number, origin, destination, departure_time, price
            FROM flights WHERE id = %s
        """, (flight_id,))
        
        flight = cursor.fetchone()
        if not flight:
            return jsonify({'success': False, 'error': 'Flight not found'}), 404
        
        # Convert datetime
        if flight['departure_time']:
            flight['departure_time'] = flight['departure_time'].isoformat()
        flight['price'] = float(flight['price'])
        
        # Get seats
        cursor.execute("""
            SELECT id, row_num, col_num, class, is_booked
            FROM seats
            WHERE flight_id = %s
            ORDER BY CAST(row_num AS UNSIGNED), col_num
        """, (flight_id,))
        
        seats = cursor.fetchall()
        
        # Organize into seat map
        seat_map = []
        current_row = None
        row_data = None
        
        for seat in seats:
            row = seat['row_num']
            if row != current_row:
                if row_data:
                    seat_map.append(row_data)
                row_data = {
                    'row': row,
                    'class': seat['class'],
                    'seats': []
                }
                current_row = row
            
            row_data['seats'].append({
                'id': seat['id'],
                'col': seat['col_num'],
                'label': f"{row}{seat['col_num']}",
                'is_booked': bool(seat['is_booked'])
            })
        
        if row_data:
            seat_map.append(row_data)
        
        # Statistics
        total = len(seats)
        booked = sum(1 for s in seats if s['is_booked'])
        available = total - booked
        
        cursor.close()
        conn.close()
        
        return jsonify({
            'success': True,
            'data': {
                'flight': flight,
                'seat_map': seat_map,
                'legend': {
                    'columns': ['A', 'B', 'C', 'D', 'E', 'F']
                },
                'statistics': {
                    'total_seats': total,
                    'booked_seats': booked,
                    'available_seats': available
                }
            }
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/book', methods=['POST'])
def create_booking():
    """
    Create a quantum-secured booking with pessimistic locking.
    
    Expected JSON body:
    {
        "flight_id": 1,
        "row": "5",
        "col": "A",
        "name": "John Quantum",
        "passport": "P12345678"
    }
    """
    conn = None
    try:
        # Parse request
        data = request.get_json()
        if not data:
            return jsonify({'success': False, 'error': 'No JSON data provided'}), 400
        
        # Validate required fields
        required = ['flight_id', 'row', 'col', 'name', 'passport']
        missing = [f for f in required if f not in data or not data[f]]
        if missing:
            return jsonify({'success': False, 'error': f'Missing fields: {", ".join(missing)}'}), 400
        
        flight_id = int(data['flight_id'])
        row_num = str(data['row'])
        col_num = str(data['col']).upper()
        passenger_name = data['name'].strip()
        passport = data['passport'].strip()
        
        # =========== DATABASE TRANSACTION WITH PESSIMISTIC LOCKING ===========
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # Step 1: Disable auto-commit (already done in config)
        conn.start_transaction()
        
        # Step 2: THE HARD LOCK (Pessimistic Locking)
        # SELECT ... FOR UPDATE locks the row until COMMIT or ROLLBACK
        cursor.execute("""
            SELECT id, is_booked, class 
            FROM seats 
            WHERE flight_id = %s AND row_num = %s AND col_num = %s
            FOR UPDATE
        """, (flight_id, row_num, col_num))
        
        seat = cursor.fetchone()
        
        if not seat:
            conn.rollback()
            return jsonify({
                'success': False, 
                'error': f'Seat {row_num}{col_num} not found on this flight'
            }), 404
        
        if seat['is_booked']:
            conn.rollback()
            return jsonify({
                'success': False, 
                'error': f'Seat {row_num}{col_num} is already booked'
            }), 409
        
        seat_id = seat['id']
        seat_class = seat['class']
        
        # =========== THE QUANTUM GAP ===========
        # While the database holds the lock, we process the quantum operations
        
        # Generate Quantum Reference ID (QRNG)
        qrng_ref = generate_quantum_entropy()
        
        # Encrypt passport with Kyber-simulated KEM
        kyber_result = kyber_encrypt(passport)
        
        # Sign the booking with Dilithium-simulated signature
        dilithium_result = dilithium_sign(qrng_ref, seat_id, flight_id, passenger_name)
        
        # =========== COMMIT PHASE ===========
        
        # Update seat as booked
        cursor.execute("""
            UPDATE seats SET is_booked = 1 WHERE id = %s
        """, (seat_id,))
        
        # Insert booking record
        cursor.execute("""
            INSERT INTO bookings (
                seat_id, flight_id, pqc_ref, passenger_name,
                kyber_capsule, passport_enc, encryption_nonce,
                pqc_signature, ticket_data_hash
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
        """, (
            seat_id,
            flight_id,
            qrng_ref,
            passenger_name,
            kyber_result['capsule'],
            kyber_result['encrypted_data'],
            kyber_result['nonce'],
            dilithium_result['signature'],
            dilithium_result['data_hash']
        ))
        
        booking_id = cursor.lastrowid
        
        # Get flight info for response
        cursor.execute("""
            SELECT flight_number, origin, destination, departure_time
            FROM flights WHERE id = %s
        """, (flight_id,))
        flight = cursor.fetchone()
        
        # COMMIT - releases the lock
        conn.commit()
        
        cursor.close()
        conn.close()
        
        # =========== SUCCESS RESPONSE ===========
        return jsonify({
            'success': True,
            'booking': {
                'id': booking_id,
                'booking_ref': qrng_ref,
                'passenger_name': passenger_name,
                'flight': {
                    'number': flight['flight_number'],
                    'origin': flight['origin'],
                    'destination': flight['destination'],
                    'departure': flight['departure_time'].isoformat() if flight['departure_time'] else None
                },
                'seat': {
                    'id': seat_id,
                    'label': f"{row_num}{col_num}",
                    'class': seat_class
                }
            },
            'quantum_security': {
                'mock_mode': True,  # We're using simulation
                'signature': {
                    'algorithm': dilithium_result['algorithm'],
                    'preview': dilithium_result['signature'][:80] + '...'
                },
                'encryption': {
                    'algorithm': 'Kyber512-Simulation (AES-256-GCM)',
                    'capsule_preview': kyber_result['capsule'][:80] + '...'
                },
                'entropy': {
                    'algorithm': 'QRNG-Simulation (secrets.token_bytes)'
                }
            }
        }), 201
        
    except mysql.connector.Error as e:
        if conn:
            conn.rollback()
        return jsonify({'success': False, 'error': f'Database error: {str(e)}'}), 500
    except Exception as e:
        if conn:
            conn.rollback()
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/verify', methods=['POST'])
def verify_ticket():
    """
    Verify a ticket's Dilithium signature.
    
    Expected JSON body:
    {
        "booking_ref": "QREF-XXXX-XXXX-XXXX"
    }
    """
    try:
        data = request.get_json()
        if not data or 'booking_ref' not in data:
            return jsonify({'success': False, 'error': 'Booking reference required'}), 400
        
        booking_ref = data['booking_ref'].strip().upper()
        
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # Get booking details
        cursor.execute("""
            SELECT 
                b.id, b.pqc_ref, b.passenger_name, b.pqc_signature, 
                b.ticket_data_hash, b.seat_id, b.flight_id,
                s.row_num, s.col_num, s.class as seat_class,
                f.flight_number, f.origin, f.destination
            FROM bookings b
            JOIN seats s ON b.seat_id = s.id
            JOIN flights f ON b.flight_id = f.id
            WHERE b.pqc_ref = %s
        """, (booking_ref,))
        
        booking = cursor.fetchone()
        
        cursor.close()
        conn.close()
        
        if not booking:
            return jsonify({
                'success': True,
                'data': {
                    'verified': False,
                    'message': 'Booking not found'
                }
            })
        
        # Verify the signature
        is_valid = dilithium_verify(
            booking['pqc_ref'],
            booking['seat_id'],
            booking['flight_id'],
            booking['passenger_name'],
            booking['pqc_signature'],
            booking['ticket_data_hash']
        )
        
        return jsonify({
            'success': True,
            'data': {
                'verified': is_valid,
                'message': 'Signature verified successfully' if is_valid else 'Signature verification failed',
                'ticket': {
                    'booking_ref': booking['pqc_ref'],
                    'flight_number': booking['flight_number'],
                    'route': f"{booking['origin']} → {booking['destination']}",
                    'passenger': booking['passenger_name'],
                    'seat': f"{booking['row_num']}{booking['col_num']} ({booking['seat_class']})"
                },
                'security': {
                    'signature_algorithm': 'Dilithium3-Simulation',
                    'mock_mode': True
                }
            }
        })
        
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint."""
    db_status = 'unknown'
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT 1")
        cursor.fetchone()
        cursor.close()
        conn.close()
        db_status = 'connected'
    except Exception as e:
        db_status = f'error: {str(e)}'
    
    return jsonify({
        'status': 'ok',
        'service': 'Quantum Booking API',
        'database': db_status,
        'crypto_library': 'available' if CRYPTO_AVAILABLE else 'simulation',
        'timestamp': datetime.now().isoformat()
    })


# =============================================================================
# Main Entry Point
# =============================================================================
if __name__ == '__main__':
    print("=" * 60)
    print("Split-Stack Quantum Booking System - Flask API Server")
    print("=" * 60)
    print(f"Database connector: {'Available' if DB_AVAILABLE else 'NOT INSTALLED'}")
    print(f"Crypto library: {'Available' if CRYPTO_AVAILABLE else 'Simulation mode'}")
    print("=" * 60)
    print("Starting server on http://localhost:5000")
    print("Press Ctrl+C to stop")
    print("=" * 60)
    
    app.run(host='0.0.0.0', port=5000, debug=True)
