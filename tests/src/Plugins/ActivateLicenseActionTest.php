<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins;

use Capell\Plugins\Actions\ActivateLicenseAction;
use Capell\Plugins\Data\AnystackLicenseValidationData;
use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Enums\PluginKind;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use DateTimeImmutable;
use Mockery;
use RuntimeException;

class ActivateLicenseActionTest extends PluginsTestCase
{
    public function test_valid_license_activates_correctly(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test',
            'composer_name' => 'vendor/test',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'anystack_product_id' => 'prod_123',
        ]);

        $mockAnystack = Mockery::mock(AnystackClient::class);
        $mockAnystack->shouldReceive('validateLicense')
            ->once()
            ->andReturn(new AnystackLicenseValidationData(
                valid: true,
                status: LicenseStatus::Active,
                expiresAt: new DateTimeImmutable('2025-12-31'),
                raw: ['test' => 'data'],
            ));
        $this->app->instance(AnystackClient::class, $mockAnystack);

        $action = new ActivateLicenseAction($mockAnystack);
        $license = $action->handle($plugin, 'test_key_123', 'site_123');

        $this->assertInstanceOf(MarketplacePluginLicense::class, $license);
        $this->assertEquals(LicenseStatus::Active, $license->status);
        $this->assertEquals('site_123', $license->site_id);
        $this->assertTrue($plugin->auditLog()->where('action', 'license_activated')->exists());
    }

    public function test_invalid_license_throws(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test',
            'composer_name' => 'vendor/test',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'anystack_product_id' => 'prod_123',
        ]);

        $mockAnystack = Mockery::mock(AnystackClient::class);
        $mockAnystack->shouldReceive('validateLicense')
            ->once()
            ->andReturn(new AnystackLicenseValidationData(
                valid: false,
                status: LicenseStatus::Revoked,
            ));
        $this->app->instance(AnystackClient::class, $mockAnystack);

        $action = new ActivateLicenseAction($mockAnystack);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('License validation failed');

        $action->handle($plugin, 'invalid_key', 'site_123');
    }

    public function test_plugin_without_product_id_throws(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test',
            'composer_name' => 'vendor/test',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'anystack_product_id' => null,
        ]);

        $mockAnystack = Mockery::mock(AnystackClient::class);
        $this->app->instance(AnystackClient::class, $mockAnystack);

        $action = new ActivateLicenseAction($mockAnystack);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no anystack_product_id');

        $action->handle($plugin, 'key', 'site');
    }

    public function test_existing_license_for_site_is_updated(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test',
            'composer_name' => 'vendor/test',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'anystack_product_id' => 'prod_123',
        ]);

        $existingLicense = MarketplacePluginLicense::create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_123',
            'encrypted_license_key' => 'old_key',
            'status' => LicenseStatus::Expired,
        ]);

        $mockAnystack = Mockery::mock(AnystackClient::class);
        $mockAnystack->shouldReceive('validateLicense')
            ->once()
            ->andReturn(new AnystackLicenseValidationData(
                valid: true,
                status: LicenseStatus::Active,
            ));
        $this->app->instance(AnystackClient::class, $mockAnystack);

        $action = new ActivateLicenseAction($mockAnystack);
        $license = $action->handle($plugin, 'new_key', 'site_123');

        // Should update, not create duplicate
        $this->assertEquals($existingLicense->id, $license->id);
        $this->assertEquals(LicenseStatus::Active, $license->status);
        $this->assertEquals(1, $plugin->licenses()->where('site_id', 'site_123')->count());
    }

    public function test_expired_license_validates_with_expired_status(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test',
            'composer_name' => 'vendor/test',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'anystack_product_id' => 'prod_123',
        ]);

        $mockAnystack = Mockery::mock(AnystackClient::class);
        $mockAnystack->shouldReceive('validateLicense')
            ->once()
            ->andReturn(new AnystackLicenseValidationData(
                valid: true,
                status: LicenseStatus::Expired,
                expiresAt: new DateTimeImmutable('2024-01-01'),
            ));
        $this->app->instance(AnystackClient::class, $mockAnystack);

        $action = new ActivateLicenseAction($mockAnystack);
        $license = $action->handle($plugin, 'expired_key', 'site_456');

        $this->assertEquals(LicenseStatus::Expired, $license->status);
        $this->assertTrue($plugin->auditLog()->where('action', 'license_activated')->exists());
    }
}
