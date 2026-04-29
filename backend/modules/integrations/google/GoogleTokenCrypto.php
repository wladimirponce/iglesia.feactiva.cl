<?php

declare(strict_types=1);

final class GoogleTokenCrypto
{
    private const CIPHER = 'aes-256-gcm';

    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!function_exists('openssl_encrypt')) {
            throw new RuntimeException('GOOGLE_TOKEN_CRYPTO_UNAVAILABLE');
        }

        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($value, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('GOOGLE_TOKEN_ENCRYPT_FAILED');
        }

        return base64_encode(json_encode([
            'v' => 1,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'value' => base64_encode($ciphertext),
        ], JSON_UNESCAPED_SLASHES) ?: '');
    }

    public function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $decoded = json_decode(base64_decode($payload, true) ?: '', true);
        if (!is_array($decoded)) {
            throw new RuntimeException('GOOGLE_TOKEN_INVALID_PAYLOAD');
        }

        $plaintext = openssl_decrypt(
            base64_decode((string) ($decoded['value'] ?? ''), true) ?: '',
            self::CIPHER,
            $this->key(),
            OPENSSL_RAW_DATA,
            base64_decode((string) ($decoded['iv'] ?? ''), true) ?: '',
            base64_decode((string) ($decoded['tag'] ?? ''), true) ?: ''
        );

        if ($plaintext === false) {
            throw new RuntimeException('GOOGLE_TOKEN_DECRYPT_FAILED');
        }

        return $plaintext;
    }

    private function key(): string
    {
        $secret = (string) (env('GOOGLE_TOKEN_ENCRYPTION_KEY') ?: env('APP_KEY') ?: env('JWT_SECRET') ?: '');
        if ($secret === '') {
            throw new RuntimeException('GOOGLE_TOKEN_ENCRYPTION_KEY_MISSING');
        }

        return hash('sha256', $secret, true);
    }
}
