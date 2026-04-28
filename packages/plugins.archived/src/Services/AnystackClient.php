<?php

declare(strict_types=1);

namespace Capell\Plugins\Services;

use Capell\Plugins\Data\AnystackLicenseValidationData;
use Capell\Plugins\Enums\LicenseStatus;
use DateTimeImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class AnystackClient
{
    public function __construct(
        private string $baseUrl = 'https://api.anystack.sh',
        private ?string $apiKey = null,
        private int $timeoutSeconds = 10,
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
        $url = sprintf('%s/v1/products/%s/licenses/activate-key', $this->baseUrl, $productId);

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
                sprintf('Anystack license activation failed with status %d: %s', $response->status(), $response->body()),
            );
        }

        $responseBody = $response->json();
        $data = is_array($responseBody) ? ($responseBody['data'] ?? null) : null;

        throw_unless(is_array($data), RuntimeException::class, 'Invalid response from Anystack: missing data object');

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
        $url = sprintf('%s/v1/products/%s/licenses/validate-key', $this->baseUrl, $productId);

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
                sprintf('Anystack license validation failed with status %d: %s', $response->status(), $response->body()),
            );
        }

        $responseBody = $response->json();
        $data = is_array($responseBody) ? ($responseBody['data'] ?? null) : null;
        $meta = is_array($responseBody) ? ($responseBody['meta'] ?? []) : [];

        throw_unless(is_array($data), RuntimeException::class, 'Invalid response from Anystack: missing data object');

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
        $url = sprintf('%s/v1/products/%s/licenses/%s/activations/%s', $this->baseUrl, $productId, $anystackLicenseId, $anystackActivationId);

        $response = $this->pendingRequest()->delete($url);

        if ($response->successful()) {
            return true;
        }

        if ($response->status() === 404) {
            return false;
        }

        throw new RuntimeException(
            sprintf('Anystack license deactivation failed with status %s: %s', $response->status(), $response->body()),
        );
    }

    public function composerRepositoryUrl(string $productId): string
    {
        return sprintf('https://%s.composer.sh', $productId);
    }

    private function pendingRequest(): PendingRequest
    {
        $request = Http::timeout($this->timeoutSeconds)
            ->acceptJson()
            ->asJson();

        if ($this->apiKey !== null && $this->apiKey !== '') {
            return $request->withToken($this->apiKey);
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
        throw_unless(array_key_exists('valid', $meta), RuntimeException::class, 'Invalid response from Anystack: missing meta.valid field');

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

        if ($statusCode === 'EXPIRED') {
            return LicenseStatus::Expired;
        }

        if ($statusCode === 'RESTRICTED') {
            // Entitled to the version shipped at expiry, but not new releases.
            // Distinct from Expired so the admin UI can render a softer
            // message ("no new updates") rather than the hard "license
            // expired, plugin disabled" banner.
            return LicenseStatus::Restricted;
        }

        if ($statusCode === 'SUSPENDED') {
            return LicenseStatus::Revoked;
        }

        if (str_starts_with($statusCode, 'FINGERPRINT_')) {
            // Fingerprint mismatch is an identity/activation problem, not a
            // billing problem. PastDue was the wrong bucket — a license
            // with a FINGERPRINT_* status won't self-heal on the next
            // heartbeat the way a PastDue billing issue might.
            return LicenseStatus::Invalid;
        }

        return LicenseStatus::Expired;
    }
}
