#!/bin/bash
# Quantum Airline Booking System - Linux Setup Script

set -e

echo "======================================"
echo "  Quantum Airline - Linux Setup      "
echo "======================================"
echo ""

# Detect distro
if command -v pacman &> /dev/null; then
    DISTRO="arch"
elif command -v apt &> /dev/null; then
    DISTRO="debian"
elif command -v dnf &> /dev/null; then
    DISTRO="fedora"
else
    DISTRO="unknown"
fi

echo "[1/4] Detected distribution: $DISTRO"

# Install dependencies based on distro
echo "[2/4] Installing dependencies..."
case $DISTRO in
    arch)
        echo "      Using pacman/yay for Arch Linux..."
        if command -v yay &> /dev/null; then
            yay -S --noconfirm --needed python-flask python-flask-cors python-mysql-connector python-cryptography mariadb 2>/dev/null || {
                echo "      Note: Some packages may require manual installation."
            }
        else
            sudo pacman -S --noconfirm --needed python mariadb
            pip install --user flask flask-cors mysql-connector-python cryptography
        fi
        ;;
    debian)
        echo "      Using apt for Debian/Ubuntu..."
        sudo apt update
        sudo apt install -y python3 python3-pip python3-venv mariadb-server
        
        # Create venv and install
        python3 -m venv venv
        source venv/bin/activate
        pip install flask flask-cors mysql-connector-python cryptography
        ;;
    fedora)
        echo "      Using dnf for Fedora..."
        sudo dnf install -y python3 python3-pip mariadb-server
        pip3 install --user flask flask-cors mysql-connector-python cryptography
        ;;
    *)
        echo "      Unknown distro. Installing via pip..."
        pip install --user flask flask-cors mysql-connector-python cryptography
        ;;
esac

# Start MariaDB if not running
echo "[3/4] Checking MariaDB service..."
if systemctl is-active --quiet mariadb 2>/dev/null; then
    echo "      MariaDB is running"
elif systemctl is-active --quiet mysql 2>/dev/null; then
    echo "      MySQL is running"
else
    echo "      Starting MariaDB..."
    sudo systemctl start mariadb 2>/dev/null || sudo systemctl start mysql 2>/dev/null || {
        echo "      WARNING: Could not start database service. Please start manually."
    }
fi

# Make run script executable
echo "[4/4] Setting permissions..."
chmod +x run_system.sh 2>/dev/null || true
chmod +x scripts/*.sh 2>/dev/null || true

echo ""
echo "======================================"
echo "  Setup Complete!                    "
echo "======================================"
echo ""
echo "Next steps:"
echo "  1. Ensure MariaDB/MySQL is running"
echo "  2. Run: python init_db.py"
echo "  3. Run: ./run_system.sh"
echo "  4. Open: http://localhost:8080"
echo ""
