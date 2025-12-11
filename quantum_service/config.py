#!/usr/bin/env python3
# =============================================================================
# Quantum Service Configuration
# =============================================================================
# Handles detection of available quantum libraries and provides fallback
# configuration for mock mode when libraries are not installed.
# =============================================================================

import sys
import json
import hashlib
import secrets
from typing import Tuple, Optional

# =============================================================================
# LIBRARY DETECTION
# =============================================================================

# Detect if liboqs (Open Quantum Safe) is available
try:
    import oqs
    LIBOQS_AVAILABLE = True
except ImportError:
    LIBOQS_AVAILABLE = False

# Detect if Qiskit is available
try:
    from qiskit import QuantumCircuit
    from qiskit_aer import AerSimulator
    QISKIT_AVAILABLE = True
except ImportError:
    QISKIT_AVAILABLE = False

# Detect if PyCryptodome is available (required for AES)
try:
    from Crypto.Cipher import AES
    from Crypto.Random import get_random_bytes
    PYCRYPTODOME_AVAILABLE = True
except ImportError:
    PYCRYPTODOME_AVAILABLE = False


# =============================================================================
# CONFIGURATION
# =============================================================================

class QuantumConfig:
    """Configuration and utility class for quantum services."""
    
    # Algorithm names
    DILITHIUM_VARIANT = "Dilithium3"
    KYBER_VARIANT = "Kyber512"
    
    # Mock mode key (only used in mock mode - NOT SECURE for production)
    MOCK_HMAC_KEY = b"QUANTUM_MOCK_KEY_DO_NOT_USE_IN_PRODUCTION_12345"
    
    @classmethod
    def is_mock_mode(cls) -> bool:
        """Check if we're running in mock mode (liboqs not available)."""
        return not LIBOQS_AVAILABLE
    
    @classmethod
    def is_qrng_mock_mode(cls) -> bool:
        """Check if QRNG is running in mock mode (Qiskit not available)."""
        return not QISKIT_AVAILABLE
    
    @classmethod
    def get_status(cls) -> dict:
        """Get the status of all quantum libraries."""
        return {
            "liboqs_available": LIBOQS_AVAILABLE,
            "qiskit_available": QISKIT_AVAILABLE,
            "pycryptodome_available": PYCRYPTODOME_AVAILABLE,
            "pqc_mock_mode": cls.is_mock_mode(),
            "qrng_mock_mode": cls.is_qrng_mock_mode()
        }


# =============================================================================
# UTILITY FUNCTIONS
# =============================================================================

def bytes_to_hex(data: bytes) -> str:
    """Convert bytes to hexadecimal string."""
    return data.hex()


def hex_to_bytes(hex_string: str) -> bytes:
    """Convert hexadecimal string to bytes."""
    return bytes.fromhex(hex_string)


def json_output(data: dict) -> None:
    """Print JSON output to stdout (for PHP consumption)."""
    print(json.dumps(data))


def json_error(message: str, code: int = 1) -> None:
    """Print JSON error and exit."""
    print(json.dumps({
        "success": False,
        "error": message,
        "mock_mode": QuantumConfig.is_mock_mode()
    }))
    sys.exit(code)


def parse_json_input() -> dict:
    """Parse JSON input from stdin or command line argument."""
    # Try reading from stdin first
    if not sys.stdin.isatty():
        try:
            input_data = sys.stdin.read().strip()
            if input_data:
                return json.loads(input_data)
        except json.JSONDecodeError as e:
            json_error(f"Invalid JSON input from stdin: {e}")
    
    # Try command line argument
    if len(sys.argv) > 1:
        try:
            return json.loads(sys.argv[1])
        except json.JSONDecodeError as e:
            json_error(f"Invalid JSON argument: {e}")
    
    json_error("No input provided. Pass JSON via stdin or as argument.")
    return {}  # Never reached


def sha256_hash(data: str) -> str:
    """Compute SHA256 hash of a string."""
    return hashlib.sha256(data.encode('utf-8')).hexdigest()


def generate_secure_random(length: int = 32) -> bytes:
    """Generate cryptographically secure random bytes."""
    return secrets.token_bytes(length)


# =============================================================================
# MOCK MODE WARNING
# =============================================================================

MOCK_MODE_WARNING = """
================================================================================
WARNING: RUNNING IN MOCK MODE
================================================================================
The liboqs library is not installed. Cryptographic operations are simulated
using classical algorithms (HMAC-SHA512 for signatures, secrets module for
key generation). This is NOT quantum-secure and should NOT be used in
production environments.

To enable real Post-Quantum Cryptography:
1. Install liboqs: https://github.com/open-quantum-safe/liboqs
2. Install liboqs-python: pip install liboqs-python

================================================================================
"""

if __name__ == "__main__":
    # Print status when run directly
    status = QuantumConfig.get_status()
    print("Quantum Service Configuration Status")
    print("=" * 40)
    for key, value in status.items():
        print(f"  {key}: {value}")
    
    if QuantumConfig.is_mock_mode():
        print(MOCK_MODE_WARNING)
