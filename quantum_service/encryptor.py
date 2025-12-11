#!/usr/bin/env python3
# =============================================================================
# Kyber512 Hybrid Encryption Service
# =============================================================================
# Implements quantum-safe hybrid encryption using Kyber512 Key Encapsulation
# Mechanism (KEM) combined with AES-256-GCM symmetric encryption.
#
# KYBER512 (NIST FIPS 203 / ML-KEM):
#   - A lattice-based key encapsulation mechanism
#   - Provides IND-CCA2 security against quantum attacks
#   - Security Level: NIST Level 1 (~128-bit classical and quantum)
#   - Based on Module Learning With Errors (MLWE) problem
#
# WORKFLOW:
#   1. Generate Kyber512 keypair
#   2. Encapsulate to produce shared secret + ciphertext
#   3. Derive AES-256 key from shared secret using HKDF
#   4. Encrypt plaintext with AES-256-GCM
#   5. Return: encrypted data, encapsulated key, nonce
#
# REAL MODE (liboqs available):
#   Uses the Open Quantum Safe library's Kyber512 implementation
#
# MOCK MODE (liboqs not available):
#   Simulates the process using secrets module and standard AES
#   WARNING: This is NOT quantum-secure
#
# Usage:
#   echo '{"plaintext": "AB1234567"}' | python encryptor.py
#
# Output:
#   {"success": true, "ciphertext": "base64...", "encapsulated_key": "base64...", ...}
# =============================================================================

import sys
import os
import base64
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
    generate_secure_random,
    LIBOQS_AVAILABLE,
    PYCRYPTODOME_AVAILABLE
)

import secrets

# =============================================================================
# AES-256-GCM ENCRYPTION (Common to both modes)
# =============================================================================

if PYCRYPTODOME_AVAILABLE:
    from Crypto.Cipher import AES
    from Crypto.Random import get_random_bytes
    
    def aes_encrypt(key: bytes, plaintext: bytes) -> tuple:
        """
        Encrypt plaintext using AES-256-GCM.
        
        Returns:
            Tuple of (ciphertext, nonce, tag)
        """
        nonce = get_random_bytes(12)  # 96-bit nonce for GCM
        cipher = AES.new(key, AES.MODE_GCM, nonce=nonce)
        ciphertext, tag = cipher.encrypt_and_digest(plaintext)
        # Append tag to ciphertext
        return ciphertext + tag, nonce
    
    def aes_decrypt(key: bytes, ciphertext_with_tag: bytes, nonce: bytes) -> bytes:
        """
        Decrypt ciphertext using AES-256-GCM.
        """
        # Split ciphertext and tag
        ciphertext = ciphertext_with_tag[:-16]
        tag = ciphertext_with_tag[-16:]
        cipher = AES.new(key, AES.MODE_GCM, nonce=nonce)
        plaintext = cipher.decrypt_and_verify(ciphertext, tag)
        return plaintext
else:
    # Fallback: No encryption available
    def aes_encrypt(key: bytes, plaintext: bytes) -> tuple:
        json_error("PyCryptodome is required for AES encryption. Install with: pip install pycryptodome")
        return b"", b""
    
    def aes_decrypt(key: bytes, ciphertext_with_tag: bytes, nonce: bytes) -> bytes:
        json_error("PyCryptodome is required for AES decryption. Install with: pip install pycryptodome")
        return b""


def hkdf_derive_key(shared_secret: bytes, info: bytes = b"quantum-airline-aes-key", length: int = 32) -> bytes:
    """
    Derive an AES key from a shared secret using HKDF-like construction.
    
    This is a simplified HKDF using HMAC-SHA256.
    For production, use a proper HKDF implementation.
    """
    # Extract phase
    prk = hmac.new(b"quantum-salt", shared_secret, hashlib.sha256).digest()
    # Expand phase
    okm = hmac.new(prk, info + b"\x01", hashlib.sha256).digest()
    return okm[:length]


# =============================================================================
# REAL KYBER512 IMPLEMENTATION (liboqs)
# =============================================================================

