<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins\Unit;

use Capell\Plugins\Data\AnystackLicenseValidationData;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Services\AnystackClient;
use Capell\Tests\Plugins\PluginsTestCase;
use DateTimeImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AnystackClientTest extends PluginsTestCase
{
    private AnystackClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new AnystackClient(
            baseUrl: 'https://api.anystack.sh',
            apiKey: null,
            timeoutSeconds: 10,
        );
    }

    public function test_validate_license_returns_active_when_meta_valid_true(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => [
                    'id' => 'license-123',
                    'key' => 'test-key',
                    'suspended' => false,
                    'expires_at' => '2099-12-31T00:00:00Z',
                    'activations' => 1,
                    'max_activations' => 3,
                ],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertInstanceOf(AnystackLicenseValidationData::class, $result);
        $this->assertTrue($result->valid);
        $this->assertSame(LicenseStatus::Active, $result->status);
        $this->assertSame('prod_xyz', $result->product);
        $this->assertSame('license-123', $result->licenseId);
        $this->assertInstanceOf(DateTimeImmutable::class, $result->expiresAt);
    }

    public function test_validate_license_maps_expired_status(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => [
                    'id' => 'license-123',
                    'suspended' => false,
                    'expires_at' => '2020-01-01T00:00:00Z',
                ],
                'meta' => ['valid' => false, 'status' => 'EXPIRED'],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertFalse($result->valid);
        $this->assertSame(LicenseStatus::Expired, $result->status);
        $this->assertSame('EXPIRED', $result->statusCode);
    }

    public function test_validate_license_maps_suspended_data_to_revoked(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => [
                    'id' => 'license-123',
                    'suspended' => true,
                    'expires_at' => '2099-12-31T00:00:00Z',
                ],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertFalse($result->valid);
        $this->assertSame(LicenseStatus::Revoked, $result->status);
    }

    public function test_validate_license_maps_fingerprint_invalid_to_invalid_not_past_due(): void
    {
        // Fingerprint mismatch is an identity/activation problem, not a
        // billing problem — mapping it to PastDue (the previous behavior)
        // conflated "we couldn't charge you" with "the install identity is
        // wrong." The Invalid bucket is terminal until manual intervention.
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => [
                    'id' => 'license-123',
                    'suspended' => false,
                ],
                'meta' => ['valid' => false, 'status' => 'FINGERPRINT_INVALID'],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertFalse($result->valid);
        $this->assertSame(LicenseStatus::Invalid, $result->status);
        $this->assertSame('FINGERPRINT_INVALID', $result->statusCode);
        $this->assertFalse($result->status->isUsable());
    }

    public function test_validate_license_sends_bearer_token_when_api_key_configured(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => ['id' => 'license-123', 'suspended' => false],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $clientWithKey = new AnystackClient(
            baseUrl: 'https://api.anystack.sh',
            apiKey: 'secret-api-key',
            timeoutSeconds: 10,
        );

        $clientWithKey->validateLicense('prod_xyz', 'test-key');

        Http::assertSent(fn (Request $request): bool => $request->header('Authorization')[0] === 'Bearer secret-api-key');
    }

    public function test_validate_license_throws_on_500(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anystack license validation failed with status 500');

        $this->client->validateLicense('prod_xyz', 'test-key');
    }

    public function test_activate_license_returns_dto_with_activation_id(): void
    {
        Http::fake([
            '*/activate-key' => Http::response([
                'data' => [
                    'id' => 'activation-abc',
                    'license_id' => 'license-xyz',
                    'fingerprint' => 'fp-1',
                    'hostname' => 'host-1',
                    'platform' => 'linux',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-01T00:00:00Z',
                ],
            ], 200),
            '*/validate-key' => Http::response([
                'data' => ['id' => 'license-xyz', 'suspended' => false],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $result = $this->client->activateLicense('prod_xyz', 'test-key', 'fp-1');

        $this->assertTrue($result->valid);
        $this->assertSame(LicenseStatus::Active, $result->status);
        $this->assertSame('activation-abc', $result->activationId);
        $this->assertSame('license-xyz', $result->licenseId);
    }

    public function test_activate_license_sends_fingerprint_in_body(): void
    {
        Http::fake([
            '*/activate-key' => Http::response([
                'data' => ['id' => 'activation-abc', 'license_id' => 'license-xyz'],
            ], 200),
            '*/validate-key' => Http::response([
                'data' => ['id' => 'license-xyz', 'suspended' => false],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $this->client->activateLicense('prod_xyz', 'test-key', 'fp-1', 'host-1');

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), 'activate-key')) {
                return false;
            }

            $body = json_decode($request->body(), true);

            return is_array($body)
                && ($body['fingerprint'] ?? null) === 'fp-1'
                && ($body['hostname'] ?? null) === 'host-1'
                && ($body['key'] ?? null) === 'test-key';
        });
    }

    public function test_activate_chains_into_validate_and_merges_dto(): void
    {
        // activate returns activation metadata only; validate supplies real
        // status + expiry. The returned DTO must merge both.
        Http::fake([
            '*/activate-key' => Http::response([
                'data' => [
                    'id' => 'activation-abc',
                    'license_id' => 'license-xyz',
                    'fingerprint' => 'fp-1',
                ],
            ], 200),
            '*/validate-key' => Http::response([
                'data' => [
                    'id' => 'license-xyz',
                    'suspended' => false,
                    'expires_at' => '2099-12-31T00:00:00Z',
                ],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $result = $this->client->activateLicense('prod_xyz', 'test-key', 'fp-1');

        $this->assertSame('activation-abc', $result->activationId);
        $this->assertSame(LicenseStatus::Active, $result->status);
        $this->assertTrue($result->valid);
        $this->assertInstanceOf(DateTimeImmutable::class, $result->expiresAt);
        $this->assertSame('2099-12-31', $result->expiresAt->format('Y-m-d'));

        Http::assertSentCount(2);
    }

    public function test_activate_surfaces_validate_chain_suspended_as_revoked(): void
    {
        Http::fake([
            '*/activate-key' => Http::response([
                'data' => ['id' => 'activation-abc', 'license_id' => 'license-xyz'],
            ], 200),
            '*/validate-key' => Http::response([
                'data' => ['id' => 'license-xyz', 'suspended' => true],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $result = $this->client->activateLicense('prod_xyz', 'test-key', 'fp-1');

        $this->assertFalse($result->valid);
        $this->assertSame(LicenseStatus::Revoked, $result->status);
        $this->assertSame('activation-abc', $result->activationId);
    }

    public function test_validate_license_maps_suspended_status_to_revoked(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => ['id' => 'license-123', 'suspended' => false],
                'meta' => ['valid' => false, 'status' => 'SUSPENDED'],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertFalse($result->valid);
        $this->assertSame(LicenseStatus::Revoked, $result->status);
        $this->assertSame('SUSPENDED', $result->statusCode);
    }

    public function test_validate_license_maps_restricted_to_restricted_distinct_from_expired(): void
    {
        // RESTRICTED is distinct from EXPIRED: the user is still entitled to
        // whatever version shipped at the restriction boundary, just not to
        // new updates. Previously bucketed as Expired; now its own case so
        // the admin UI can render a softer "no new updates" message.
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => ['id' => 'license-123', 'suspended' => false],
                'meta' => ['valid' => false, 'status' => 'RESTRICTED'],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertFalse($result->valid);
        $this->assertSame(LicenseStatus::Restricted, $result->status);
        $this->assertSame('RESTRICTED', $result->statusCode);
        $this->assertFalse($result->status->isUsable());
    }

    public function test_validate_license_maps_fingerprint_invalid_hostname_to_invalid(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => ['id' => 'license-123', 'suspended' => false],
                'meta' => ['valid' => false, 'status' => 'FINGERPRINT_INVALID_HOSTNAME'],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertSame(LicenseStatus::Invalid, $result->status);
        $this->assertSame('FINGERPRINT_INVALID_HOSTNAME', $result->statusCode);
    }

    public function test_validate_license_maps_fingerprint_missing_to_invalid(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => ['id' => 'license-123', 'suspended' => false],
                'meta' => ['valid' => false, 'status' => 'FINGERPRINT_MISSING'],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertSame(LicenseStatus::Invalid, $result->status);
        $this->assertSame('FINGERPRINT_MISSING', $result->statusCode);
    }

    public function test_validate_license_throws_when_meta_valid_missing(): void
    {
        // Malformed anystack response (no meta.valid) is a protocol error,
        // not an expiration — surface it rather than silently marking the
        // license expired.
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => ['id' => 'license-123', 'suspended' => false],
                'meta' => ['some_other_field' => true],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing meta.valid');

        $this->client->validateLicense('prod_xyz', 'test-key');
    }

    public function test_validate_license_maps_suspended_data_with_valid_meta_to_revoked(): void
    {
        // Edge case: meta.valid=true but data.suspended=true. Persisting
        // "Active" would be wrong — the license is NOT usable.
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'data' => [
                    'id' => 'license-123',
                    'suspended' => true,
                    'expires_at' => '2099-12-31T00:00:00Z',
                ],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $result = $this->client->validateLicense('prod_xyz', 'test-key');

        $this->assertFalse($result->valid);
        $this->assertSame(LicenseStatus::Revoked, $result->status);
    }

    public function test_activate_license_throws_on_422(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'message' => 'activation limit exceeded',
                'code' => 'ACTIVATION_LIMIT_EXCEEDED',
            ], 422),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anystack license activation failed with status 422');

        $this->client->activateLicense('prod_xyz', 'test-key', 'fp-1');
    }

    public function test_deactivate_license_returns_true_on_200(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response('', 200),
        ]);

        $result = $this->client->deactivateLicense('prod_xyz', 'license-1', 'activation-1');

        $this->assertTrue($result);
    }

    public function test_deactivate_license_returns_false_on_404(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response(['message' => 'not found'], 404),
        ]);

        $result = $this->client->deactivateLicense('prod_xyz', 'license-1', 'activation-missing');

        $this->assertFalse($result);
    }

    public function test_deactivate_license_throws_on_500(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response(['message' => 'boom'], 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anystack license deactivation failed with status 500');

        $this->client->deactivateLicense('prod_xyz', 'license-1', 'activation-1');
    }

    public function test_composer_repository_url_uses_product_subdomain(): void
    {
        $url = $this->client->composerRepositoryUrl('my-prod');

        $this->assertSame('https://my-prod.composer.sh', $url);
    }
}
