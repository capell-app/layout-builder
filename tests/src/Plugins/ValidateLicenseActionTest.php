<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins;

use Capell\Plugins\Actions\ValidateLicenseAction;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

final class ValidateLicenseActionTest extends PluginsTestCase
{
    public function test_happy_path_validates_and_updates_heartbeat(): void
    {
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

        $action = new ValidateLicenseAction($this->makeClient());
        $result = $action->handle($license);

        CarbonImmutable::setTestNow();

        $this->assertSame(LicenseStatus::Active, $result->status);
        $this->assertInstanceOf(CarbonImmutable::class, $result->last_heartbeat_at);
        $this->assertSame($now->toDateTimeString(), $result->last_heartbeat_at->toDateTimeString());

        $this->assertTrue($plugin->auditLog()->where('action', 'license_validated')->exists());
    }

    public function test_expired_response_persists_status_as_expired(): void
    {
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

        $action = new ValidateLicenseAction($this->makeClient());
        $result = $action->handle($license);

        $this->assertSame(LicenseStatus::Expired, $result->status);

        $auditEntry = $plugin->auditLog()->where('action', 'license_validated')->first();
        $this->assertNotNull($auditEntry);
        $data = $auditEntry->data->getArrayCopy();
        $this->assertSame(LicenseStatus::Expired->value, $data['status']);
        $this->assertSame('EXPIRED', $data['status_code']);
    }

    public function test_anystack_offline_within_grace_period_keeps_status(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response(['message' => 'offline'], 503),
        ]);

        $plugin = MarketplacePlugin::factory()->create([
            'anystack_product_id' => 'prod_xyz',
        ]);
        // Within default grace (14 days): heartbeat 3 days ago.
        $license = MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'status' => LicenseStatus::Active,
            'last_heartbeat_at' => now()->subDays(3),
        ]);

        $action = new ValidateLicenseAction($this->makeClient());
        $result = $action->handle($license);

        $this->assertSame(LicenseStatus::Active, $result->status);

        $auditEntry = $plugin->auditLog()->where('action', 'license_validation_failed')->first();
        $this->assertNotNull($auditEntry);
        $data = $auditEntry->data->getArrayCopy();
        $this->assertTrue($data['grace_period_remaining']);
    }

    public function test_validate_skips_licenses_for_plugin_without_anystack_product_id(): void
    {
        // Free plugins have no anystack_product_id. Calling anystack with a
        // null product would hit `/v1/products//licenses/validate-key` (note
        // the double slash) and blow up. Skip with an audit entry instead.
        Http::fake();

        $plugin = MarketplacePlugin::factory()->create([
            'anystack_product_id' => null,
        ]);
        $license = MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'status' => LicenseStatus::Active,
        ]);

        $action = new ValidateLicenseAction($this->makeClient());
        $result = $action->handle($license);

        Http::assertNothingSent();
        $this->assertSame(LicenseStatus::Active, $result->status);
        $this->assertTrue($plugin->auditLog()->where('action', 'license_skipped_no_product_id')->exists());
    }

    public function test_anystack_offline_past_grace_period_flips_to_expired(): void
    {
        Http::fake([
            'api.anystack.sh/*' => Http::response(['message' => 'offline'], 503),
        ]);

        $plugin = MarketplacePlugin::factory()->create([
            'anystack_product_id' => 'prod_xyz',
        ]);
        // Past default grace (14 days): heartbeat 30 days ago.
        $license = MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'status' => LicenseStatus::Active,
            'last_heartbeat_at' => now()->subDays(30),
        ]);

        $action = new ValidateLicenseAction($this->makeClient());
        $result = $action->handle($license);

        $this->assertSame(LicenseStatus::Expired, $result->status);

        $this->assertTrue($plugin->auditLog()->where('action', 'license_expired_offline')->exists());
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
