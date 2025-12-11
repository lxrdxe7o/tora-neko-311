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

| Layer               | Algorithm       | NIST Standard     | Purpose                                    |
| ------------------- | --------------- | ----------------- | ------------------------------------------ |
| **Identity**        | Dilithium3      | FIPS 204 (ML-DSA) | Digital signatures for ticket authenticity |
| **Confidentiality** | Kyber512        | FIPS 203 (ML-KEM) | Hybrid encryption for sensitive data       |
| **Randomness**      | QRNG Simulation | N/A               | Quantum-grade entropy for booking IDs      |

## Architecture (Split-Stack)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      FRONTEND (HTML/CSS/JS)                             │
│                 Served on Apache (Port 80) or Python (Port 8080)        │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │ AJAX/Fetch (CORS)
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      PYTHON FLASK BACKEND (Port 5000)                   │
│        Quantum Trinity: Kyber + Dilithium + QRNG Simulation             │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      MariaDB / MySQL (Port 3306)                        │
│             InnoDB Engine with Row-Level Locking (FOR UPDATE)           │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 🪟 Windows Setup (XAMPP)

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (includes Apache + MariaDB)
- [Python 3.10+](https://www.python.org/downloads/)
- Git (optional)

### Quick Start (PowerShell)

```powershell
# Clone the repository
git clone <repository-url>
cd quantum-airline

# Run the setup script
.\scripts\setup-windows.ps1
```

### Manual Setup

#### Step 1: Install Python Dependencies

```powershell
# Create virtual environment (recommended)
python -m venv venv
.\venv\Scripts\Activate.ps1

# Install packages
pip install flask flask-cors mysql-connector-python cryptography
```

#### Step 2: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** (optional, for serving frontend)
3. Start **MySQL** (required for database)

#### Step 3: Initialize Database

```powershell
# With venv activated
python init_db.py
```

#### Step 4: Start the Application

```powershell
# Option 1: Use the run script
.\scripts\run-windows.ps1

# Option 2: Manual start
# Terminal 1 - Backend
python server.py

# Terminal 2 - Frontend
cd public
python -m http.server 8080
```

#### Step 5: Open Browser

Navigate to: **http://localhost:8080**

---

## 🐧 Linux Setup (Arch/Ubuntu/Debian)

### Prerequisites

- Python 3.10+
- MariaDB or MySQL
- Git (optional)

### Quick Start (Bash)

```bash
# Clone the repository
git clone <repository-url>
cd quantum-airline

# Make scripts executable
chmod +x scripts/*.sh run_system.sh

# Run setup
./scripts/setup-linux.sh
```

### Manual Setup

#### Arch Linux

```bash
# Install system packages
yay -S python-flask python-flask-cors python-mysql-connector python-cryptography mariadb

# Initialize MariaDB (if first time)
sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Initialize database
python init_db.py

# Run the application
./run_system.sh
```

#### Ubuntu/Debian

```bash
# Install Python and pip
sudo apt update
sudo apt install python3 python3-pip python3-venv mariadb-server

# Create virtual environment
python3 -m venv venv
source venv/bin/activate

# Install Python packages
pip install flask flask-cors mysql-connector-python cryptography

# Start MariaDB
sudo systemctl start mariadb

# Initialize database
python init_db.py

# Run the application
./run_system.sh
```

#### Step: Open Browser

Navigate to: **http://localhost:8080**

---

## API Endpoints

| Endpoint                 | Method | Description                    |
| ------------------------ | ------ | ------------------------------ |
| `/api/health`            | GET    | Service health check           |
| `/api/flights`           | GET    | List all available flights     |
| `/api/seats/<flight_id>` | GET    | Get seat map for a flight      |
| `/api/book`              | POST   | Create quantum-secured booking |
| `/api/verify`            | POST   | Verify ticket signature        |

### Booking Request Example

```json
POST /api/book
{
    "flight_id": 1,
    "row": "5",
    "col": "A",
    "name": "John Quantum",
    "passport": "AB1234567"
}
```

### Booking Response Example

```json
{
  "success": true,
  "booking": {
    "booking_ref": "QREF-X7Z9-A2B4-C6D8",
    "passenger_name": "John Quantum",
    "seat": { "label": "5A", "class": "economy" }
  },
  "quantum_security": {
    "signature": { "algorithm": "Dilithium3-Simulation" },
    "encryption": { "algorithm": "Kyber512-Simulation (AES-256-GCM)" }
  }
}
```

---

## Project Structure

```
quantum-airline/
├── server.py                  # Flask backend (Port 5000)
├── init_db.py                 # Database initialization script
├── run_system.sh              # Linux startup script
├── schema_mariadb.sql         # MariaDB schema + seed data
├── requirements.txt           # Python dependencies
├── quantum_service/           # Quantum simulation modules
│   ├── entropy.py             # QRNG simulation
│   ├── encryptor.py           # Kyber512 + AES-256-GCM
│   ├── signer.py              # Dilithium3 signatures
│   └── decryptor.py           # Data decryption
├── public/                    # Frontend assets
│   ├── index.html             # Main page
│   ├── css/style.css          # Cyberpunk styling
│   └── js/app.js              # Frontend logic
├── scripts/                   # Setup & run scripts
│   ├── setup-windows.ps1      # Windows setup
│   ├── run-windows.ps1        # Windows run
│   ├── setup-linux.sh         # Linux setup
│   └── run-linux.sh           # Linux run
└── README.md                  # This file
```

---

## Troubleshooting

| Issue                           | Solution                                                  |
| ------------------------------- | --------------------------------------------------------- |
| **Connection Error** in browser | Ensure Flask backend is running on port 5000              |
| **Database connection failed**  | Start MariaDB/MySQL service                               |
| **CORS error** in console       | Check `server.py` CORS origins include your frontend port |
| **Module not found**            | Activate virtual environment or install system packages   |

---

## Security Note

This is a **demonstration platform** using simulated quantum cryptography. For production:

- Install `liboqs-python` for real PQC algorithms
- Add authentication and rate limiting
- Enable HTTPS
- Implement audit logging

---

## License

MIT License - See LICENSE file for details.

## Acknowledgments

- [Open Quantum Safe (liboqs)](https://openquantumsafe.org/)
- [NIST Post-Quantum Cryptography](https://csrc.nist.gov/projects/post-quantum-cryptography)
