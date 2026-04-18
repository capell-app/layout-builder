<?php

declare(strict_types=1);

namespace Capell\Plugins\Services;

use Capell\Plugins\Data\AnystackLicenseValidationData;
use Capell\Plugins\Enums\LicenseStatus;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AnystackClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://api.anystack.sh',
        private readonly int $timeoutSeconds = 10,
    ) {}

    public function validateLicense(
        string $productId,
        string $licenseKey,
        ?string $siteFingerprint = null,
    ): AnystackLicenseValidationData {
        $url = "{$this->baseUrl}/v1/products/{$productId}/licenses/validate-key";

        $payload = [
            'key' => $licenseKey,
            'scope' => [],
        ];

        if ($siteFingerprint !== null) {
            $payload['scope']['fingerprint'] = $siteFingerprint;
        }

        $response = Http::timeout($this->timeoutSeconds)->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Anystack license validation failed with status {$response->status()}: {$response->body()}",
            );
        }

        $responseData = $response->json();
        $data = $responseData['data'] ?? null;

        if (! is_array($data)) {
            throw new RuntimeException('Invalid response from Anystack: missing data object');
        }

        $status = $this->determineStatus(
            $data['suspended'] ?? false,
            $data['expires_at'] ?? null,
        );

        return new AnystackLicenseValidationData(
            valid: $status->isUsable(),
            status: $status,
            expiresAt: $data['expires_at'] ? new DateTimeImmutable($data['expires_at']) : null,
            product: $productId,
            raw: $data,
        );
    }

    public function composerRepositoryUrl(string $vendor): string
    {
        return "{$this->baseUrl}/composer/{$vendor}";
    }

    private function determineStatus(bool $suspended, ?string $expiresAt): LicenseStatus
    {
        if ($suspended) {
            return LicenseStatus::Revoked;
        }

        if ($expiresAt === null) {
            return LicenseStatus::Active;
        }

        $expireDate = new DateTimeImmutable($expiresAt);
        $now = new DateTimeImmutable;

        if ($expireDate <= $now) {
            return LicenseStatus::Expired;
        }

        return LicenseStatus::Active;
    }
}
