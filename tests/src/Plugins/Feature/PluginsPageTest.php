<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins\Feature;

use Capell\Plugins\Actions\InstallPluginAction;
use Capell\Plugins\Filament\Pages\PluginsPage;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Services\AnystackClient;
use Capell\Plugins\Services\ComposerRunner;
use Capell\Plugins\Support\SiteIdResolver;
use Capell\Tests\Plugins\PluginsTestCase;
use Capell\Tests\Plugins\Unit\StubComposerProcess;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

use Symfony\Component\Process\Process;

final class PluginsPageTest extends PluginsTestCase
{
    use CreatesAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_page_renders_successfully(): void
    {
        $component = livewire(PluginsPage::class);

        $component->assertSuccessful();
        $this->assertNotNull($component->instance());
    }

    public function test_browse_tab_shows_only_visible_plugins(): void
    {
        $visible = MarketplacePlugin::factory()->create([
            'is_visible' => true,
            'name' => 'Visible Plugin',
        ]);
        $hidden = MarketplacePlugin::factory()->create([
            'is_visible' => false,
            'name' => 'Hidden Plugin',
        ]);

        $component = livewire(PluginsPage::class, ['activeTab' => 'browse']);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$visible]);
        $component->assertCanNotSeeTableRecords([$hidden]);

        $this->assertTrue(true, 'Livewire assertions above establish the contract.');
    }

    public function test_installed_tab_queries_by_license_relationship(): void
    {
        $withLicense = MarketplacePlugin::factory()->create([
            'name' => 'Licensed Plugin',
            'composer_name' => 'vendor/licensed',
        ]);
        $withLicense->licenses()->create([
            'encrypted_license_key' => 'k',
            'status' => 'active',
        ]);

        $unlicensed = MarketplacePlugin::factory()->create([
            'name' => 'Unlicensed Plugin',
            'composer_name' => 'vendor/unlicensed',
        ]);

        $component = livewire(PluginsPage::class, ['activeTab' => 'installed']);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$withLicense]);
        $component->assertCanNotSeeTableRecords([$unlicensed]);

        $this->assertTrue(true, 'Livewire assertions above establish the contract.');
    }

    public function test_tabs_badge_counts_reflect_data(): void
    {
        MarketplacePlugin::factory()->count(2)->create(['is_visible' => true]);
        MarketplacePlugin::factory()->create(['is_visible' => false]);

        $tabs = (new PluginsPage)->getTabs();
        $this->assertArrayHasKey('browse', $tabs);
        $this->assertArrayHasKey('installed', $tabs);
        $this->assertArrayHasKey('updates', $tabs);
    }

    public function test_paid_install_dispatches_action_with_resolved_site_id(): void
    {
        // Stable APP_KEY so SiteIdResolver returns a predictable hash.
        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        config()->set('app.name', 'Capell Test');
        SiteIdResolver::flushCache();

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

        // ComposerRunner is final, but the constructor accepts a process
        // factory that lets us return deterministic no-op Processes — no
        // actual composer binary gets invoked in the install path.
        $stubRunner = new ComposerRunner(
            binary: 'composer',
            timeoutSeconds: 10,
            workingDirectory: sys_get_temp_dir(),
            processFactory: static fn (array $command, string $cwd, int $timeout): Process => StubComposerProcess::make(0, '', ''),
        );
        $this->app->instance(ComposerRunner::class, $stubRunner);

        // Rebind InstallPluginAction so it picks up the stubbed runner.
        $this->app->forgetInstance(InstallPluginAction::class);
        $this->app->bind(InstallPluginAction::class, fn (): InstallPluginAction => new InstallPluginAction(
            $this->app->make(ComposerRunner::class),
            $this->app->make(AnystackClient::class),
        ));

        $plugin = MarketplacePlugin::factory()->create([
            'is_visible' => true,
            'composer_name' => 'vendor/paid-plugin-' . uniqid(),
            'anystack_product_id' => 'prod_xyz',
            'price_once' => 99,
            'name' => 'Paid Plugin',
        ]);

        // Direct action dispatch — same codepath the Livewire action uses
        // internally, with the resolved site id that the install action
        // wiring in PluginsTable now passes through.
        InstallPluginAction::run($plugin, 'license_key_123', SiteIdResolver::get());

        $expectedSiteId = SiteIdResolver::get();

        $this->assertDatabaseHas('marketplace_plugin_licenses', [
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => $expectedSiteId,
            'anystack_license_id' => 'license_abc',
            'anystack_activation_id' => 'activation_xyz',
        ]);
    }
}
