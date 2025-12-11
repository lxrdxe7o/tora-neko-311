# Quantum-Secure Airline Booking System

A next-generation airline booking platform integrating **Post-Quantum Cryptography (PQC)** to protect against "Harvest Now, Decrypt Later" attacks.

```
 ██████  ██    ██  █████  ███    ██ ████████ ██    ██ ███    ███ 
██    ██ ██    ██ ██   ██ ████   ██    ██    ██    ██ ████  ████ 
██    ██ ██    ██ ███████ ██ ██  ██    ██    ██    ██ ██ ████ ██ 
██ ▄▄ ██ ██    ██ ██   ██ ██  ██ ██    ██    ██    ██ ██  ██  ██ 
 ██████   ██████  ██   ██ ██   ████    ██     ██████  ██      ██ 
    ▀▀                                                           
          █████  ██ ██████  ██     ██  █████  ██    ██ ███████   
         ██   ██ ██ ██   ██ ██     ██ ██   ██  ██  ██  ██        
         ███████ ██ ██████  ██  █  ██ ███████   ████   ███████   
         ██   ██ ██ ██   ██ ██ ███ ██ ██   ██    ██         ██   
         ██   ██ ██ ██   ██  ███ ███  ██   ██    ██    ███████   
```

## The Quantum Trinity

This system implements three layers of quantum-resistant security:

| Layer | Algorithm | NIST Standard | Purpose |
|-------|-----------|---------------|---------|
| **Identity** | Dilithium3 | FIPS 204 (ML-DSA) | Digital signatures for ticket authenticity |
| **Confidentiality** | Kyber512 | FIPS 203 (ML-KEM) | Hybrid encryption for sensitive data |
| **Randomness** | QRNG Simulation | N/A | Quantum-grade entropy for booking IDs |

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           FRONTEND (HTML/CSS/JS)                        │
│                     Dark "Cyberpunk" Themed UI                          │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │ AJAX/Fetch
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         PHP BACKEND (api/)                              │
│         BookingService → QuantumBridge → Python Microservice            │
└────────────────────────────────────┬──────────────────────┬─────────────┘
                                     │                      │
                                     ▼                      ▼
┌────────────────────────────────────────────┐  ┌─────────────────────────┐
│         PostgreSQL 14+                     │  │   QUANTUM SERVICE       │
│   ACID Transactions + Row-Level Locking    │  │   (Python + liboqs)     │
└────────────────────────────────────────────┘  └─────────────────────────┘
```

## Prerequisites

- **PHP** 8.1+ with extensions: `pdo_pgsql`, `json`, `mbstring`
- **PostgreSQL** 14+
- **Python** 3.10+
- **Optional**: `liboqs-python` for real PQC (falls back to mock mode)
- **Optional**: `qiskit` for quantum circuit simulation

## Quick Start

### 1. Clone & Setup

```bash
git clone <repository-url>
cd quantum-airline

# Make setup script executable
chmod +x setup.sh

# Run setup (creates database, installs dependencies)
./setup.sh
```

### 2. Manual Setup (Alternative)

```bash
# Create PostgreSQL database
createdb quantum_airline

# Run schema
psql -d quantum_airline -f schema.sql

# Install Python dependencies
cd quantum_service
pip install -r requirements.txt
cd ..
```

### 3. Configure Database

Edit `backend/config/database.php` with your credentials:

```php
return [
    'host' => 'localhost',
    'port' => 5432,
    'dbname' => 'quantum_airline',
    'user' => 'postgres',
    'password' => 'your_password'
];
```

### 4. Start the Server

```bash
# Using PHP built-in server (development)
php -S localhost:8000 -t public

