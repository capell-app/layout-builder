<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Feature;

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Actions\BuildPublicPageRenderDataAction;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Actions\SeedWidgetIntegrityScreenshotFixturesAction;
use Capell\LayoutBuilder\Actions\WidgetExtensions\RenderPublicWidgetExtensionAction;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Tests\LayoutBuilderTestCase;
use Capell\Tests\Fixtures\Models\User;
use Capell\WidgetAudioPlaylist\Data\AudioPlaylistInputData;
use Capell\WidgetBeforeAfter\Data\BeforeAfterInputData;
use Capell\WidgetContentReveal\Data\ContentRevealInputData;
use Capell\WidgetCountdown\Data\CountdownInputData;
use Capell\WidgetDataChart\Data\DataChartInputData;
use Capell\WidgetHotspots\Data\HotspotsInputData;
use Capell\WidgetLivePoll\Data\LivePollInputData;
use Capell\WidgetLivePoll\Models\Poll;
use Capell\WidgetLocationMap\Data\LocationMapInputData;
use Capell\WidgetSlideshow\Data\SlideshowInputData;
use Capell\WidgetYouTube\Data\YouTubeInputData;
use Filament\Facades\Filament;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Override;
use RuntimeException;
use Workbench\App\Actions\PrepareWidgetScreenshotsAction;
use Workbench\App\Support\WidgetScreenshotFixtureRoutes;
use Workbench\App\Support\WidgetScreenshotFixtures;

final class LayoutBuilderScreenshotFixturesTest extends LayoutBuilderTestCase
{
    public function test_it_renders_safe_public_layout_builder_screenshot_fixtures(): void
    {
        // Livewire's auto-injected asset state is request-scoped, but the
        // Testbench lifecycle does not flush it between ordinary HTTP tests.
        // These routes intentionally render standalone public HTML.
        Livewire::flushState();

        $fixtures = [
            '/screenshot-fixtures/layout-builder/main-sidebar' => [
                'Main content with sidebar',
                'A composed page with supporting content',
                'On this page',
                'Strategy content block',
            ],
            '/screenshot-fixtures/layout-builder/full-width' => [
                'Full width content',
                'Full width sections for broad storytelling',
                'Feature grid',
                'Supporting editorial copy',
            ],
            '/screenshot-fixtures/layout-builder/preset-action' => [
                'Layout preset action',
                'Save this container as a preset',
                'Reusable layout patterns',
                'Fixture state',
            ],
            '/screenshot-fixtures/layout-builder/undo-redo-actions' => [
                'Undo and redo actions',
                'Recover from layout changes',
                'Undo mutation',
                'Redo mutation',
            ],
            '/screenshot-fixtures/layout-builder/bulk-change-criteria' => [
                'Bulk change criteria',
                'Scope the layouts to update',
                'Bulk layout operations',
                'Ready for review',
            ],
            '/screenshot-fixtures/layout-builder/bulk-change-review' => [
                'Bulk change review',
                'Review affected layouts before approval',
                'Safe review step',
                'Hash guarded',
            ],
        ];

        foreach ($fixtures as $path => $visibleContent) {
            $response = $this->get($path);

            $response->assertOk();

            foreach ($visibleContent as $content) {
                $response->assertSee($content, false);
            }

            $html = (string) $response->getContent();

            $this->assertStringNotContainsString('data-layout-builder-editor', $html);
            $this->assertStringNotContainsString('wire:', $html);
            $this->assertStringNotContainsString('signed', $html);
            $this->assertStringNotContainsString('filament', $html);
        }
    }

    public function test_it_rejects_unknown_layout_builder_screenshot_fixture_screens(): void
    {
        $this->get('/screenshot-fixtures/layout-builder/missing')->assertNotFound();
    }

