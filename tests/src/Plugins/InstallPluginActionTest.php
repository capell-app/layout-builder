<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins;

use Capell\Plugins\Actions\InstallPluginAction;
use Capell\Plugins\Enums\CapabilityWarningLevel;
use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\ComposerResult;
use Capell\Plugins\Services\ComposerRunner;
use Mockery;
use RuntimeException;

class InstallPluginActionTest extends PluginsTestCase
{
    public function test_install_free_plugin_succeeds(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test description',
            'composer_name' => 'vendor/free-plugin',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'price_monthly' => null,
            'price_yearly' => null,
            'price_once' => null,
        ]);

        $mockComposer = Mockery::mock(ComposerRunner::class);
        $mockComposer->shouldReceive('requirePackage')
            ->with($plugin->composer_name, $plugin->latest_version)
            ->once()
            ->andReturn(new ComposerResult(0, 'ok', ''));
        $this->app->instance(ComposerRunner::class, $mockComposer);

        $action = new InstallPluginAction($mockComposer);
        $action->handle($plugin);

        $this->assertTrue($plugin->auditLog()->where('action', 'installed')->exists());
    }

    public function test_install_paid_plugin_with_valid_license_succeeds(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Paid Plugin',
            'slug' => 'paid-plugin',
            'description' => 'Test description',
            'composer_name' => 'vendor/paid-plugin',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'anystack_product_id' => 'prod_123',
            'price_once' => 99,
        ]);

        $mockComposer = Mockery::mock(ComposerRunner::class);
        $mockComposer->shouldReceive('configureAnystackRepo')
            ->once()
            ->andReturn(new ComposerResult(0, 'ok', ''));
        $mockComposer->shouldReceive('requirePackage')
            ->with($plugin->composer_name, $plugin->latest_version)
            ->once()
            ->andReturn(new ComposerResult(0, 'ok', ''));
        $this->app->instance(ComposerRunner::class, $mockComposer);

        $action = new InstallPluginAction($mockComposer);
        $action->handle($plugin, 'license_key_123');

        $this->assertTrue($plugin->auditLog()->where('action', 'installed')->exists());
    }

    public function test_install_paid_plugin_without_license_key_throws(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Paid Plugin',
            'slug' => 'paid-plugin',
            'description' => 'Test description',
            'composer_name' => 'vendor/paid-plugin',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'price_once' => 99,
        ]);

        $mockComposer = Mockery::mock(ComposerRunner::class);
        $this->app->instance(ComposerRunner::class, $mockComposer);

        $action = new InstallPluginAction($mockComposer);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot install paid plugin without license key');

        $action->handle($plugin);
    }

    public function test_composer_config_failure_throws(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Paid Plugin',
            'slug' => 'paid-plugin',
            'description' => 'Test description',
            'composer_name' => 'vendor/paid-plugin',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'price_once' => 99,
        ]);

        $mockComposer = Mockery::mock(ComposerRunner::class);
        $mockComposer->shouldReceive('configureAnystackRepo')
            ->once()
            ->andReturn(new ComposerResult(1, '', 'Auth failed'));
        $this->app->instance(ComposerRunner::class, $mockComposer);

        $action = new InstallPluginAction($mockComposer);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to configure Anystack repository');

        $action->handle($plugin, 'invalid_key');
    }

    public function test_composer_install_failure_logs_and_throws(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test description',
            'composer_name' => 'vendor/plugin',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
        ]);

        $mockComposer = Mockery::mock(ComposerRunner::class);
        $mockComposer->shouldReceive('requirePackage')
            ->once()
            ->andReturn(new ComposerResult(1, '', 'Package not found error'));
        $this->app->instance(ComposerRunner::class, $mockComposer);

        $action = new InstallPluginAction($mockComposer);

        $this->expectException(RuntimeException::class);

        try {
            $action->handle($plugin);
        } finally {
            $this->assertTrue($plugin->auditLog()->where('action', 'install_failed')->exists());
        }
    }

    public function test_preview_capability_warnings_returns_correct_highest_level(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test description',
            'composer_name' => 'vendor/plugin',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'capabilities' => ['db_schema_changes', 'http_outbound:capell.app', 'reads_secrets'],
        ]);

        $mockComposer = Mockery::mock(ComposerRunner::class);
        $action = new InstallPluginAction($mockComposer);

        $warnings = $action->previewCapabilityWarnings($plugin);

        $this->assertEquals(CapabilityWarningLevel::Red, $warnings->highestLevel);
        $this->assertCount(3, $warnings->warnings);
    }

    public function test_preview_capability_warnings_empty_returns_green(): void
    {
        $plugin = MarketplacePlugin::create([
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'description' => 'Test description',
            'composer_name' => 'vendor/plugin',
            'vendor' => 'vendor',
            'kind' => PluginKind::Full,
            'license_model' => LicenseModel::Free,
            'latest_version' => '1.0.0',
            'capabilities' => [],
        ]);

        $mockComposer = Mockery::mock(ComposerRunner::class);
        $action = new InstallPluginAction($mockComposer);

        $warnings = $action->previewCapabilityWarnings($plugin);

        $this->assertEquals(CapabilityWarningLevel::Green, $warnings->highestLevel);
        $this->assertCount(0, $warnings->warnings);
    }
}