# Or configure Apache/Nginx to point to public/
```

### 5. Access the Application

Open `http://localhost:8000` in your browser.

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/flights.php` | GET | List all available flights |
| `/api/seats.php?flight_id=X` | GET | Get seat map for a flight |
| `/api/book.php` | POST | Create quantum-secured booking |
| `/api/verify.php` | POST | Verify ticket signature |

### Booking Request Example

```json
POST /api/book.php
{
    "seat_id": 15,
    "passenger_name": "John Quantum",
    "passport_number": "AB1234567"
}
```

### Booking Response Example

```json
{
    "success": true,
    "booking": {
        "booking_ref": "QX7A9B2C",
        "flight": "QA-101",
        "seat": "4C",
        "passenger": "John Quantum"
    },
    "quantum_security": {
        "signature_algorithm": "Dilithium3",
        "encryption_algorithm": "Kyber512-AES256GCM",
        "entropy_source": "QRNG-Hadamard",
        "signature_preview": "3a4b5c6d..."
    }
}
```

## Security Deep Dive

### Why Post-Quantum Cryptography?

Current encryption (RSA, ECDSA) will be broken by quantum computers using Shor's algorithm. Adversaries can harvest encrypted data today and decrypt it when quantum computers mature ("Harvest Now, Decrypt Later").

### Dilithium3 (Digital Signatures)

- **Purpose**: Ensures ticket authenticity - no one can forge a valid ticket
- **Security Level**: NIST Level 3 (~128-bit classical, ~128-bit quantum)
- **Based On**: Module-LWE and Module-SIS lattice problems
- **NIST Status**: FIPS 204 (ML-DSA) - Standardized August 2024

### Kyber512 (Key Encapsulation)

- **Purpose**: Securely exchange symmetric keys for data encryption
- **Workflow**: 
  1. Generate Kyber keypair
  2. Encapsulate to produce shared secret
  3. Derive AES-256 key from shared secret
  4. Encrypt sensitive data (passport numbers)
- **Security Level**: NIST Level 1 (~128-bit)
- **NIST Status**: FIPS 203 (ML-KEM) - Standardized August 2024

### QRNG Simulation

- **Purpose**: Generate unpredictable booking reference IDs
- **Method**: Simulates Hadamard gate measurements on qubits
- **Fallback**: Uses `secrets.token_bytes()` (cryptographically secure PRNG)

## Concurrency & ACID Compliance

The system prevents double-booking using PostgreSQL's `SELECT ... FOR UPDATE`:

```sql
BEGIN;
SELECT * FROM seats WHERE id = $1 FOR UPDATE;  -- Acquires row lock
-- Check if seat is available
-- Perform quantum operations
-- Insert booking
UPDATE seats SET is_booked = true WHERE id = $1;
COMMIT;
```

### Stress Test

Run the included stress test to verify concurrency handling:

```bash
php tests/stress_test.php
```

This simulates 20 concurrent users attempting to book the same seat. Expected result: 1 success, 19 failures.

## Mock Mode

If `liboqs` is not installed, the system automatically falls back to mock mode:

| Component | Real Mode | Mock Mode |
|-----------|-----------|-----------|
| Dilithium3 | `oqs.Signature` | HMAC-SHA512 |
| Kyber512 | `oqs.KeyEncapsulation` | `secrets.token_bytes` |
| QRNG | Qiskit Hadamard circuit | `secrets.token_hex` |

Mock mode is **clearly indicated** in API responses and the UI.

## Project Structure

```
quantum-airline/
├── schema.sql                 # Database schema + seed data
├── setup.sh                   # Automated setup script
├── quantum_service/           # Python PQC microservice
│   ├── config.py              # Mock mode detection
│   ├── entropy.py             # QRNG simulation
│   ├── encryptor.py           # Kyber512 + AES-256-GCM
│   ├── signer.py              # Dilithium3 signatures
│   └── decryptor.py           # Data decryption
├── backend/                   # PHP application
│   ├── config/database.php    # DB configuration
│   ├── core/                  # Core classes
│   ├── services/              # Business logic
│   └── repositories/          # Data access
├── api/                       # REST endpoints
├── public/                    # Frontend assets
│   ├── index.html
│   ├── css/style.css
│   └── js/app.js
└── tests/                     # Test suite
    └── stress_test.php
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- [Open Quantum Safe (liboqs)](https://openquantumsafe.org/) - PQC library
- [NIST Post-Quantum Cryptography](https://csrc.nist.gov/projects/post-quantum-cryptography) - Standards
- [Qiskit](https://qiskit.org/) - Quantum computing framework

---

**Note**: This is a demonstration platform. For production use, additional security measures (authentication, rate limiting, input validation, audit logging) should be implemented.
