#!/bin/bash
# =============================================================================
# Quantum-Secure Airline Booking System - Setup Script
# =============================================================================
# This script automates the initial setup of the application:
#   1. Validates prerequisites (PHP, PostgreSQL, Python)
#   2. Creates the PostgreSQL database
#   3. Runs the database schema
#   4. Installs Python dependencies
#   5. Validates the quantum service
# =============================================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# =============================================================================
# Helper Functions
# =============================================================================

print_banner() {
    echo ""
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║           QUANTUM-SECURE AIRLINE BOOKING SYSTEM - SETUP                  ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

print_step() {
    echo -e "${CYAN}[STEP]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_command() {
    if command -v $1 &> /dev/null; then
        print_success "$1 is installed"
        return 0
    else
        print_error "$1 is not installed"
        return 1
    fi
}

# =============================================================================
# Configuration
# =============================================================================

DB_NAME="${DB_NAME:-quantum_airline}"
DB_USER="${DB_USER:-postgres}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# =============================================================================
# Main Setup
# =============================================================================

print_banner

# -----------------------------------------------------------------------------
# Step 1: Check Prerequisites
# -----------------------------------------------------------------------------
print_step "Checking prerequisites..."

PREREQS_OK=true

# Check PHP
if check_command php; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d ' ' -f 2 | cut -d '.' -f 1,2)
    echo "       PHP version: $PHP_VERSION"
    
    # Check required extensions
    if php -m | grep -q "pdo_pgsql"; then
        print_success "PHP pdo_pgsql extension is installed"
    else
        print_warning "PHP pdo_pgsql extension is NOT installed"
        PREREQS_OK=false
    fi
else
    PREREQS_OK=false
fi

# Check PostgreSQL
if check_command psql; then
    PSQL_VERSION=$(psql --version | cut -d ' ' -f 3 | cut -d '.' -f 1)
    echo "       PostgreSQL version: $PSQL_VERSION"
else
    PREREQS_OK=false
fi

# Check Python
if check_command python3; then
    PYTHON_VERSION=$(python3 --version | cut -d ' ' -f 2)
    echo "       Python version: $PYTHON_VERSION"
else
    PREREQS_OK=false
fi

# Check pip
if check_command pip3 || check_command pip; then
    :
else
    print_warning "pip is not installed - Python dependencies may need manual installation"
fi

echo ""

if [ "$PREREQS_OK" = false ]; then
    print_error "Some prerequisites are missing. Please install them and try again."
    exit 1
fi

# -----------------------------------------------------------------------------
# Step 2: Create Database
# -----------------------------------------------------------------------------
print_step "Setting up PostgreSQL database..."

# Check if database exists
if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -lqt | cut -d \| -f 1 | grep -qw "$DB_NAME"; then
    print_warning "Database '$DB_NAME' already exists"
    read -p "Do you want to drop and recreate it? (y/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_step "Dropping existing database..."
        psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -c "DROP DATABASE $DB_NAME;" 2>/dev/null || true
        psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -c "CREATE DATABASE $DB_NAME;" 2>/dev/null
        print_success "Database recreated"
    else
        print_warning "Skipping database creation"
    fi
else
    print_step "Creating database '$DB_NAME'..."
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -c "CREATE DATABASE $DB_NAME;" 2>/dev/null
    print_success "Database created"
fi

# -----------------------------------------------------------------------------
# Step 3: Run Schema
# -----------------------------------------------------------------------------
print_step "Running database schema..."

if [ -f "$SCRIPT_DIR/schema.sql" ]; then
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$SCRIPT_DIR/schema.sql" > /dev/null 2>&1
    print_success "Schema applied successfully"
    
    # Verify tables
    TABLE_COUNT=$(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public';" | tr -d ' ')
    echo "       Tables created: $TABLE_COUNT"
else
    print_error "schema.sql not found at $SCRIPT_DIR/schema.sql"
    exit 1
fi

# -----------------------------------------------------------------------------
# Step 4: Install Python Dependencies
# -----------------------------------------------------------------------------
print_step "Installing Python dependencies..."

cd "$SCRIPT_DIR/quantum_service"

if [ -f "requirements.txt" ]; then
    # Try pip3 first, then pip
    if command -v pip3 &> /dev/null; then
        pip3 install -r requirements.txt --quiet 2>/dev/null && print_success "Python dependencies installed" || print_warning "Some Python dependencies failed to install"
    elif command -v pip &> /dev/null; then
        pip install -r requirements.txt --quiet 2>/dev/null && print_success "Python dependencies installed" || print_warning "Some Python dependencies failed to install"
    else
        print_warning "pip not found - please install Python dependencies manually"
    fi
else
    print_warning "requirements.txt not found"
fi

cd "$SCRIPT_DIR"

# -----------------------------------------------------------------------------
# Step 5: Validate Quantum Service
# -----------------------------------------------------------------------------
print_step "Validating quantum service..."

# Test the entropy generator
ENTROPY_TEST=$(python3 "$SCRIPT_DIR/quantum_service/entropy.py" '{"length": 4}' 2>&1)

if echo "$ENTROPY_TEST" | grep -q '"success": true'; then
    print_success "Quantum service is working"
    
    # Check for mock mode
    if echo "$ENTROPY_TEST" | grep -q '"mock_mode": true'; then
        print_warning "Running in MOCK MODE (liboqs/qiskit not installed)"
        echo "       To enable real PQC, install liboqs: https://github.com/open-quantum-safe/liboqs"
    else
        print_success "Running with REAL quantum libraries"
    fi
else
    print_error "Quantum service test failed"
    echo "       Output: $ENTROPY_TEST"
fi

# -----------------------------------------------------------------------------
# Step 6: Create local config (if not exists)
# -----------------------------------------------------------------------------
print_step "Checking configuration..."

LOCAL_CONFIG="$SCRIPT_DIR/backend/config/database.local.php"
if [ ! -f "$LOCAL_CONFIG" ]; then
    print_warning "No local database config found"
    echo "       Create $LOCAL_CONFIG to override default settings"
    echo "       Or set environment variables: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD"
fi

# =============================================================================
# Summary
# =============================================================================

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}Setup completed successfully!${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════${NC}"
echo ""
echo "Next steps:"
echo ""
echo "  1. Start the PHP development server:"
echo "     ${CYAN}php -S localhost:8000 -t public${NC}"
echo ""
echo "  2. Open in your browser:"
echo "     ${CYAN}http://localhost:8000${NC}"
echo ""
echo "  3. Run the stress test (optional):"
echo "     ${CYAN}php tests/stress_test.php${NC}"
echo ""
echo -e "${YELLOW}Note:${NC} If running in mock mode, the system uses simulated PQC."
echo "      For production, install liboqs for real quantum security."
echo ""
