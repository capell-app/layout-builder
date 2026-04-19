<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins;

use Capell\Plugins\Actions\DeactivateLicenseAction;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class DeactivateLicenseActionTest extends PluginsTestCase
{
    public function test_happy_path_calls_anystack_and_deletes_license(): void
    {
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

        $action = new DeactivateLicenseAction($this->makeClient());
        $action->handle($license);

        $this->assertDatabaseMissing('marketplace_plugin_licenses', ['id' => $licenseId]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'prod_xyz/licenses/lic_123/activations/act_456')
            && $request->method() === 'DELETE');

        $auditEntry = $plugin->auditLog()->where('action', 'license_deactivated')->first();
        $this->assertNotNull($auditEntry);
        $data = $auditEntry->data->getArrayCopy();
        $this->assertSame('site_abc', $data['site_id']);
        $this->assertSame($licenseId, $data['license_id']);
        $this->assertTrue($data['remote_removed']);
        $this->assertNull($data['remote_error']);
    }

    public function test_anystack_failure_still_deletes_locally_and_audits_error(): void
    {
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

        $action = new DeactivateLicenseAction($this->makeClient());
        $action->handle($license);

        $this->assertDatabaseMissing('marketplace_plugin_licenses', ['id' => $licenseId]);

        $auditEntry = $plugin->auditLog()->where('action', 'license_deactivated')->first();
        $this->assertNotNull($auditEntry);
        $data = $auditEntry->data->getArrayCopy();
        $this->assertIsString($data['remote_error']);
        $this->assertStringContainsString('500', $data['remote_error']);
        $this->assertNull($data['remote_removed']);
    }

    public function test_license_without_activation_id_skips_remote_call(): void
    {
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

        $action = new DeactivateLicenseAction($this->makeClient());
        $action->handle($license);

        $this->assertDatabaseMissing('marketplace_plugin_licenses', ['id' => $licenseId]);
        Http::assertNothingSent();

        $auditEntry = $plugin->auditLog()->where('action', 'license_deactivated')->first();
        $this->assertNotNull($auditEntry);
        $data = $auditEntry->data->getArrayCopy();
        $this->assertNull($data['anystack_activation_id']);
        $this->assertNull($data['remote_removed']);
    }

    private function makeClient(): AnystackClient
    {
        return new AnystackClient(
            baseUrl: 'https://api.anystack.sh',
            apiKey: null,
            timeoutSeconds: 5,
        );
    }
}