    public function test_it_renders_the_real_visual_editor_for_the_admin_editor_capture(): void
    {
        $language = Language::factory()->create();
        $site = Site::factory()
            ->language($language)
            ->withTranslations($language)
            ->create(['language_id' => $language->getKey()]);
        $admin = User::factory()->create()->assignRole('super_admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
        Filament::setServingStatus();

        $this->actingAs($admin)
            ->get('/screenshot-fixtures/layout-builder-admin-editor')
            ->assertOk()
            ->assertSee('data-layout-builder-surface="visual-editor"', false)
            ->assertSee('class="layout-builder-visual-toolbar"', false)
            ->assertSee('data-layout-builder-tree-item="main"', false)
            ->assertSee('data-layout-builder-tree-item="sidebar"', false);

        $this->assertTrue($site->default);
    }

    public function test_it_seeds_widget_integrity_screenshot_data_idempotently_before_capture(): void
    {
        SeedWidgetIntegrityScreenshotFixturesAction::run();

        $firstFixtureState = WidgetAsset::query()
            ->whereIn('container', [
                SeedWidgetIntegrityScreenshotFixturesAction::BROKEN_ASSET_CONTAINER,
                SeedWidgetIntegrityScreenshotFixturesAction::UNSCOPED_ASSET_CONTAINER,
            ])
            ->orderBy('container')
            ->get(['container', 'updated_at'])
            ->mapWithKeys(fn (WidgetAsset $asset): array => [$asset->container => $asset->updated_at?->toAtomString()])
            ->all();

        SeedWidgetIntegrityScreenshotFixturesAction::run();

        $secondFixtureState = WidgetAsset::query()
            ->whereIn('container', [
                SeedWidgetIntegrityScreenshotFixturesAction::BROKEN_ASSET_CONTAINER,
                SeedWidgetIntegrityScreenshotFixturesAction::UNSCOPED_ASSET_CONTAINER,
            ])
            ->orderBy('container')
            ->get(['container', 'updated_at'])
            ->mapWithKeys(fn (WidgetAsset $asset): array => [$asset->container => $asset->updated_at?->toAtomString()])
            ->all();

        $this->assertSame($firstFixtureState, $secondFixtureState);
        $this->assertDatabaseCount('widgets', 2);
        $this->assertDatabaseCount('widget_assets', 2);
    }

    public function test_it_does_not_register_screenshot_fixture_seeding_in_the_package_console(): void
    {
        $this->assertArrayNotHasKey('capell:layout-builder-seed-screenshot-integrity-fixtures', $this->app->make(Kernel::class)->all());
    }

    public function test_it_protects_and_renders_widget_integrity_tables_without_mutating_capture_gets(): void
    {
        $usagePath = '/screenshot-fixtures/layout-builder-integrity/widget-usage';
        $assetsPath = '/screenshot-fixtures/layout-builder-integrity/widget-assets';

        $this->get($usagePath)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($usagePath)->assertForbidden();

        SeedWidgetIntegrityScreenshotFixturesAction::run();

        $beforeCapture = WidgetAsset::query()
            ->whereIn('container', [
                SeedWidgetIntegrityScreenshotFixturesAction::BROKEN_ASSET_CONTAINER,
                SeedWidgetIntegrityScreenshotFixturesAction::UNSCOPED_ASSET_CONTAINER,
            ])
            ->orderBy('container')
            ->get(['container', 'updated_at'])
            ->mapWithKeys(fn (WidgetAsset $asset): array => [$asset->container => $asset->updated_at?->toAtomString()])
            ->all();

        $admin = User::factory()->create()->assignRole('super_admin');

        $this->actingAs($admin)->get($usagePath)
            ->assertOk()
            ->assertSee('data-layout-builder-integrity-table="widgets"', false)
            ->assertSee('Screenshot unused and disabled widget', false)
            ->assertSee('Unused', false)
            ->assertDontSee('Edit', false);

        $this->actingAs($admin)->get($assetsPath)
            ->assertOk()
            ->assertSee('data-layout-builder-integrity-table="widget-assets"', false)
            ->assertSee('Broken asset', false)
            ->assertSee('Not placed', false)
            ->assertDontSee('Add Asset', false)
            ->assertDontSee('Edit', false);

        $afterCapture = WidgetAsset::query()
            ->whereIn('container', [
                SeedWidgetIntegrityScreenshotFixturesAction::BROKEN_ASSET_CONTAINER,
                SeedWidgetIntegrityScreenshotFixturesAction::UNSCOPED_ASSET_CONTAINER,
            ])
            ->orderBy('container')
            ->get(['container', 'updated_at'])
            ->mapWithKeys(fn (WidgetAsset $asset): array => [$asset->container => $asset->updated_at?->toAtomString()])
            ->all();

        $this->assertSame($beforeCapture, $afterCapture);

        $this->assertDatabaseHas('widgets', [
            'key' => SeedWidgetIntegrityScreenshotFixturesAction::UNUSED_WIDGET_KEY,
            'status' => false,
        ]);
        $this->assertDatabaseHas('widget_assets', [
            'container' => SeedWidgetIntegrityScreenshotFixturesAction::BROKEN_ASSET_CONTAINER,
            'asset_id' => 999999999,
        ]);
        $this->assertDatabaseHas('widget_assets', [
            'container' => SeedWidgetIntegrityScreenshotFixturesAction::UNSCOPED_ASSET_CONTAINER,
        ]);

        $this->actingAs($admin)->get('/screenshot-fixtures/layout-builder-integrity/missing')->assertNotFound();
        $this->get('/screenshot-fixtures/login')->assertNotFound();
    }

    public function test_widget_captures_require_seeded_records_and_use_the_real_product_routes(): void
    {
        $this->get('/screenshot-fixtures/widgets/countdown/editor')->assertRedirect('/admin/login');
        $this->get('/screenshot-fixtures/widgets/countdown/public')->assertNotFound();

        $admin = User::factory()->create();
        $this->actingAs($admin);

        foreach ([...array_keys($this->widgetScreenshotFixtureDefinitions()), 'showcase'] as $widget) {
            $page = Page::factory()->create([
                'uuid' => WidgetScreenshotFixtures::uuid('page-' . $widget),
            ]);

            $this->get(sprintf('/screenshot-fixtures/widgets/%s/editor', $widget))
                ->assertRedirect('/admin/pages/' . $page->getRouteKey() . '/edit')
                ->assertDontSee('fixture-editor', false);
            $this->get(sprintf('/screenshot-fixtures/widgets/%s/public', $widget))
                ->assertRedirect('/widget-showcase/' . $widget)
                ->assertDontSee('Representative workflow', false);
        }

        $this->get('/screenshot-fixtures/widgets/not-a-widget/public')->assertNotFound();
        $this->get('/screenshot-fixtures/widgets/youtube/unknown')->assertNotFound();
    }

    public function test_widget_screenshot_states_pass_the_shipped_input_validation(): void
    {
        $classes = [
            'audio-playlist' => AudioPlaylistInputData::class,
            'before-after' => BeforeAfterInputData::class,
            'content-reveal' => ContentRevealInputData::class,
            'countdown' => CountdownInputData::class,
            'data-chart' => DataChartInputData::class,
            'hotspots' => HotspotsInputData::class,
            'live-poll' => LivePollInputData::class,
            'location-map' => LocationMapInputData::class,
            'slideshow' => SlideshowInputData::class,
            'youtube' => YouTubeInputData::class,
        ];

        foreach (WidgetScreenshotFixtures::states() as $widget => $state) {
            $this->assertInstanceOf($classes[$widget], $classes[$widget]::validateAndCreate($state));
        }

        $instances = [];
        foreach (WidgetScreenshotFixtures::blocks('showcase') as $block) {
            $metadata = $block['data']['__capell'];
            $this->assertIsArray($metadata);
            $this->assertArrayHasKey('instance_id', $metadata);
            $this->assertIsString($metadata['instance_id']);
            $instances[] = $metadata['instance_id'];
        }

        $this->assertCount(count($instances), array_unique($instances));
        $this->assertContains(WidgetScreenshotFixtures::uuid('showcase-' . WidgetScreenshotFixtures::uuid('content-reveal-target')), $instances);
        $this->assertNotContains(WidgetScreenshotFixtures::uuid('live-poll'), $instances);
    }

    public function test_widget_preparation_creates_real_pages_media_and_poll_state_idempotently(): void
    {
        Storage::fake('public');
        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 3) . '/widget-live-poll/database/migrations',
            '--realpath' => true,
        ])->assertExitCode(0);
        foreach (['AudioPlaylist', 'BeforeAfter', 'ContentReveal', 'Countdown', 'DataChart', 'Hotspots', 'LivePoll', 'LocationMap', 'Slideshow', 'YouTube'] as $name) {
            $provider = 'Capell\\Widget' . $name . '\\Providers\\Widget' . $name . 'ServiceProvider';
            $this->app->register($provider);
        }

        $site = Site::factory()->create(['default' => true]);
        SiteDomain::query()->updateOrCreate([
            'site_id' => $site->id, 'domain' => 'localhost', 'path' => null,
        ], ['scheme' => 'http', 'language_id' => $site->language_id, 'status' => true, 'default' => true]);
        Page::factory()->site($site)->create(['name' => 'Existing page']);
        $initialPages = Page::query()->count();
        $originalFixture = getenv('CAPELL_SCREENSHOT_FIXTURE');
        $originalPath = getenv('CAPELL_SCREENSHOT_APP_PATH');
        putenv('CAPELL_SCREENSHOT_FIXTURE=record-state');
        putenv('CAPELL_SCREENSHOT_APP_PATH=' . base_path());

        try {
            $action = resolve(PrepareWidgetScreenshotsAction::class);
            $action->handle();
            $action->handle();

            $this->assertSame($initialPages + 11, Page::query()->count());
            $this->assertSame(2, Poll::query()->count());
            foreach ([...array_keys(WidgetScreenshotFixtures::states()), 'showcase'] as $widget) {
                $page = Page::query()->where('uuid', WidgetScreenshotFixtures::uuid('page-' . $widget))->sole();
                $this->assertSame(ContentStructure::Blocks, $page->content_structure);
                $this->assertTrue($page->pageUrls()->exists(), $widget . ' must have a real public URL');
                $page->setRelation('translation', $page->translations()->firstOrFail());
                $context = new FrontendRenderContextData($page, $site, $site->language, $page->layout, $site->theme);
                $payloads = BuildPublicPageRenderDataAction::run($context);
                foreach (WidgetScreenshotFixtures::blocks($widget) as $block) {
                    $html = resolve(RenderPublicWidgetExtensionAction::class)->render($block, $payloads);
                    $this->assertStringContainsString('class="capell-', $html, $widget);
                    $this->assertStringNotContainsString('Representative workflow', $html);
                    $this->assertStringNotContainsString('__capell', $html);
                    $this->assertStringNotContainsString('state_version', $html);
                    if ($block['type'] === 'capell-app.content-reveal') {
                        $this->assertStringContainsString('data-capell-interaction-target-url=', $html);
                    }

                    if ($block['type'] === 'capell-app.live-poll') {
                        $this->assertStringContainsString('data-poll-form', $html);
                    }
                }

                $content = $page->translations()->firstOrFail()->getRawOriginal('content');
                $this->assertIsString($content);
                $this->assertStringContainsString('capell-app.', $content);
            }

            Storage::disk('public')->assertExists([
                'widget-screenshots/workspace.jpg', 'widget-screenshots/workflow.jpg', 'widget-screenshots/reference.wav',
            ]);
        } finally {
            putenv($originalFixture === false ? 'CAPELL_SCREENSHOT_FIXTURE' : 'CAPELL_SCREENSHOT_FIXTURE=' . $originalFixture);
            putenv($originalPath === false ? 'CAPELL_SCREENSHOT_APP_PATH' : 'CAPELL_SCREENSHOT_APP_PATH=' . $originalPath);
        }
    }

    public function test_widget_preparation_refuses_an_unselected_application(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('explicitly selected disposable application');
        resolve(PrepareWidgetScreenshotsAction::class)->handle();
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        $app->make(Repository::class)->set('app.key', 'base64:' . base64_encode('12345678901234567890123456789012'));

        $migrationWorkspace = storage_path('framework/testing-migrations');

        if (! is_dir($migrationWorkspace)) {
            mkdir($migrationWorkspace, 0775, true);
        }
    }

    #[Override]
    protected function defineRoutes($router): void
    {
        throw_unless($router instanceof Router, RuntimeException::class, 'Expected the layout builder screenshot fixture router.');

        if (! function_exists('registerLayoutBuilderScreenshotFixtureRoutes')) {
            require dirname(__DIR__, 4) . '/workbench/routes/screenshot-fixtures.php';
        }

        $this->registerFixtureRoutes('registerLayoutBuilderScreenshotFixtureRoutes');
        WidgetScreenshotFixtureRoutes::register();
    }

    /**
     * @return array<string, mixed>
     */
    private function widgetScreenshotFixtureDefinitions(): array
    {
        return WidgetScreenshotFixtureRoutes::definitions();
    }

    private function registerFixtureRoutes(string $callback): void
    {
        $routeRegistrar = $this->fixtureCallback($callback);

        $routeRegistrar();
    }

    private function fixtureCallback(string $callback): callable
    {
        throw_unless(is_callable($callback), RuntimeException::class, sprintf('Screenshot fixture callback [%s] is unavailable.', $callback));

        return $callback;
    }
}
