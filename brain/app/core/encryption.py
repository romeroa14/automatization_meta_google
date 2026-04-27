"""Encryption utilities for API keys and sensitive data"""
import os
import base64
from cryptography.fernet import Fernet


def _get_cipher() -> Fernet:
    """Get Fernet cipher from encryption key"""
    key = os.getenv("ENCRYPTION_KEY", "")
    if not key:
        # Use a default dummy key for development
        key = "development-key-please-change-in-production"
    
    # Ensure key is valid Base64
    if len(key) < 32:
        key = key.ljust(32, '0')
    
    # Convert to Base64
    key_bytes = key.encode()[:32]
    key_base64 = base64.urlsafe_b64encode(key_bytes.ljust(32, b'0'))
    
    return Fernet(key_base64)


def encrypt(plaintext: str) -> str:
    """Encrypt a plaintext string"""
    if not plaintext:
        return ""
    cipher = _get_cipher()
    return cipher.encrypt(plaintext.encode()).decode()


def decrypt(ciphertext: str) -> str:
    """Decrypt a ciphertext string"""
    if not ciphertext:
        return ""
    cipher = _get_cipher()
    return cipher.decrypt(ciphertext.encode()).decode()


def generate_encryption_key() -> str:
    """Generate a new encryption key"""
    return Fernet.generate_key().decode()