<?php

declare(strict_types=1);

use Capell\Plugins\Data\AnystackLicenseValidationData;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Services\AnystackClient;
use DateTimeImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

$client = null;

beforeEach(function () use (&$client): void {
    $client = new AnystackClient(
        baseUrl: 'https://api.anystack.sh',
        apiKey: null,
        timeoutSeconds: 10,
    );
});

test('validate license returns active when meta valid true', function () use (&$client): void {
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

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result)->toBeInstanceOf(AnystackLicenseValidationData::class);
    expect($result->valid)->toBeTrue();
    expect($result->status)->toBe(LicenseStatus::Active);
    expect($result->product)->toBe('prod_xyz');
    expect($result->licenseId)->toBe('license-123');
    expect($result->expiresAt)->toBeInstanceOf(DateTimeImmutable::class);
});

test('validate license maps expired status', function () use (&$client): void {
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

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result->valid)->toBeFalse();
    expect($result->status)->toBe(LicenseStatus::Expired);
    expect($result->statusCode)->toBe('EXPIRED');
});

test('validate license maps suspended data to revoked', function () use (&$client): void {
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

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result->valid)->toBeFalse();
    expect($result->status)->toBe(LicenseStatus::Revoked);
});

test('validate license maps fingerprint invalid to invalid not past due', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'data' => [
                'id' => 'license-123',
                'suspended' => false,
            ],
            'meta' => ['valid' => false, 'status' => 'FINGERPRINT_INVALID'],
        ], 200),
    ]);

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result->valid)->toBeFalse();
    expect($result->status)->toBe(LicenseStatus::Invalid);
    expect($result->statusCode)->toBe('FINGERPRINT_INVALID');
    expect($result->status->isUsable())->toBeFalse();
});

test('validate license sends bearer token when api key configured', function (): void {
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
});

test('validate license throws on 500', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response(['error' => 'boom'], 500),
    ]);

    expect(fn () => $client->validateLicense('prod_xyz', 'test-key'))
        ->toThrow(RuntimeException::class, 'Anystack license validation failed with status 500');
});

test('activate license returns dto with activation id', function () use (&$client): void {
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

    $result = $client->activateLicense('prod_xyz', 'test-key', 'fp-1');

    expect($result->valid)->toBeTrue();
    expect($result->status)->toBe(LicenseStatus::Active);
    expect($result->activationId)->toBe('activation-abc');
    expect($result->licenseId)->toBe('license-xyz');
});

test('activate license sends fingerprint in body', function () use (&$client): void {
    Http::fake([
        '*/activate-key' => Http::response([
            'data' => ['id' => 'activation-abc', 'license_id' => 'license-xyz'],
        ], 200),
        '*/validate-key' => Http::response([
            'data' => ['id' => 'license-xyz', 'suspended' => false],
            'meta' => ['valid' => true],
        ], 200),
    ]);

    $client->activateLicense('prod_xyz', 'test-key', 'fp-1', 'host-1');

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
});

test('activate chains into validate and merges dto', function () use (&$client): void {
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

    $result = $client->activateLicense('prod_xyz', 'test-key', 'fp-1');

    expect($result->activationId)->toBe('activation-abc');
    expect($result->status)->toBe(LicenseStatus::Active);
    expect($result->valid)->toBeTrue();
    expect($result->expiresAt)->toBeInstanceOf(DateTimeImmutable::class);
    expect($result->expiresAt->format('Y-m-d'))->toBe('2099-12-31');

    Http::assertSentCount(2);
});

test('activate surfaces validate chain suspended as revoked', function () use (&$client): void {
    Http::fake([
        '*/activate-key' => Http::response([
            'data' => ['id' => 'activation-abc', 'license_id' => 'license-xyz'],
        ], 200),
        '*/validate-key' => Http::response([
            'data' => ['id' => 'license-xyz', 'suspended' => true],
            'meta' => ['valid' => true],
        ], 200),
    ]);

    $result = $client->activateLicense('prod_xyz', 'test-key', 'fp-1');

    expect($result->valid)->toBeFalse();
    expect($result->status)->toBe(LicenseStatus::Revoked);
    expect($result->activationId)->toBe('activation-abc');
});

