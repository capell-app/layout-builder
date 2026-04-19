<?php

declare(strict_types=1);

namespace Capell\Plugins\Services;

use Capell\Plugins\Data\AnystackLicenseValidationData;
use Capell\Plugins\Enums\LicenseStatus;
use DateTimeImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AnystackClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://api.anystack.sh',
        private readonly ?string $apiKey = null,
        private readonly int $timeoutSeconds = 10,
    ) {}

    /**
     * Activate a license with anystack. Anystack's activate-key response only
     * contains activation metadata (id + license_id); it does NOT include
     * meta.valid, suspended, or expires_at — so we chain into validate-key to
     * resolve real status and expiry before returning. Doing the chain in the
     * service (option (a)) keeps callers oblivious to the two-step dance.
     */
    public function activateLicense(
        string $productId,
        string $licenseKey,
        string $fingerprint,
        ?string $hostname = null,
    ): AnystackLicenseValidationData {
        $url = "{$this->baseUrl}/v1/products/{$productId}/licenses/activate-key";

        $payload = [
            'key' => $licenseKey,
            'fingerprint' => $fingerprint,
        ];

        if ($hostname !== null) {
            $payload['hostname'] = $hostname;
        }

        $response = $this->pendingRequest()->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Anystack license activation failed with status {$response->status()}: {$response->body()}",
            );
        }

        $responseBody = $response->json();
        $data = is_array($responseBody) ? ($responseBody['data'] ?? null) : null;

        if (! is_array($data)) {
            throw new RuntimeException('Invalid response from Anystack: missing data object');
        }

        $activationId = isset($data['id']) && is_string($data['id']) ? $data['id'] : null;
        $licenseId = isset($data['license_id']) && is_string($data['license_id']) ? $data['license_id'] : null;

        // Chain into validate-key: activate alone cannot tell us if the license
        // is usable (meta.valid / suspended / expires_at come from validate).
        $validation = $this->validateLicense($productId, $licenseKey, $fingerprint);

        return new AnystackLicenseValidationData(
            valid: $validation->valid,
            status: $validation->status,
            licenseId: $licenseId ?? $validation->licenseId,
            activationId: $activationId,
            expiresAt: $validation->expiresAt,
            product: $productId,
            statusCode: $validation->statusCode,
            raw: $data,
        );
    }

    public function validateLicense(
        string $productId,
        string $licenseKey,
        ?string $fingerprint = null,
    ): AnystackLicenseValidationData {
        $url = "{$this->baseUrl}/v1/products/{$productId}/licenses/validate-key";

        $payload = [
            'key' => $licenseKey,
            'scope' => [],
        ];

        if ($fingerprint !== null) {
            $payload['scope']['fingerprint'] = $fingerprint;
        }

        $response = $this->pendingRequest()->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Anystack license validation failed with status {$response->status()}: {$response->body()}",
            );
        }

        $responseBody = $response->json();
        $data = is_array($responseBody) ? ($responseBody['data'] ?? null) : null;
        $meta = is_array($responseBody) ? ($responseBody['meta'] ?? []) : [];

        if (! is_array($data)) {
            throw new RuntimeException('Invalid response from Anystack: missing data object');
        }

        if (! is_array($meta)) {
            $meta = [];
        }

        $status = $this->mapStatus($meta, $data);
        $valid = $status === LicenseStatus::Active;
        $statusCode = isset($meta['status']) && is_string($meta['status']) ? $meta['status'] : null;
        $licenseId = isset($data['id']) && is_string($data['id']) ? $data['id'] : null;
        $expiresAtRaw = $data['expires_at'] ?? null;
        $expiresAt = is_string($expiresAtRaw) ? new DateTimeImmutable($expiresAtRaw) : null;

        return new AnystackLicenseValidationData(
            valid: $valid,
            status: $status,
            licenseId: $licenseId,
            activationId: null,
            expiresAt: $expiresAt,
            product: $productId,
            statusCode: $statusCode,
            raw: $data,
        );
    }

    public function deactivateLicense(
        string $productId,
        string $anystackLicenseId,
        string $anystackActivationId,
    ): bool {
        $url = "{$this->baseUrl}/v1/products/{$productId}/licenses/{$anystackLicenseId}/activations/{$anystackActivationId}";

        $response = $this->pendingRequest()->delete($url);

        if ($response->successful()) {
            return true;
        }

        if ($response->status() === 404) {
            return false;
        }

        throw new RuntimeException(
            "Anystack license deactivation failed with status {$response->status()}: {$response->body()}",
        );
    }

    public function composerRepositoryUrl(string $productId): string
    {
        return "https://{$productId}.composer.sh";
    }

    private function pendingRequest(): PendingRequest
    {
        $request = Http::timeout($this->timeoutSeconds)
            ->acceptJson()
            ->asJson();

        if ($this->apiKey !== null && $this->apiKey !== '') {
            $request = $request->withToken($this->apiKey);
        }

        return $request;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $data
     */
    private function mapStatus(array $meta, array $data): LicenseStatus
    {
        // A malformed response from anystack (no `meta.valid`) is a protocol
        // bug, not an expiration. Surface it so callers can see the failure
        // rather than silently marking the license expired.
        if (! array_key_exists('valid', $meta)) {
            throw new RuntimeException('Invalid response from Anystack: missing meta.valid field');
        }

        $valid = (bool) $meta['valid'];
        $statusCode = isset($meta['status']) && is_string($meta['status']) ? $meta['status'] : null;
        $suspended = isset($data['suspended']) && (bool) $data['suspended'];

        if ($valid) {
            if ($suspended) {
                return LicenseStatus::Revoked;
            }

            return LicenseStatus::Active;
        }

        if ($statusCode === null) {
            return LicenseStatus::Expired;
        }

        if ($statusCode === 'EXPIRED' || $statusCode === 'RESTRICTED') {
            return LicenseStatus::Expired;
        }

        if ($statusCode === 'SUSPENDED') {
            return LicenseStatus::Revoked;
        }

        if (str_starts_with($statusCode, 'FINGERPRINT_')) {
            return LicenseStatus::PastDue;
        }

        return LicenseStatus::Expired;
    }
}
