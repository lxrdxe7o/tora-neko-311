<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=gradient&customColorList=6,11,20&height=180&section=header&text=✈️%20QUANTUM%20AIRWAYS&fontSize=42&fontColor=fff&animation=twinkling&fontAlignY=32&desc=Post-Quantum%20Secure%20Booking%20System&descSize=18&descAlignY=52"/>
</p>

<p align="center">
  <a href="#-quick-start"><img src="https://img.shields.io/badge/🚀_Quick_Start-blue?style=for-the-badge" alt="Quick Start"/></a>
  <a href="#-windows-setup"><img src="https://img.shields.io/badge/🪟_Windows-0078D4?style=for-the-badge&logo=windows&logoColor=white" alt="Windows"/></a>
  <a href="#-linux-setup"><img src="https://img.shields.io/badge/🐧_Linux-FCC624?style=for-the-badge&logo=linux&logoColor=black" alt="Linux"/></a>
  <a href="#-api-endpoints"><img src="https://img.shields.io/badge/📡_API-green?style=for-the-badge" alt="API"/></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Python-3.10+-3776AB?style=flat-square&logo=python&logoColor=white"/>
  <img src="https://img.shields.io/badge/Flask-3.0+-000000?style=flat-square&logo=flask&logoColor=white"/>
  <img src="https://img.shields.io/badge/MariaDB-003545?style=flat-square&logo=mariadb&logoColor=white"/>
  <img src="https://img.shields.io/badge/License-MIT-yellow?style=flat-square"/>
</p>

---

## 🛡️ The Quantum Trinity

<table align="center">
<tr>
<td align="center" width="33%">
<img src="https://img.shields.io/badge/🔐-Dilithium3-blueviolet?style=for-the-badge"/>
<br/><b>Digital Signatures</b>
<br/><sub>FIPS 204 (ML-DSA)</sub>
<br/><sub>Unforgeable ticket authenticity</sub>
</td>
<td align="center" width="33%">
<img src="https://img.shields.io/badge/🔑-Kyber512-ff69b4?style=for-the-badge"/>
<br/><b>Key Encapsulation</b>
<br/><sub>FIPS 203 (ML-KEM)</sub>
<br/><sub>Quantum-safe encryption</sub>
</td>
<td align="center" width="33%">
<img src="https://img.shields.io/badge/🎲-QRNG-00d4ff?style=for-the-badge"/>
<br/><b>Entropy Source</b>
<br/><sub>Hadamard Simulation</sub>
<br/><sub>True random booking IDs</sub>
</td>
</tr>
</table>

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│  🌐  FRONTEND (HTML/CSS/JS)                                             │
│      Cyberpunk UI • Port 80/8080                                        │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │ CORS
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  🐍  PYTHON FLASK BACKEND                                               │
│      Quantum Trinity • Port 5000                                        │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  🗄️  MariaDB / MySQL                                                    │
│      InnoDB • Row-Level Locking • Port 3306                             │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Quick Start

```bash
# Clone
git clone <repository-url> && cd quantum-airline

# Setup (choose your OS below)
# Initialize database
python init_db.py

# Run
./run_system.sh          # Linux
.\scripts\run-windows.ps1 # Windows
```

**Open:** http://localhost:8080

---

## 🪟 Windows Setup

<details>
<summary><b>📋 Prerequisites</b></summary>

