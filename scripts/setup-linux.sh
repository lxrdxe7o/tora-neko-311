#!/bin/bash
# Quantum Airline Booking System - Docker Setup Script

set -e

echo "======================================"
echo "  Quantum Airline - Docker Setup     "
echo "======================================"
echo ""

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "[ERROR] Docker is not installed."
    echo "Please install Docker and Docker Compose to run this project."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "[ERROR] docker-compose is not installed."
    exit 1
fi

echo "[OK] Docker is installed."
echo ""
echo "Setup is simple with Docker:"
echo "1. Run: ./run_system.sh"
echo "2. Access: http://localhost:3000"
echo ""
