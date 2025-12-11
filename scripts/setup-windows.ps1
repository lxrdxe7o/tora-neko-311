# Quantum Airline Booking System - Windows Setup Script
# Run this script in PowerShell as Administrator (if needed for pip)

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  Quantum Airline - Windows Setup    " -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Check Python
Write-Host "[1/4] Checking Python installation..." -ForegroundColor Yellow
try {
    $pythonVersion = python --version 2>&1
    Write-Host "      Found: $pythonVersion" -ForegroundColor Green
} catch {
    Write-Host "      ERROR: Python not found. Please install Python 3.10+ from python.org" -ForegroundColor Red
    exit 1
}

# Create virtual environment
Write-Host "[2/4] Creating virtual environment..." -ForegroundColor Yellow
if (Test-Path "venv") {
    Write-Host "      Virtual environment already exists, skipping..." -ForegroundColor Gray
} else {
    python -m venv venv
    Write-Host "      Created venv/" -ForegroundColor Green
}

# Activate and install dependencies
Write-Host "[3/4] Installing Python dependencies..." -ForegroundColor Yellow
& .\venv\Scripts\Activate.ps1
pip install flask flask-cors mysql-connector-python cryptography --quiet
Write-Host "      Installed: flask, flask-cors, mysql-connector-python, cryptography" -ForegroundColor Green

# Check XAMPP/MySQL
Write-Host "[4/4] Checking database..." -ForegroundColor Yellow
Write-Host "      Please ensure XAMPP MySQL is running before continuing." -ForegroundColor Yellow
Write-Host ""

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  Setup Complete!                    " -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor White
Write-Host "  1. Start XAMPP Control Panel and click 'Start' on MySQL" -ForegroundColor Gray
Write-Host "  2. Run: python init_db.py" -ForegroundColor Gray
Write-Host "  3. Run: .\scripts\run-windows.ps1" -ForegroundColor Gray
Write-Host "  4. Open: http://localhost:8080" -ForegroundColor Gray
Write-Host ""
