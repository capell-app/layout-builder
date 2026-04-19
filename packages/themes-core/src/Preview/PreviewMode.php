<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Preview;

use Illuminate\Support\Facades\Date;

class PreviewMode
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $tokenParam = 'preview_token',
    ) {}

    public function generateToken(string $path, int $expiresInMinutes = 60): string
    {
        $issuedAt = Date::now()->getTimestamp();
        $expiresAt = $issuedAt + ($expiresInMinutes * 60);

        $payload = json_encode([
            'path' => $path,
            'exp' => $expiresAt,
            'iat' => $issuedAt,
        ]);

        $encodedPayload = base64_encode((string) $payload);
        $signature = hash_hmac('sha256', (string) $payload, $this->secretKey);

        return $encodedPayload . '.' . $signature;
    }

    public function signedUrl(string $path, string $baseUrl, int $expiresInMinutes = 60): string
    {
        $token = $this->generateToken($path, $expiresInMinutes);

        return rtrim($baseUrl, '/') . $path . '?' . $this->tokenParam . '=' . urlencode($token);
    }

    public function validateToken(string $token, string $path): bool
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return false;
        }

        [$encodedPayload, $signature] = $parts;
        $payload = base64_decode($encodedPayload, true);

        if ($payload === false) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);

        if (! hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $data = json_decode($payload, true);

        if (! is_array($data)) {
            return false;
        }

        if (($data['path'] ?? null) !== $path) {
            return false;
        }

        return ! (($data['exp'] ?? 0) < Date::now()->getTimestamp());
    }

    public function isExpired(string $token): bool
    {
        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return true;
        }

        [$encodedPayload] = $parts;
        $payload = base64_decode($encodedPayload, true);

        if ($payload === false) {
            return true;
        }

        $data = json_decode($payload, true);

        if (! is_array($data)) {
            return true;
        }

        return ($data['exp'] ?? 0) < Date::now()->getTimestamp();
    }

    public function tokenParam(): string
    {
        return $this->tokenParam;
    }
}
