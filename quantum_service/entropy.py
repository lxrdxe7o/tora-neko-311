#!/usr/bin/env python3
# =============================================================================
# Quantum Random Number Generator (QRNG) Service
# =============================================================================
# Generates quantum-grade random strings for booking reference IDs.
#
# REAL MODE (Qiskit available):
#   Uses a quantum circuit with Hadamard gates to create superposition,
#   then measures the qubits to collapse them into random classical bits.
#   This simulates true quantum randomness.
#
# MOCK MODE (Qiskit not available):
#   Uses Python's `secrets` module which provides cryptographically secure
#   pseudo-random numbers suitable for security-sensitive applications.
#
# Usage:
#   echo '{"length": 8}' | python entropy.py
#   python entropy.py '{"length": 8}'
#
# Output:
#   {"success": true, "random_id": "QX7A9B2C", "method": "hadamard_simulation", ...}
# =============================================================================

import sys
import os

# Add parent directory to path for imports
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from config import (
    QuantumConfig,
    json_output,
    json_error,
    parse_json_input,
    QISKIT_AVAILABLE
)

import secrets
import string

# =============================================================================
# CONSTANTS
# =============================================================================

# Character set for booking references (uppercase + digits, excluding confusing chars)
BOOKING_CHARSET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"  # No I, O, 0, 1

# Default booking reference length
DEFAULT_LENGTH = 8

# Maximum length to prevent abuse
MAX_LENGTH = 32


# =============================================================================
# REAL QRNG IMPLEMENTATION (Qiskit)
# =============================================================================

def generate_qrng_real(num_bits: int) -> str:
    """
    Generate random bits using a quantum circuit simulation.
    
    This creates a quantum circuit where each qubit is put into superposition
    using a Hadamard gate, then measured. The measurement collapses the
    superposition, yielding a truly random classical bit.
    
    In a real quantum computer, this randomness comes from the fundamental
    nature of quantum mechanics. In simulation, it's pseudo-random but
    demonstrates the concept.
    """
    from qiskit import QuantumCircuit
    from qiskit_aer import AerSimulator
    
    # Create a quantum circuit with num_bits qubits and classical bits
    qc = QuantumCircuit(num_bits, num_bits)
    
    # Apply Hadamard gate to each qubit
    # H|0⟩ = (|0⟩ + |1⟩) / √2 (equal superposition)
    for i in range(num_bits):
        qc.h(i)
    
    # Measure all qubits
    qc.measure(range(num_bits), range(num_bits))
    
    # Execute on the Aer simulator
    simulator = AerSimulator()
    job = simulator.run(qc, shots=1)
    result = job.result()
    
    # Get the measurement result (a binary string)
    counts = result.get_counts()
    random_bits = list(counts.keys())[0]
    
    return random_bits


def bits_to_booking_ref(bits: str, charset: str, length: int) -> str:
    """Convert binary string to booking reference using the charset."""
    # Convert bits to integer
    num = int(bits, 2)
    
    # Convert to base-N where N is the charset size
    result = []
    charset_size = len(charset)
    
    while len(result) < length:
        result.append(charset[num % charset_size])
        num //= charset_size
        # If we run out of entropy, add more
        if num == 0 and len(result) < length:
            num = secrets.randbelow(charset_size ** (length - len(result)))
    
    return ''.join(result)


# =============================================================================
# MOCK QRNG IMPLEMENTATION
# =============================================================================

def generate_qrng_mock(length: int) -> str:
    """
    Generate a random booking reference using cryptographically secure PRNG.
    
    Uses Python's `secrets` module which is suitable for generating
    cryptographic tokens and passwords.
    """
    return ''.join(secrets.choice(BOOKING_CHARSET) for _ in range(length))


# =============================================================================
# MAIN ENTRY POINT
# =============================================================================

def generate_booking_reference(length: int = DEFAULT_LENGTH) -> dict:
    """
    Generate a random booking reference ID.
    
    Args:
        length: Length of the booking reference (default 8)
    
    Returns:
        Dictionary containing the random ID and metadata
    """
    # Validate length
    if length < 4:
        length = 4
    elif length > MAX_LENGTH:
        length = MAX_LENGTH
    
    mock_mode = not QISKIT_AVAILABLE
    
    if mock_mode:
        # Mock mode: use secrets module
        random_id = generate_qrng_mock(length)
        method = "secrets_prng"
    else:
        # Real mode: use quantum circuit simulation
        # We need more bits than characters due to the conversion
        num_bits = length * 6  # ~6 bits per character for our charset
        random_bits = generate_qrng_real(num_bits)
        random_id = bits_to_booking_ref(random_bits, BOOKING_CHARSET, length)
        method = "hadamard_simulation"
    
    return {
        "success": True,
        "random_id": random_id,
        "length": len(random_id),
        "method": method,
        "mock_mode": mock_mode,
        "algorithm": "QRNG-Hadamard" if not mock_mode else "CSPRNG-Secrets",
        "description": (
            "Generated using quantum Hadamard gate measurements"
            if not mock_mode else
            "Generated using cryptographically secure PRNG (Qiskit not available)"
        )
    }


def main():
    """Main entry point for CLI usage."""
    try:
        # Parse input
        input_data = parse_json_input()
        length = input_data.get("length", DEFAULT_LENGTH)
        
        # Validate
        if not isinstance(length, int):
            try:
                length = int(length)
            except (ValueError, TypeError):
                length = DEFAULT_LENGTH
        
        # Generate and output
        result = generate_booking_reference(length)
        json_output(result)
        
    except Exception as e:
        json_error(f"QRNG generation failed: {str(e)}")


if __name__ == "__main__":
    main()
