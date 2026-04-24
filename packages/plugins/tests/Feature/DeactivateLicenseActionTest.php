<?php

declare(strict_types=1);

use Capell\Plugins\Actions\DeactivateLicenseAction;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function makeDeactivateClient(): AnystackClient
{
    return new AnystackClient(
        baseUrl: 'https://api.anystack.sh',
        apiKey: null,
        timeoutSeconds: 5,
    );
}

test('happy path calls anystack and deletes license', function (): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response('', 204),
    ]);

    $plugin = MarketplacePlugin::factory()->create([
        'anystack_product_id' => 'prod_xyz',
    ]);
    $license = MarketplacePluginLicense::factory()->create([
        'marketplace_plugin_id' => $plugin->id,
        'site_id' => 'site_abc',
        'anystack_license_id' => 'lic_123',
        'anystack_activation_id' => 'act_456',
    ]);
    $licenseId = $license->id;

    $action = new DeactivateLicenseAction(makeDeactivateClient());
    $action->handle($license);

    expect($license->fresh())->toBeNull();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'prod_xyz/licenses/lic_123/activations/act_456')
        && $request->method() === 'DELETE');

    $auditEntry = $plugin->auditLog()->where('action', 'license_deactivated')->first();
    expect($auditEntry)->not()->toBeNull();
    $data = $auditEntry->data->getArrayCopy();
    expect($data['site_id'])->toBe('site_abc');
    expect($data['license_id'])->toBe($licenseId);
    expect($data['remote_removed'])->toBeTrue();
    expect($data['remote_error'])->toBeNull();
});

test('anystack failure still deletes locally and audits error', function (): void {
    Http::fake([
        'api.anystack.sh/*' => Http::response(['message' => 'Server error'], 500),
    ]);

    $plugin = MarketplacePlugin::factory()->create([
        'anystack_product_id' => 'prod_xyz',
    ]);
    $license = MarketplacePluginLicense::factory()->create([
        'marketplace_plugin_id' => $plugin->id,
        'anystack_license_id' => 'lic_123',
        'anystack_activation_id' => 'act_456',
    ]);
    $licenseId = $license->id;

    $action = new DeactivateLicenseAction(makeDeactivateClient());
    $action->handle($license);

    expect($license->fresh())->toBeNull();

    $auditEntry = $plugin->auditLog()->where('action', 'license_deactivated')->first();
    expect($auditEntry)->not()->toBeNull();
    $data = $auditEntry->data->getArrayCopy();
    expect($data['remote_error'])->toBeString();
    expect($data['remote_error'])->toContain('500');
    expect($data['remote_removed'])->toBeNull();
});

test('license without activation id skips remote call', function (): void {
    Http::fake();

    $plugin = MarketplacePlugin::factory()->create([
        'anystack_product_id' => 'prod_xyz',
    ]);
    $license = MarketplacePluginLicense::factory()->create([
        'marketplace_plugin_id' => $plugin->id,
        'anystack_license_id' => 'lic_123',
        'anystack_activation_id' => null,
    ]);
    $licenseId = $license->id;

    $action = new DeactivateLicenseAction(makeDeactivateClient());
    $action->handle($license);

    expect($license->fresh())->toBeNull();
    Http::assertNothingSent();

    $auditEntry = $plugin->auditLog()->where('action', 'license_deactivated')->first();
    expect($auditEntry)->not()->toBeNull();
    $data = $auditEntry->data->getArrayCopy();
    expect($data['anystack_activation_id'])->toBeNull();
    expect($data['remote_removed'])->toBeNull();
});