def kyber_encrypt_real(plaintext: bytes) -> dict:
    """
    Encrypt using real Kyber512 KEM from liboqs.
    
    Workflow:
    1. Generate Kyber512 keypair
    2. Encapsulate to get shared secret
    3. Derive AES key from shared secret
    4. Encrypt plaintext with AES-256-GCM
    """
    import oqs
    
    # Initialize Kyber512 KEM
    kem = oqs.KeyEncapsulation(QuantumConfig.KYBER_VARIANT)
    
    # Generate keypair
    public_key = kem.generate_keypair()
    private_key = kem.export_secret_key()
    
    # Encapsulate: produces shared secret and ciphertext (encapsulated key)
    encapsulated_key, shared_secret = kem.encap_secret(public_key)
    
    # Derive AES-256 key from the shared secret
    aes_key = hkdf_derive_key(shared_secret)
    
    # Encrypt the plaintext with AES-256-GCM
    ciphertext, nonce = aes_encrypt(aes_key, plaintext)
    
    return {
        "success": True,
        "ciphertext": base64.b64encode(ciphertext).decode('utf-8'),
        "encapsulated_key": base64.b64encode(encapsulated_key).decode('utf-8'),
        "nonce": base64.b64encode(nonce).decode('utf-8'),
        "public_key": bytes_to_hex(public_key),
        "private_key": bytes_to_hex(private_key),  # For demo - store securely in production!
        "algorithm": "Kyber512-AES256GCM",
        "mock_mode": False,
        "description": "Encrypted using NIST FIPS 203 (ML-KEM) Kyber512 key encapsulation with AES-256-GCM"
    }


# =============================================================================
# MOCK KYBER512 IMPLEMENTATION
# =============================================================================

def kyber_encrypt_mock(plaintext: bytes) -> dict:
    """
    Mock Kyber512 encryption using classical cryptography.
    
    WARNING: This is NOT quantum-secure and should only be used for
    demonstration when liboqs is not available.
    
    This simulates the Kyber workflow using:
    - Random bytes for "keypair"
    - Random bytes for "shared secret"
    - Real AES-256-GCM for actual encryption
    """
    # Simulate keypair generation
    # Real Kyber512 public key is 800 bytes, private key is 1632 bytes
    mock_public_key = secrets.token_bytes(800)
    mock_private_key = secrets.token_bytes(1632)
    
    # Simulate encapsulation
    # Real Kyber512 ciphertext is 768 bytes
    mock_encapsulated_key = secrets.token_bytes(768)
    mock_shared_secret = secrets.token_bytes(32)
    
    # Derive AES key (same process as real mode)
    aes_key = hkdf_derive_key(mock_shared_secret)
    
    # Encrypt with AES-256-GCM (this part is real)
    ciphertext, nonce = aes_encrypt(aes_key, plaintext)
    
    # Store the mock shared secret in a way that allows decryption
    # In mock mode, we embed it in the "encapsulated key" (NOT SECURE)
    # This is purely for demonstration purposes
    mock_encapsulated_data = mock_shared_secret + mock_encapsulated_key[32:]
    
    return {
        "success": True,
        "ciphertext": base64.b64encode(ciphertext).decode('utf-8'),
        "encapsulated_key": base64.b64encode(mock_encapsulated_data).decode('utf-8'),
        "nonce": base64.b64encode(nonce).decode('utf-8'),
        "public_key": bytes_to_hex(mock_public_key),
        "private_key": bytes_to_hex(mock_private_key),
        "algorithm": "Mock-Kyber512-AES256GCM",
        "mock_mode": True,
        "warning": "MOCK MODE - Not quantum secure! Install liboqs for real PQC.",
        "description": "Simulated Kyber512 (liboqs not available) with real AES-256-GCM encryption"
    }


# =============================================================================
# MAIN ENCRYPTION FUNCTION
# =============================================================================

def encrypt(plaintext: str) -> dict:
    """
    Encrypt plaintext using Kyber512 hybrid encryption.
    
    Args:
        plaintext: The string to encrypt (e.g., passport number)
    
    Returns:
        Dictionary containing ciphertext and all necessary decryption materials
    """
    if not plaintext:
        json_error("Plaintext cannot be empty")
    
    plaintext_bytes = plaintext.encode('utf-8')
    
    if LIBOQS_AVAILABLE:
        return kyber_encrypt_real(plaintext_bytes)
    else:
        return kyber_encrypt_mock(plaintext_bytes)


# =============================================================================
# MAIN ENTRY POINT
# =============================================================================

def main():
    """Main entry point for CLI usage."""
    try:
        # Parse input
        input_data = parse_json_input()
        plaintext = input_data.get("plaintext", "")
        
        if not plaintext:
            json_error("Missing required field: plaintext")
        
        # Encrypt and output
        result = encrypt(plaintext)
        json_output(result)
        
    except Exception as e:
        json_error(f"Encryption failed: {str(e)}")


if __name__ == "__main__":
    main()
