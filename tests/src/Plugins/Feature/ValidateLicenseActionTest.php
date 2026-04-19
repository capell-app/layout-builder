<?php

declare(strict_types=1);

use Capell\Plugins\Actions\ValidateLicenseAction;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

function makeValidateClient(): AnystackClient
{
    return new AnystackClient(
        baseUrl: 'https://api.anystack.sh',
        apiKey: null,
        timeoutSeconds: 5,
    );
}

test('happy path validates and updates heartbeat', function (): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'data' => [
                'id' => 'license_abc',
                'expires_at' => '2030-01-01T00:00:00Z',
                'suspended' => false,
            ],
            'meta' => [
                'valid' => true,
                'status' => 'VALID',
            ],
        ], 200),
    ]);

    $plugin = MarketplacePlugin::factory()->create([
        'anystack_product_id' => 'prod_xyz',
    ]);
    $license = MarketplacePluginLicense::factory()->create([
        'marketplace_plugin_id' => $plugin->id,
        'site_id' => 'site_abc',
        'status' => LicenseStatus::Active,
        'last_heartbeat_at' => now()->subDays(2),
    ]);

    $now = CarbonImmutable::parse('2026-06-01 12:00:00');
    CarbonImmutable::setTestNow($now);

    $action = new ValidateLicenseAction(makeValidateClient());
    $result = $action->handle($license);

    CarbonImmutable::setTestNow();

    expect($result->status)->toBe(LicenseStatus::Active);
    expect($result->last_heartbeat_at)->toBeInstanceOf(CarbonImmutable::class);
    expect($result->last_heartbeat_at->toDateTimeString())->toBe($now->toDateTimeString());

    expect($plugin->auditLog()->where('action', 'license_validated')->exists())->toBeTrue();
});

test('expired response persists status as expired', function (): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'data' => [
                'id' => 'license_abc',
            ],
            'meta' => [
                'valid' => false,
                'status' => 'EXPIRED',
            ],
        ], 200),
    ]);

    $plugin = MarketplacePlugin::factory()->create([
        'anystack_product_id' => 'prod_xyz',
    ]);
    $license = MarketplacePluginLicense::factory()->create([
        'marketplace_plugin_id' => $plugin->id,
        'status' => LicenseStatus::Active,
        'last_heartbeat_at' => now()->subDays(1),
    ]);

    $action = new ValidateLicenseAction(makeValidateClient());
    $result = $action->handle($license);

    expect($result->status)->toBe(LicenseStatus::Expired);

    $auditEntry = $plugin->auditLog()->where('action', 'license_validated')->first();
    expect($auditEntry)->not()->toBeNull();
    $data = $auditEntry->data->getArrayCopy();
    expect($data['status'])->toBe(LicenseStatus::Expired->value);
    expect($data['status_code'])->toBe('EXPIRED');
});

test('anystack offline within grace period keeps status', function (): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response(['message' => 'offline'], 503),
    ]);

    $plugin = MarketplacePlugin::factory()->create([
        'anystack_product_id' => 'prod_xyz',
    ]);
    $license = MarketplacePluginLicense::factory()->create([
        'marketplace_plugin_id' => $plugin->id,
        'status' => LicenseStatus::Active,
        'last_heartbeat_at' => now()->subDays(3),
    ]);

    $action = new ValidateLicenseAction(makeValidateClient());
    $result = $action->handle($license);

    expect($result->status)->toBe(LicenseStatus::Active);

    $auditEntry = $plugin->auditLog()->where('action', 'license_validation_failed')->first();
    expect($auditEntry)->not()->toBeNull();
    $data = $auditEntry->data->getArrayCopy();
    expect($data['grace_period_remaining'])->toBeTrue();
});

test('validate skips licenses for plugin without anystack product id', function (): void {
    Http::fake();

    $plugin = MarketplacePlugin::factory()->create([
        'anystack_product_id' => null,
    ]);
    $license = MarketplacePluginLicense::factory()->create([
        'marketplace_plugin_id' => $plugin->id,
        'status' => LicenseStatus::Active,
    ]);

    $action = new ValidateLicenseAction(makeValidateClient());
    $result = $action->handle($license);

    Http::assertNothingSent();
    expect($result->status)->toBe(LicenseStatus::Active);
    expect($plugin->auditLog()->where('action', 'license_skipped_no_product_id')->exists())->toBeTrue();
});

test('anystack offline past grace period flips to expired', function (): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response(['message' => 'offline'], 503),
    ]);

    $plugin = MarketplacePlugin::factory()->create([
        'anystack_product_id' => 'prod_xyz',
    ]);
    $license = MarketplacePluginLicense::factory()->create([
        'marketplace_plugin_id' => $plugin->id,
        'status' => LicenseStatus::Active,
        'last_heartbeat_at' => now()->subDays(30),
    ]);

    $action = new ValidateLicenseAction(makeValidateClient());
    $result = $action->handle($license);

    expect($result->status)->toBe(LicenseStatus::Expired);

    expect($plugin->auditLog()->where('action', 'license_expired_offline')->exists())->toBeTrue();
});
