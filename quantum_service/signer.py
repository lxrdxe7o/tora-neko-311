#!/usr/bin/env python3
# =============================================================================
# Dilithium3 Digital Signature Service
# =============================================================================
# Implements quantum-safe digital signatures using the Dilithium3 algorithm.
#
# DILITHIUM3 (NIST FIPS 204 / ML-DSA):
#   - A lattice-based digital signature scheme
#   - Provides EUF-CMA security against quantum attacks
#   - Security Level: NIST Level 3 (~128-bit classical, ~128-bit quantum)
#   - Based on Module Learning With Errors (MLWE) and Module Short Integer
#     Solution (MSIS) problems
#   - Signature size: ~3,293 bytes
#   - Public key size: ~1,952 bytes
#   - Private key size: ~4,000 bytes
#
# PURPOSE:
#   Every confirmed ticket is signed with Dilithium3, ensuring that even a
#   quantum computer cannot forge a valid ticket. This provides authenticity
#   and non-repudiation for the entire booking.
#
# REAL MODE (liboqs available):
#   Uses the Open Quantum Safe library's Dilithium3 implementation
#
# MOCK MODE (liboqs not available):
#   Uses HMAC-SHA512 as a signature simulation
#   WARNING: This is NOT quantum-secure
#
# Usage:
#   echo '{"data": "ticket data to sign"}' | python signer.py
#
# Output:
#   {"success": true, "signature": "hex...", "public_key": "hex...", ...}
# =============================================================================

import sys
import os
import hashlib
import hmac

# Add parent directory to path for imports
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from config import (
    QuantumConfig,
    json_output,
    json_error,
    parse_json_input,
    bytes_to_hex,
    hex_to_bytes,
    sha256_hash,
    LIBOQS_AVAILABLE
)


# =============================================================================
# REAL DILITHIUM3 IMPLEMENTATION (liboqs)
# =============================================================================

def sign_real(data: str) -> dict:
    """
    Sign data using real Dilithium3 from liboqs.
    
    Args:
        data: The string data to sign
    
    Returns:
        Dictionary with signature, public key, and metadata
    """
    import oqs
    
    # Initialize Dilithium3 signature scheme
    signer = oqs.Signature(QuantumConfig.DILITHIUM_VARIANT)
    
    # Generate keypair
    public_key = signer.generate_keypair()
    private_key = signer.export_secret_key()
    
    # Sign the data
    data_bytes = data.encode('utf-8')
    signature = signer.sign(data_bytes)
    
    # Compute hash of signed data for quick integrity check
    data_hash = sha256_hash(data)
    
    return {
        "success": True,
        "signature": bytes_to_hex(signature),
        "public_key": bytes_to_hex(public_key),
        "private_key": bytes_to_hex(private_key),  # For demo - protect in production!
        "data_hash": data_hash,
        "algorithm": "Dilithium3",
        "nist_level": 3,
        "mock_mode": False,
        "signature_size_bytes": len(signature),
        "public_key_size_bytes": len(public_key),
        "description": "Signed using NIST FIPS 204 (ML-DSA) Dilithium3 lattice-based signature"
    }


def verify_real(data: str, signature_hex: str, public_key_hex: str) -> dict:
    """
    Verify a Dilithium3 signature.
    
    Args:
        data: The original signed data
        signature_hex: The signature in hex format
        public_key_hex: The public key in hex format
    
    Returns:
        Dictionary with verification result
    """
    import oqs
    
    # Initialize verifier with the public key
    verifier = oqs.Signature(QuantumConfig.DILITHIUM_VARIANT)
    
    # Convert from hex
    signature = hex_to_bytes(signature_hex)
    public_key = hex_to_bytes(public_key_hex)
    data_bytes = data.encode('utf-8')
    
    # Verify
    is_valid = verifier.verify(data_bytes, signature, public_key)
    
    return {
        "success": True,
        "valid": is_valid,
        "algorithm": "Dilithium3",
        "mock_mode": False,
        "description": "Verification using NIST FIPS 204 (ML-DSA) Dilithium3"
    }


# =============================================================================
# MOCK DILITHIUM3 IMPLEMENTATION
# =============================================================================

