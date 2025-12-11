#!/bin/bash
# Start Split-Stack Quantum Booking System using System Packages

echo "Starting Backend Server on port 5000..."
python server.py &
BACKEND_PID=$!

echo "Starting Frontend Server on port 8080..."
cd public && python -m http.server 8080 &
FRONTEND_PID=$!

echo "System running."
echo "Backend PID: $BACKEND_PID"
echo "Frontend PID: $FRONTEND_PID"
echo "Press Ctrl+C to stop both."

trap "kill $BACKEND_PID $FRONTEND_PID; exit" INT
wait