test('validate license maps suspended status to revoked', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'data' => ['id' => 'license-123', 'suspended' => false],
            'meta' => ['valid' => false, 'status' => 'SUSPENDED'],
        ], 200),
    ]);

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result->valid)->toBeFalse();
    expect($result->status)->toBe(LicenseStatus::Revoked);
    expect($result->statusCode)->toBe('SUSPENDED');
});

test('validate license maps restricted to restricted distinct from expired', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'data' => ['id' => 'license-123', 'suspended' => false],
            'meta' => ['valid' => false, 'status' => 'RESTRICTED'],
        ], 200),
    ]);

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result->valid)->toBeFalse();
    expect($result->status)->toBe(LicenseStatus::Restricted);
    expect($result->statusCode)->toBe('RESTRICTED');
    expect($result->status->isUsable())->toBeFalse();
});

test('validate license maps fingerprint invalid hostname to invalid', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'data' => ['id' => 'license-123', 'suspended' => false],
            'meta' => ['valid' => false, 'status' => 'FINGERPRINT_INVALID_HOSTNAME'],
        ], 200),
    ]);

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result->status)->toBe(LicenseStatus::Invalid);
    expect($result->statusCode)->toBe('FINGERPRINT_INVALID_HOSTNAME');
});

test('validate license maps fingerprint missing to invalid', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'data' => ['id' => 'license-123', 'suspended' => false],
            'meta' => ['valid' => false, 'status' => 'FINGERPRINT_MISSING'],
        ], 200),
    ]);

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result->status)->toBe(LicenseStatus::Invalid);
    expect($result->statusCode)->toBe('FINGERPRINT_MISSING');
});

test('validate license throws when meta valid missing', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'data' => ['id' => 'license-123', 'suspended' => false],
            'meta' => ['some_other_field' => true],
        ], 200),
    ]);

    expect(fn () => $client->validateLicense('prod_xyz', 'test-key'))
        ->toThrow(RuntimeException::class, 'missing meta.valid');
});

test('validate license maps suspended data with valid meta to revoked', function () use (&$client): void {
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

    $result = $client->validateLicense('prod_xyz', 'test-key');

    expect($result->valid)->toBeFalse();
    expect($result->status)->toBe(LicenseStatus::Revoked);
});

test('activate license throws on 422', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'message' => 'activation limit exceeded',
            'code' => 'ACTIVATION_LIMIT_EXCEEDED',
        ], 422),
    ]);

    expect(fn () => $client->activateLicense('prod_xyz', 'test-key', 'fp-1'))
        ->toThrow(RuntimeException::class, 'Anystack license activation failed with status 422');
});

test('deactivate license returns true on 200', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response('', 200),
    ]);

    $result = $client->deactivateLicense('prod_xyz', 'license-1', 'activation-1');

    expect($result)->toBeTrue();
});

test('deactivate license returns false on 404', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response(['message' => 'not found'], 404),
    ]);

    $result = $client->deactivateLicense('prod_xyz', 'license-1', 'activation-missing');

    expect($result)->toBeFalse();
});

test('deactivate license throws on 500', function () use (&$client): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response(['message' => 'boom'], 500),
    ]);

    expect(fn () => $client->deactivateLicense('prod_xyz', 'license-1', 'activation-1'))
        ->toThrow(RuntimeException::class, 'Anystack license deactivation failed with status 500');
});

test('composer repository url uses product subdomain', function () use (&$client): void {
    $url = $client->composerRepositoryUrl('my-prod');

    expect($url)->toBe('https://my-prod.composer.sh');
});