- [XAMPP](https://www.apachefriends.org/) (Apache + MariaDB)
- [Python 3.10+](https://www.python.org/downloads/)
- Git (optional)

</details>

### ⚡ Automated Setup

```powershell
.\scripts\setup-windows.ps1
python init_db.py
.\scripts\run-windows.ps1
```

### 🔧 Manual Setup

```powershell
# 1️⃣ Create virtual environment
python -m venv venv
.\venv\Scripts\Activate.ps1

# 2️⃣ Install dependencies
pip install flask flask-cors mysql-connector-python cryptography

# 3️⃣ Start XAMPP MySQL from Control Panel

# 4️⃣ Initialize database
python init_db.py

# 5️⃣ Start servers
python server.py                           # Terminal 1
cd public; python -m http.server 8080      # Terminal 2
```

---

## 🐧 Linux Setup

<details>
<summary><b>📋 Prerequisites</b></summary>

- Python 3.10+
- MariaDB or MySQL
- Git (optional)

</details>

### ⚡ Automated Setup

```bash
chmod +x scripts/*.sh run_system.sh
./scripts/setup-linux.sh
python init_db.py
./run_system.sh
```

### 🔧 Arch Linux

```bash
yay -S python-flask python-flask-cors python-mysql-connector python-cryptography mariadb
sudo systemctl start mariadb
python init_db.py
./run_system.sh
```

### 🔧 Ubuntu/Debian

```bash
sudo apt install python3 python3-pip python3-venv mariadb-server
python3 -m venv venv && source venv/bin/activate
pip install flask flask-cors mysql-connector-python cryptography
sudo systemctl start mariadb
python init_db.py
./run_system.sh
```

---

## 📡 API Endpoints

| Endpoint          | Method | Description         |
| :---------------- | :----: | :------------------ |
| `/api/health`     | `GET`  | 🩺 Health check     |
| `/api/flights`    | `GET`  | ✈️ List flights     |
| `/api/seats/<id>` | `GET`  | 💺 Seat map         |
| `/api/book`       | `POST` | 🎫 Book ticket      |
| `/api/verify`     | `POST` | ✅ Verify signature |

<details>
<summary><b>📝 Request/Response Examples</b></summary>

**Book Request:**

```json
POST /api/book
{
  "flight_id": 1,
  "row": "5", "col": "A",
  "name": "John Quantum",
  "passport": "AB1234567"
}
```

**Book Response:**

```json
{
  "success": true,
  "booking": {
    "booking_ref": "QREF-X7Z9-A2B4-C6D8",
    "seat": { "label": "5A", "class": "economy" }
  },
  "quantum_security": {
    "signature": { "algorithm": "Dilithium3-Simulation" },
    "encryption": { "algorithm": "Kyber512-AES256GCM" }
  }
}
```

</details>

---

## 📁 Project Structure

```
quantum-airline/
├── 🐍 server.py              # Flask backend
├── 🗄️ schema_mariadb.sql     # Database schema
├── 🔧 init_db.py             # DB initializer
├── 🚀 run_system.sh          # Linux launcher
├── 📦 requirements.txt       # Python deps
├── 🔬 quantum_service/       # PQC modules
│   ├── entropy.py            # QRNG
│   ├── encryptor.py          # Kyber512
│   ├── signer.py             # Dilithium3
│   └── decryptor.py          # Decryption
├── 🌐 public/                # Frontend
│   ├── index.html
│   ├── css/style.css
│   └── js/app.js
└── 📜 scripts/               # Setup scripts
    ├── setup-windows.ps1
    ├── run-windows.ps1
    ├── setup-linux.sh
    └── run-linux.sh
```

---

## 🔥 Troubleshooting

| Issue               | Solution                          |
| :------------------ | :-------------------------------- |
| 🔴 Connection Error | Start Flask backend on port 5000  |
| 🔴 Database failed  | Start MariaDB/MySQL service       |
| 🔴 CORS error       | Check `server.py` origins list    |
| 🔴 Module not found | Activate venv or install packages |

---

## ⚠️ Security Note

> This is a **demo platform** using simulated PQC. For production:
>
> - Install `liboqs-python` for real quantum algorithms
> - Enable HTTPS and authentication
> - Add rate limiting and audit logs

---

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=gradient&customColorList=6,11,20&height=100&section=footer"/>
</p>

<p align="center">
  <sub>Built with 💜 using Post-Quantum Cryptography</sub>
  <br/>
  <a href="https://openquantumsafe.org/">liboqs</a> •
  <a href="https://csrc.nist.gov/projects/post-quantum-cryptography">NIST PQC</a> •
  <a href="https://flask.palletsprojects.com/">Flask</a>
</p>
