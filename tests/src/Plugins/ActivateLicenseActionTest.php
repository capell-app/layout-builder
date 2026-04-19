<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins;

use Capell\Plugins\Actions\ActivateLicenseAction;
use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Enums\PluginKind;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ActivateLicenseActionTest extends PluginsTestCase
{
    public function test_valid_license_activates_correctly(): void
    {
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

        $plugin = $this->makePlugin();
        $action = $this->makeAction();

        $license = $action->handle($plugin, 'test_key_123', 'site_123');

        $this->assertInstanceOf(MarketplacePluginLicense::class, $license);
        $this->assertEquals(LicenseStatus::Active, $license->status);
        $this->assertEquals('site_123', $license->site_id);
        $this->assertEquals('license_abc', $license->anystack_license_id);
        $this->assertEquals('activation_xyz', $license->anystack_activation_id);
        $this->assertTrue($plugin->auditLog()->where('action', 'license_activated')->exists());
    }

    public function test_anystack_failure_throws(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response([
                'message' => 'fingerprint already exists',
                'code' => 'FINGERPRINT_ALREADY_EXISTS',
            ], 422),
        ]);

        $plugin = $this->makePlugin();
        $action = $this->makeAction();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Anystack license activation failed');

        $action->handle($plugin, 'invalid_key', 'site_123');
    }

    public function test_plugin_without_product_id_throws(): void
    {
        $plugin = $this->makePlugin(['anystack_product_id' => null]);
        $action = $this->makeAction();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no anystack_product_id');

        $action->handle($plugin, 'key', 'site');
    }

    public function test_existing_license_for_site_is_updated(): void
    {
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

        $plugin = $this->makePlugin();

        $existingLicense = MarketplacePluginLicense::query()->create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_123',
            'encrypted_license_key' => 'old_key',
            'status' => LicenseStatus::Expired,
        ]);

        $action = $this->makeAction();
        $license = $action->handle($plugin, 'new_key', 'site_123');

        $this->assertEquals($existingLicense->id, $license->id);
        $this->assertEquals(LicenseStatus::Active, $license->status);
        $this->assertEquals('activation_xyz', $license->anystack_activation_id);
        $this->assertEquals(1, $plugin->licenses()->where('site_id', 'site_123')->count());
    }

    public function test_passes_explicit_fingerprint_to_anystack(): void
    {
        Http::fake([
            '*/activate-key' => Http::response([
                'data' => ['id' => 'a', 'license_id' => 'l'],
            ], 200),
            '*/validate-key' => Http::response([
                'data' => ['id' => 'l', 'suspended' => false],
                'meta' => ['valid' => true],
            ], 200),
        ]);

        $plugin = $this->makePlugin();
        $action = $this->makeAction();

        $action->handle($plugin, 'k', 'site_123', 'explicit-fp');

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), 'activate-key')) {
                return false;
            }

            $body = json_decode($request->body(), true);

            return is_array($body) && ($body['fingerprint'] ?? null) === 'explicit-fp';
        });
    }

    private function makeAction(): ActivateLicenseAction
    {
        // Real client — Http::fake() intercepts the underlying HTTP calls.
        $client = new AnystackClient(
            baseUrl: 'https://api.anystack.sh',
            apiKey: null,
            timeoutSeconds: 5,
        );
        $this->app->instance(AnystackClient::class, $client);

        return new ActivateLicenseAction($client);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePlugin(array $overrides = []): MarketplacePlugin
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
}