def sign_mock(data: str) -> dict:
    """
    Mock Dilithium3 signature using HMAC-SHA512.
    
    WARNING: This is NOT quantum-secure and should only be used for
    demonstration when liboqs is not available.
    
    This simulates the signature process but provides no quantum resistance.
    """
    data_bytes = data.encode('utf-8')
    
    # Simulate keypair (just random bytes of appropriate size)
    # Real Dilithium3: public key ~1952 bytes, private key ~4000 bytes
    import secrets
    mock_private_key = secrets.token_bytes(4000)
    mock_public_key = secrets.token_bytes(1952)
    
    # Create "signature" using HMAC-SHA512
    # This is deterministic based on data and "private key"
    # We use a fixed key for reproducibility in mock mode
    signature = hmac.new(
        QuantumConfig.MOCK_HMAC_KEY,
        data_bytes,
        hashlib.sha512
    ).digest()
    
    # Pad to simulate Dilithium3 signature size (~3293 bytes)
    # Add hash of public key to make it look more realistic
    extended_sig = signature + hashlib.sha512(mock_public_key).digest()
    mock_signature = extended_sig * 26  # Approximately 3328 bytes
    mock_signature = mock_signature[:3293]  # Trim to exact size
    
    # Compute hash of signed data
    data_hash = sha256_hash(data)
    
    return {
        "success": True,
        "signature": bytes_to_hex(mock_signature),
        "public_key": bytes_to_hex(mock_public_key),
        "private_key": bytes_to_hex(mock_private_key),
        "data_hash": data_hash,
        "algorithm": "Mock-Dilithium3-HMAC",
        "nist_level": "N/A (Mock)",
        "mock_mode": True,
        "warning": "MOCK MODE - Not quantum secure! Install liboqs for real PQC.",
        "signature_size_bytes": len(mock_signature),
        "public_key_size_bytes": len(mock_public_key),
        "description": "Simulated Dilithium3 using HMAC-SHA512 (liboqs not available)"
    }


def verify_mock(data: str, signature_hex: str, public_key_hex: str) -> dict:
    """
    Mock signature verification.
    
    In mock mode, we regenerate the HMAC and compare the first 64 bytes
    (the actual HMAC portion of our mock signature).
    """
    data_bytes = data.encode('utf-8')
    signature = hex_to_bytes(signature_hex)
    
    # Regenerate the expected HMAC
    expected_hmac = hmac.new(
        QuantumConfig.MOCK_HMAC_KEY,
        data_bytes,
        hashlib.sha512
    ).digest()
    
    # Compare first 64 bytes (the HMAC portion)
    is_valid = hmac.compare_digest(signature[:64], expected_hmac)
    
    return {
        "success": True,
        "valid": is_valid,
        "algorithm": "Mock-Dilithium3-HMAC",
        "mock_mode": True,
        "warning": "MOCK MODE verification - Not quantum secure!",
        "description": "Mock verification using HMAC-SHA512 comparison"
    }


# =============================================================================
# MAIN SIGNING FUNCTIONS
# =============================================================================

def sign(data: str) -> dict:
    """
    Sign data using Dilithium3 (or mock equivalent).
    
    Args:
        data: The string data to sign
    
    Returns:
        Dictionary containing signature and all metadata
    """
    if not data:
        json_error("Data to sign cannot be empty")
    
    if LIBOQS_AVAILABLE:
        return sign_real(data)
    else:
        return sign_mock(data)


def verify(data: str, signature_hex: str, public_key_hex: str) -> dict:
    """
    Verify a signature.
    
    Args:
        data: The original signed data
        signature_hex: The signature in hex format
        public_key_hex: The public key in hex format
    
    Returns:
        Dictionary with verification result
    """
    if not data or not signature_hex or not public_key_hex:
        json_error("Missing required fields for verification")
    
    if LIBOQS_AVAILABLE:
        return verify_real(data, signature_hex, public_key_hex)
    else:
        return verify_mock(data, signature_hex, public_key_hex)


# =============================================================================
# MAIN ENTRY POINT
# =============================================================================

def main():
    """Main entry point for CLI usage."""
    try:
        # Parse input
        input_data = parse_json_input()
        
        # Check if this is a verification request
        if "verify" in input_data and input_data["verify"]:
            data = input_data.get("data", "")
            signature = input_data.get("signature", "")
            public_key = input_data.get("public_key", "")
            result = verify(data, signature, public_key)
        else:
            # Signing request
            data = input_data.get("data", "")
            if not data:
                json_error("Missing required field: data")
            result = sign(data)
        
        json_output(result)
        
    except Exception as e:
        json_error(f"Signature operation failed: {str(e)}")


if __name__ == "__main__":
    main()
