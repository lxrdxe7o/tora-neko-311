# Quantum Airline Booking System - Windows Run Script
# Starts both backend and frontend servers

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  Quantum Airline - Starting...      " -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Activate virtual environment
if (Test-Path "venv\Scripts\Activate.ps1") {
    & .\venv\Scripts\Activate.ps1
    Write-Host "[OK] Virtual environment activated" -ForegroundColor Green
} else {
    Write-Host "[WARN] No virtual environment found. Using system Python." -ForegroundColor Yellow
}

# Check if database is accessible
Write-Host "[1/3] Checking database connection..." -ForegroundColor Yellow
$dbCheck = python -c "import mysql.connector; c=mysql.connector.connect(host='localhost',user='root',password=''); print('OK')" 2>&1
if ($dbCheck -eq "OK") {
    Write-Host "      Database connection successful" -ForegroundColor Green
} else {
    Write-Host "      WARNING: Cannot connect to database. Is XAMPP MySQL running?" -ForegroundColor Red
}

# Start Backend
Write-Host "[2/3] Starting Flask backend on port 5000..." -ForegroundColor Yellow
Start-Process -FilePath "python" -ArgumentList "server.py" -WindowStyle Minimized
Write-Host "      Backend started (minimized window)" -ForegroundColor Green

# Start Frontend
Write-Host "[3/3] Starting frontend on port 8080..." -ForegroundColor Yellow
$frontendProcess = Start-Process -FilePath "python" -ArgumentList "-m http.server 8080" -WorkingDirectory "public" -WindowStyle Minimized -PassThru
Write-Host "      Frontend started (minimized window)" -ForegroundColor Green

Write-Host ""
Write-Host "======================================" -ForegroundColor Cyan
Write-Host "  System Running!                    " -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Frontend: http://localhost:8080" -ForegroundColor White
Write-Host "  Backend:  http://localhost:5000/api/health" -ForegroundColor White
Write-Host ""
Write-Host "Press Enter to open browser, or Ctrl+C to exit..." -ForegroundColor Gray
Read-Host

Start-Process "http://localhost:8080"
