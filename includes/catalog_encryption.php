<?php

function getEncryptionKey(): string
{
    $keyFile = __DIR__ . '/../config/encryption.key';
    if (!file_exists($keyFile)) {
        $key = random_bytes(32);
        file_put_contents($keyFile, $key, LOCK_EX);
        @chmod($keyFile, 0600);
    } else {
        $key = file_get_contents($keyFile);
    }
    return $key;
}

function encryptPassword(string $plain): string
{
    $key = getEncryptionKey();
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed: ' . openssl_error_string());
    }
    return base64_encode($iv . $ciphertext);
}

function decryptPassword(string $encrypted): string
{
    $key = getEncryptionKey();
    $data = base64_decode($encrypted, true);
    if ($data === false || strlen($data) < 16) {
        throw new RuntimeException('Decryption failed: invalid data');
    }
    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);
    $plain = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($plain === false) {
        throw new RuntimeException('Decryption failed: ' . openssl_error_string());
    }
    return $plain;
}
