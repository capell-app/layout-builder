<?php

declare(strict_types=1);

use Capell\Plugins\Actions\ActivateLicenseAction;
use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Enums\PluginKind;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function makeActivateAction(): ActivateLicenseAction
{
    // Real client — Http::fake() intercepts the underlying HTTP calls.
    $client = new AnystackClient(
        baseUrl: 'https://api.anystack.sh',
        apiKey: null,
        timeoutSeconds: 5,
    );

    return new ActivateLicenseAction($client);
}

function makeActivatePlugin(array $overrides = []): MarketplacePlugin
{
    return MarketplacePlugin::query()->create(array_merge([
        'name' => 'Test Plugin',
        'slug' => 'test-plugin',
        'description' => 'Test',
        'composer_name' => 'vendor/test',
        'vendor' => 'vendor',
        'kind' => PluginKind::Full,
        'license_model' => LicenseModel::Free,
        'latest_version' => '1.0.0',
        'anystack_product_id' => 'prod_123',
    ], $overrides));
}

test('valid license activates correctly', function (): void {
    Http::fake([
        '*/activate-key' => Http::response([
            'data' => [
                'id' => 'activation_xyz',
                'license_id' => 'license_abc',
                'fingerprint' => 'fp',
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
            ],
        ], 200),
        '*/validate-key' => Http::response([
            'data' => [
                'id' => 'license_abc',
                'suspended' => false,
                'expires_at' => '2099-12-31T00:00:00Z',
            ],
            'meta' => ['valid' => true],
        ], 200),
    ]);

    $plugin = makeActivatePlugin();
    $action = makeActivateAction();

    $license = $action->handle($plugin, 'test_key_123', 'site_123');

    expect($license)->toBeInstanceOf(MarketplacePluginLicense::class);
    expect($license->status)->toBe(LicenseStatus::Active);
    expect($license->site_id)->toBe('site_123');
    expect($license->anystack_license_id)->toBe('license_abc');
    expect($license->anystack_activation_id)->toBe('activation_xyz');
    expect($plugin->auditLog()->where('action', 'license_activated')->exists())->toBeTrue();
});

test('anystack failure throws', function (): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response([
            'message' => 'fingerprint already exists',
            'code' => 'FINGERPRINT_ALREADY_EXISTS',
        ], 422),
    ]);

    $plugin = makeActivatePlugin();
    $action = makeActivateAction();

    expect(fn () => $action->handle($plugin, 'invalid_key', 'site_123'))
        ->toThrow(RuntimeException::class, 'Anystack license activation failed');
});

test('plugin without product id throws', function (): void {
    $plugin = makeActivatePlugin(['anystack_product_id' => null]);
    $action = makeActivateAction();

    expect(fn () => $action->handle($plugin, 'key', 'site'))
        ->toThrow(RuntimeException::class, 'no anystack_product_id');
});

test('existing license for site is updated', function (): void {
    Http::fake([
        '*/activate-key' => Http::response([
            'data' => [
                'id' => 'activation_xyz',
                'license_id' => 'license_abc',
            ],
        ], 200),
        '*/validate-key' => Http::response([
            'data' => ['id' => 'license_abc', 'suspended' => false],
            'meta' => ['valid' => true],
        ], 200),
    ]);

    $plugin = makeActivatePlugin();

    $existingLicense = MarketplacePluginLicense::query()->create([
        'marketplace_plugin_id' => $plugin->id,
        'site_id' => 'site_123',
        'encrypted_license_key' => 'old_key',
        'status' => LicenseStatus::Expired,
    ]);

    $action = makeActivateAction();
    $license = $action->handle($plugin, 'new_key', 'site_123');

    expect($license->id)->toBe($existingLicense->id);
    expect($license->status)->toBe(LicenseStatus::Active);
    expect($license->anystack_activation_id)->toBe('activation_xyz');
    expect($plugin->licenses()->where('site_id', 'site_123')->count())->toBe(1);
});

test('passes explicit fingerprint to anystack', function (): void {
    Http::fake([
        '*/activate-key' => Http::response([
            'data' => ['id' => 'a', 'license_id' => 'l'],
        ], 200),
        '*/validate-key' => Http::response([
            'data' => ['id' => 'l', 'suspended' => false],
            'meta' => ['valid' => true],
        ], 200),
    ]);

    $plugin = makeActivatePlugin();
    $action = makeActivateAction();

    $action->handle($plugin, 'k', 'site_123', 'explicit-fp');

    Http::assertSent(function (Request $request): bool {
        if (! str_contains($request->url(), 'activate-key')) {
            return false;
        }

        $body = json_decode($request->body(), true);

        return is_array($body) && ($body['fingerprint'] ?? null) === 'explicit-fp';
    });
});
