#!/usr/bin/env python3
# =============================================================================
# Kyber512 Hybrid Decryption Service
# =============================================================================
# Decrypts data that was encrypted using the Kyber512 hybrid encryption scheme.
#
# WORKFLOW:
#   1. Receive: ciphertext, encapsulated key, nonce, private key
#   2. Decapsulate using Kyber512 private key to recover shared secret
#   3. Derive AES-256 key from shared secret using HKDF
#   4. Decrypt ciphertext with AES-256-GCM
#   5. Return: plaintext
#
# Usage:
#   echo '{"ciphertext": "...", "encapsulated_key": "...", "nonce": "...", "private_key": "..."}' | python decryptor.py
#
# Output:
#   {"success": true, "plaintext": "decrypted data", ...}
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
    LIBOQS_AVAILABLE,
    PYCRYPTODOME_AVAILABLE
)


# =============================================================================
# AES-256-GCM DECRYPTION
# =============================================================================

if PYCRYPTODOME_AVAILABLE:
    from Crypto.Cipher import AES
    
    def aes_decrypt(key: bytes, ciphertext_with_tag: bytes, nonce: bytes) -> bytes:
        """
        Decrypt ciphertext using AES-256-GCM.
        """
        # Split ciphertext and tag (tag is last 16 bytes)
        ciphertext = ciphertext_with_tag[:-16]
        tag = ciphertext_with_tag[-16:]
        cipher = AES.new(key, AES.MODE_GCM, nonce=nonce)
        plaintext = cipher.decrypt_and_verify(ciphertext, tag)
        return plaintext
else:
    def aes_decrypt(key: bytes, ciphertext_with_tag: bytes, nonce: bytes) -> bytes:
        json_error("PyCryptodome is required for AES decryption. Install with: pip install pycryptodome")
        return b""


def hkdf_derive_key(shared_secret: bytes, info: bytes = b"quantum-airline-aes-key", length: int = 32) -> bytes:
    """
    Derive an AES key from a shared secret using HKDF-like construction.
    Must match the derivation in encryptor.py.
    """
    # Extract phase
    prk = hmac.new(b"quantum-salt", shared_secret, hashlib.sha256).digest()
    # Expand phase
    okm = hmac.new(prk, info + b"\x01", hashlib.sha256).digest()
    return okm[:length]


# =============================================================================
# REAL KYBER512 DECRYPTION (liboqs)
# =============================================================================

def kyber_decrypt_real(
    ciphertext_b64: str,
    encapsulated_key_b64: str,
    nonce_b64: str,
    private_key_hex: str
) -> dict:
    """
    Decrypt using real Kyber512 KEM from liboqs.
    
    Args:
        ciphertext_b64: Base64-encoded AES ciphertext
        encapsulated_key_b64: Base64-encoded Kyber encapsulated key
        nonce_b64: Base64-encoded AES nonce
        private_key_hex: Hex-encoded Kyber private key
    
    Returns:
        Dictionary with decrypted plaintext
    """
    import oqs
    
    # Decode inputs
    ciphertext = base64.b64decode(ciphertext_b64)
    encapsulated_key = base64.b64decode(encapsulated_key_b64)
    nonce = base64.b64decode(nonce_b64)
    private_key = hex_to_bytes(private_key_hex)
    
    # Initialize Kyber512 KEM
    kem = oqs.KeyEncapsulation(QuantumConfig.KYBER_VARIANT, secret_key=private_key)
    
    # Decapsulate to recover shared secret
    shared_secret = kem.decap_secret(encapsulated_key)
    
    # Derive AES-256 key from the shared secret
    aes_key = hkdf_derive_key(shared_secret)
    
    # Decrypt the ciphertext with AES-256-GCM
    plaintext = aes_decrypt(aes_key, ciphertext, nonce)
    
    return {
        "success": True,
        "plaintext": plaintext.decode('utf-8'),
        "algorithm": "Kyber512-AES256GCM",
        "mock_mode": False,
        "description": "Decrypted using NIST FIPS 203 (ML-KEM) Kyber512 key decapsulation"
    }


# =============================================================================
# MOCK KYBER512 DECRYPTION
# =============================================================================

def kyber_decrypt_mock(
    ciphertext_b64: str,
    encapsulated_key_b64: str,
    nonce_b64: str,
    private_key_hex: str
) -> dict:
    """
    Mock Kyber512 decryption.
    
    In mock mode, the shared secret is embedded in the first 32 bytes
    of the encapsulated key (as stored by encryptor.py mock mode).
    """
    # Decode inputs
    ciphertext = base64.b64decode(ciphertext_b64)
    encapsulated_data = base64.b64decode(encapsulated_key_b64)
    nonce = base64.b64decode(nonce_b64)
    
    # Extract the mock shared secret (first 32 bytes of encapsulated data)
    mock_shared_secret = encapsulated_data[:32]
    
    # Derive AES key (same process as encryption)
    aes_key = hkdf_derive_key(mock_shared_secret)
    
    # Decrypt with AES-256-GCM
    plaintext = aes_decrypt(aes_key, ciphertext, nonce)
    
    return {
        "success": True,
        "plaintext": plaintext.decode('utf-8'),
        "algorithm": "Mock-Kyber512-AES256GCM",
        "mock_mode": True,
        "warning": "MOCK MODE decryption - Not quantum secure!",
        "description": "Mock decryption (liboqs not available)"
    }


# =============================================================================
# MAIN DECRYPTION FUNCTION
# =============================================================================

def decrypt(
    ciphertext_b64: str,
    encapsulated_key_b64: str,
    nonce_b64: str,
    private_key_hex: str
) -> dict:
    """
    Decrypt data encrypted with Kyber512 hybrid encryption.
    
    Args:
        ciphertext_b64: Base64-encoded AES ciphertext
        encapsulated_key_b64: Base64-encoded Kyber encapsulated key
        nonce_b64: Base64-encoded AES nonce
        private_key_hex: Hex-encoded Kyber private key
    
    Returns:
        Dictionary containing decrypted plaintext
    """
    if LIBOQS_AVAILABLE:
        return kyber_decrypt_real(
            ciphertext_b64, encapsulated_key_b64, nonce_b64, private_key_hex
        )
    else:
        return kyber_decrypt_mock(
            ciphertext_b64, encapsulated_key_b64, nonce_b64, private_key_hex
        )


# =============================================================================
# MAIN ENTRY POINT
# =============================================================================

def main():
    """Main entry point for CLI usage."""
    try:
        # Parse input
        input_data = parse_json_input()
        
        # Extract required fields
        ciphertext = input_data.get("ciphertext", "")
        encapsulated_key = input_data.get("encapsulated_key", "")
        nonce = input_data.get("nonce", "")
        private_key = input_data.get("private_key", "")
        
        # Validate
        if not all([ciphertext, encapsulated_key, nonce, private_key]):
            json_error("Missing required fields: ciphertext, encapsulated_key, nonce, private_key")
        
        # Decrypt and output
        result = decrypt(ciphertext, encapsulated_key, nonce, private_key)
        json_output(result)
        
    except ValueError as e:
        json_error(f"Decryption failed - integrity check failed or invalid data: {str(e)}")
    except Exception as e:
        json_error(f"Decryption failed: {str(e)}")


if __name__ == "__main__":
    main()
