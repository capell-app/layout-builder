<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins\Feature;

use Capell\Plugins\Filament\Resources\MarketplacePlugin\Pages\ListMarketplacePlugins;
use Capell\Plugins\Filament\Resources\MarketplacePluginResource;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Tests\Plugins\PluginsTestCase;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

/**
 * Resource smoke test.
 *
 * Full create/edit form coverage is deferred: the Create/Edit Livewire
 * component's persisted form state depends on TagsInput and JSON textarea
 * fields whose empty-string vs null semantics require extensive setup to
 * exercise end-to-end. The list-page + resource-metadata checks here catch
 * boot-time regressions (navigation icon types, record title, model binding).
 * Create/Edit behavior is covered indirectly by InstallPluginActionTest and
 * MarketplacePluginModelTest.
 */
final class MarketplacePluginResourceTest extends PluginsTestCase
{
    use CreatesAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_list_page_renders_with_visible_plugin(): void
    {
        $plugin = MarketplacePlugin::factory()->create(['name' => 'Alpha']);

        $component = livewire(ListMarketplacePlugins::class);
        $component->assertSuccessful();
        $component->assertCanSeeTableRecords([$plugin]);

        $this->assertTrue(true);
    }

    public function test_list_page_shows_no_records_when_empty(): void
    {
        $component = livewire(ListMarketplacePlugins::class);
        $component->assertSuccessful();
        $component->assertCountTableRecords(0);

        $this->assertTrue(true);
    }

    public function test_resource_binds_to_marketplace_plugin_model(): void
    {
        $this->assertSame(MarketplacePlugin::class, MarketplacePluginResource::getModel());
    }

    public function test_resource_registers_expected_pages(): void
    {
        $pages = MarketplacePluginResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }
}
