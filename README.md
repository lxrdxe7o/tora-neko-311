<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=gradient&customColorList=6,11,20&height=180&section=header&text=✈️%20QUANTUM%20AIRWAYS&fontSize=42&fontColor=fff&animation=twinkling&fontAlignY=32&desc=Post-Quantum%20Secure%20Booking%20System&descSize=18&descAlignY=52"/>
</p>

<p align="center">
  <a href="#-quick-start"><img src="https://img.shields.io/badge/🚀_Quick_Start-blue?style=for-the-badge" alt="Quick Start"/></a>
  <a href="#-tech-stack"><img src="https://img.shields.io/badge/💻_Stack-purple?style=for-the-badge" alt="Tech Stack"/></a>
  <a href="#-api-endpoints"><img src="https://img.shields.io/badge/📡_API-green?style=for-the-badge" alt="API"/></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Docker-2496ED?style=flat-square&logo=docker&logoColor=white"/>
  <img src="https://img.shields.io/badge/Node.js-339933?style=flat-square&logo=nodedotjs&logoColor=white"/>
  <img src="https://img.shields.io/badge/Vite-646CFF?style=flat-square&logo=vite&logoColor=white"/>
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

## 🏗️ Architecture (Dockerized)

```
┌─────────────────────────────────────────────────────────────────────────┐
│  ⚛️  NODE.JS FRONTEND (Vite)                                            │
│      Cyberpunk UI • Port 3000 (Host)                                    │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │ Proxy /api
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  🐍  PYTHON FLASK BACKEND                                               │
│      Quantum Trinity • Port 5000 (Internal)                             │
└────────────────────────────────────┬────────────────────────────────────┘
                                     │
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  🗄️  MariaDB                                                            │
│      InnoDB • Port 3306 (Internal)                                      │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Quick Start

This project is fully containerized. You only need **Docker** and **Docker Compose**.

```bash
# 1. Clone
git clone <repository-url> && cd quantum-airline

# 2. Run (Builds containers, inits DB, starts services)
./run_system.sh
```

**Access the application:**

- **Frontend:** [http://localhost:3000](http://localhost:3000)
- **Backend API:** [http://localhost:5000/api/health](http://localhost:5000/api/health)

To stop the system:

```bash
docker-compose down
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
├── 🐳 docker-compose.yml     # Service orchestration
├── 🐳 Dockerfile             # Backend image definition
├── 🚀 run_system.sh          # Setup & Run script
├── 🐍 server.py              # Flask backend
├── 🔧 init_db.py             # DB initializer
├── 📦 requirements.txt       # Python deps
├── 📂 frontend/              # New Node/Vite Frontend
│   ├── 🐳 Dockerfile         # Frontend image definition
│   ├── ⚙️ vite.config.ts     # Vite configuration
│   ├── 📄 package.json       # Node dependencies
│   ├── 📂 src/               # TypeScript source
│   └── 📂 public/            # Static assets
├── 📂 quantum_service/       # PQC modules
│   ├── entropy.py            # QRNG
│   ├── encryptor.py          # Kyber512
│   ├── signer.py             # Dilithium3
│   └── decryptor.py          # Decryption
└── 📂 public_legacy/         # Old static frontend (archived)
```

---

## 🔥 Troubleshooting

| Issue                                | Solution                                                                   |
| :----------------------------------- | :------------------------------------------------------------------------- |
| 🔴 **Containers fail to start**      | Ensure Docker Desktop is running and ports 3000, 5000, 3306 are free.      |
| 🔴 **Frontend "Connection Refused"** | Wait 10-20s for the backend to fully start up.                             |
| 🔴 **Database issues**               | Delete the volume: `docker volume rm quantum-airline_db_data` and restart. |

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
  <a href="https://flask.palletsprojects.com/">Flask</a> •
  <a href="https://vitejs.dev/">Vite</a>
</p>
