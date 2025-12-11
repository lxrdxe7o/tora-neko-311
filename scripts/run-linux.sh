#!/bin/bash
# Quantum Airline Booking System - Linux Run Script
# Alternative to run_system.sh with more verbose output

set -e

echo "======================================"
echo "  Quantum Airline - Starting...      "
echo "======================================"
echo ""

# Activate venv if exists
if [ -f "venv/bin/activate" ]; then
    source venv/bin/activate
    echo "[OK] Virtual environment activated"
fi

# Check database
echo "[1/3] Checking database connection..."
python -c "import mysql.connector; c=mysql.connector.connect(host='localhost',user='root',password=''); print('      Database connection successful')" 2>/dev/null || {
    echo "      WARNING: Cannot connect to database. Is MariaDB/MySQL running?"
    echo "      Try: sudo systemctl start mariadb"
}

# Start backend
echo "[2/3] Starting Flask backend on port 5000..."
python server.py &
BACKEND_PID=$!
sleep 2

# Check if backend started
if kill -0 $BACKEND_PID 2>/dev/null; then
    echo "      Backend started (PID: $BACKEND_PID)"
else
    echo "      ERROR: Backend failed to start"
    exit 1
fi

# Start frontend
echo "[3/3] Starting frontend on port 8080..."
cd public && python -m http.server 8080 &
FRONTEND_PID=$!
cd ..
sleep 1

echo ""
echo "======================================"
echo "  System Running!                    "
echo "======================================"
echo ""
echo "  Frontend: http://localhost:8080"
echo "  Backend:  http://localhost:5000/api/health"
echo ""
echo "  Backend PID:  $BACKEND_PID"
echo "  Frontend PID: $FRONTEND_PID"
echo ""
echo "Press Ctrl+C to stop both servers..."
echo ""

# Handle shutdown
trap "echo 'Stopping servers...'; kill $BACKEND_PID $FRONTEND_PID 2>/dev/null; exit" INT TERM

# Wait
wait
