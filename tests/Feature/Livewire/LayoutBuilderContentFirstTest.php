<?php

declare(strict_types=1);

use Capell\Admin\Data\AdminAssetData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Data\AssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\Frontend\Contracts\AdminAccessCheckerInterface;
use Capell\FrontendAuthoring\Enums\EditableRegionSurface;
use Capell\FrontendAuthoring\Http\Controllers\EditRegionController;
use Capell\FrontendAuthoring\Support\EditableRegionSigner;
use Capell\FrontendAuthoring\Support\EditorSurfaceRegistry;
use Capell\FrontendAuthoring\Support\EditorSurfaces\FieldEditorSurface;
use Capell\HtmlCache\Models\CachedModelUrl;
use Capell\HtmlCache\Support\Cache\HtmlCachePathResolver;
use Capell\LayoutBuilder\Actions\BuildLayoutBuilderTreeAction;
use Capell\LayoutBuilder\Data\AdminWidgetPreviewData;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditableRegionContributor;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditorSurface;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderNonPublishableAsset;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderNonPublishableAssetEnum;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderNonPublishableAssetForm;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\CssDeclarations;
use Capell\Tests\Support\RenderedHtml;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

afterEach(function (): void {
    SchemaFacade::dropIfExists('layout_builder_non_publishable_assets');
    resolve(PermissionRegistrar::class)->setPermissionsTeamId(null);
    resolve(PermissionRegistrar::class)->teams = false;
    resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    config(['permission.teams' => false]);
});

it('renders the content first editor by default from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first')
        ->assertElementExists('[data-layout-builder-surface="content-editor"]')
        ->assertSee(__('capell-layout-builder::generic.content_first_editor'))
        ->assertSee(__('capell-layout-builder::button.advanced_layout'));

    expect((new RenderedHtml($component->html()))->count('//*[@data-layout-builder-surface="visual-editor"]'))->toBe(0);
});

it('mounts with public widget extension blocks beside editable widgets', function (): void {
    $widget = Widget::factory()->create(['key' => 'page-content']);
    $extensionBlock = [
        'type' => 'capell-app.auth-menu',
        'data' => [
            '__capell' => [
                'instance_id' => 'foundation-header-auth-menu',
                'state_version' => 1,
            ],
        ],
    ];
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            $extensionBlock,
            ['widget_key' => $widget->key],
        ]],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('containers.main.widgets.0', $extensionBlock)
        ->assertSet('containers.main.widgets.1.widget_key', $widget->key)
        ->call('layoutUpdated')
        ->call('saveLayout')
        ->assertHasNoErrors()
        ->assertOk();

    $tree = $component->instance()->layoutBuilderTree();

    expect($tree->containers[0]->widgets)->toHaveCount(1)
        ->and($tree->containers[0]->widgets[0]->widgetIndex)->toBe(1)
        ->and($tree->containers[0]->widgets[0]->nodeId)->toBe(hash('xxh128', 'widget:main:1'))
        ->and($layout->refresh()->containers['main']['widgets'])->toBe([
            $extensionBlock,
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]);
});

it('uses the loaded layout page count for shared layout context', function (): void {
    $site = Site::factory()->createOne();
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);
    $page = Page::factory()
        ->for($site)
        ->createOne(['layout_id' => $layout->getKey()]);

    Page::factory()
        ->count(2)
        ->for($site)
        ->create(['layout_id' => $layout->getKey()]);

    $currentPageCountQueries = [];
    $pageIdColumn = DB::connection()->getQueryGrammar()->wrap('pages.id');

    DB::listen(function (QueryExecuted $query) use (&$currentPageCountQueries, $pageIdColumn): void {
        if (
            str_contains($query->sql, 'count(*)')
            && str_contains($query->sql, 'pages')
            && str_contains($query->sql, $pageIdColumn . ' !=')
        ) {
            $currentPageCountQueries[] = $query->sql;
        }
    });

    Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])
        ->assertSet('layout.pages_count', 3)
        ->assertSee(trans_choice('capell-layout-builder::message.layout_shared_with_other_pages_heading', 2, ['count' => 2]))
        ->assertElementExists('.layout-builder-impact-note-warning');

    expect($currentPageCountQueries)->toBeEmpty();
});

it('renders a full width empty page preview when a layout has no containers', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');

    $layout = Layout::factory()->create(['containers' => []]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::message.layout_empty'))
        ->assertElementExists('.clb-preview-empty-page')
        ->assertElementExists('.layout-builder-shadow-preview-empty');

    $document = new RenderedHtml($component->html());
    expect($document->count('//*[contains(concat(" ", normalize-space(@class), " "), " clb-preview-empty-page ")]'))->toBe(1)
        ->and($document->count('//*[@data-clb-preview-node-type="widget"]'))->toBe(0);

    // Livewire delivers this script separately; execute the public editor
    // factory and parse the CSS returned for its preview shadow root.
    $outcomes = contentFirstEditorOutcomes($component->__get('effects'), <<<'JS'
console.log(JSON.stringify({styles: window.capellLayoutBuilderVisualEditor().shadowStyles()}));
JS);
    $styles = $outcomes['styles'] ?? null;
    throw_unless(is_string($styles), RuntimeException::class, 'Expected preview shadow CSS.');
    $declarations = CssDeclarations::parse($styles);
    expect(collect($declarations)->where('selector', '.clb-preview-empty-page')->where('property', 'grid-column')->pluck('value')->all())
        ->toContain('1 / -1');
});

it('inserts a positioned container after hydrating an empty layout', function (): void {
    $layout = Layout::factory()->create(['containers' => []]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('insertContainerAtPosition', 0)
        ->assertHasNoErrors()
        ->call('saveLayout')
        ->assertHasNoErrors();

    expect(array_keys($component->get('containers')))->toBe(['container-1'])
        ->and($component->get('containers')['container-1']['widgets'])->toBe([])
        ->and($layout->refresh()->containers['container-1']['widgets'])->toBe([])
        ->and(array_keys($layout->refresh()->containers))->toBe(['container-1']);
});

it('duplicates an empty container after hydrating the layout', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('duplicateContainer', 'main')
        ->assertHasNoErrors()
        ->call('saveLayout')
        ->assertHasNoErrors();

    expect(array_keys($component->get('containers')))->toBe(['main', 'container-2'])
        ->and($component->get('containers')['container-2']['widgets'])->toBe([])
        ->and($layout->refresh()->containers['container-2']['widgets'])->toBe([])
        ->and(array_keys($layout->refresh()->containers))->toBe(['main', 'container-2']);
});

it('can switch to the visual editor after mounting the content editor', function (): void {
    $layout = Layout::factory()->create(['containers' => ['main' => ['widgets' => []]]]);
    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first');

    $outcomes = contentFirstEditorOutcomes($component->__get('effects'), <<<'JS'
const editor = window.capellLayoutBuilderVisualEditor({breakpointWidths: {mobile: '390px'}});
editor.$refs = {previewHost: {dataset: {}, style: {}}};
editor.setActiveBreakpointPreview('mobile');
console.log(JSON.stringify({breakpoint: editor.$refs.previewHost.dataset.activeBreakpoint, width: editor.$refs.previewHost.style.maxWidth}));
JS);
    expect($outcomes)->toBe(['breakpoint' => 'mobile', 'width' => '390px']);
    $component->call('showAdvancedLayout')
        ->assertSet('editorMode', 'layout_first')
        ->assertElementExists('[data-layout-builder-surface="visual-editor"]');
});

it('resolves lazy mount scalar identifiers into builder models', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);
    $page = Page::factory()->for($site)->create([
        'layout_id' => $layout->getKey(),
    ]);

    Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])
        ->assertSet('site.id', $site->getKey())
        ->assertSet('layout.id', $layout->getKey())
        ->assertSet('page.id', $page->getKey())
        ->assertSet('editorMode', 'content_first');
});

it('rejects lazy mounted layout builders with a mismatched explicit site', function (): void {
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);
    $page = Page::factory()->for($site)->create([
        'layout_id' => $layout->getKey(),
    ]);

    Livewire::test(LayoutBuilder::class, [
        'siteId' => $otherSite->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])
        ->assertForbidden();
});

it('selects the requested widget when mounted from frontend authoring', function (): void {
    $site = Site::factory()->create();
    $firstWidget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $secondWidget = Widget::factory()->create(['key' => 'proof', 'name' => 'Proof widget']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $firstWidget->key, 'occurrence' => 1],
            ['widget_key' => $secondWidget->key, 'occurrence' => 1],
        ]],
    ]]);
    $page = Page::factory()->for($site)->create([
        'layout_id' => $layout->getKey(),
    ]);

    $component = Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
        'initialContainerKey' => 'main',
        'initialWidgetIndex' => 1,
    ])
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedWidgetIndex', 1);

    $selectedWidget = capell_test_instance($component->instance(), LayoutBuilder::class)->selectedWidget();

    expect($selectedWidget?->name)->toBe('Proof widget')
        ->and($selectedWidget?->relationLoaded('type'))->toBeTrue();
});

it('dispatches frontend authoring dirty and saved lifecycle events', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('layoutUpdated')
        ->assertDispatched('capell-layout-builder-authoring-dirty')
        ->assertNotified(__('capell-layout-builder::message.layout_unsaved'));

    expect($component->instance()->saveLayout())->toBeTrue();
});

it('acknowledges an untouched automatic layout save for page editors without shared layout permission', function (): void {
    [$site, $layout, $page, $editor, $widgetAsset] = contentFirstPageSaveFixture();
    $layoutAttributes = $layout->refresh()->getAttributes();
    $pageAttributes = $page->refresh()->getAttributes();
    $widgetAssetAttributes = $widgetAsset->refresh()->getAttributes();

    expect($editor->isGlobalAdmin())->toBeFalse()
        ->and($editor->getAssignedSiteIds()->all())->toBe([$site->getKey()])
        ->and(Gate::forUser($editor)->allows('update', $page))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editContent', $page))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editLayout', $page))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('editLayout', $layout))->toBeFalse()
        ->and(Gate::forUser($editor)->allows('update', $layout))->toBeFalse();

    $component = Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])
        ->assertSet('editorMode', 'content_first')
        ->assertSet('layoutModified', false)
        ->assertSet('layout.pages_count', 2);
    $containers = $component->get('containers');
    $assets = $component->get('assets');
    $writes = [];
    contentFirstTrackDatabaseWrites($writes);

    $component
        ->dispatch('save-layout', withNotifications: true)
        ->assertOk()
        ->assertHasNoErrors()
        ->assertSet('layoutModified', false)
        ->assertSet('layout.id', $layout->getKey())
        ->assertSet('page.id', $page->getKey())
        ->assertSet('site.id', $site->getKey())
        ->assertSet('containers', $containers)
        ->assertSet('assets', $assets)
        ->assertDispatched('capell-layout-builder-authoring-saved', status: 'published', redirectUrl: null)
        ->assertNotDispatched('layout-builder-reset');

    expect($writes)->toBeEmpty()
        ->and($layout->refresh()->getAttributes())->toBe($layoutAttributes)
        ->and($page->refresh()->getAttributes())->toBe($pageAttributes)
        ->and($widgetAsset->refresh()->getAttributes())->toBe($widgetAssetAttributes);
});

it('forbids modified automatic layout saves for page editors without shared layout permission', function (): void {
    [$site, $layout, $page, $editor, $widgetAsset] = contentFirstPageSaveFixture();
    $layoutAttributes = $layout->refresh()->getAttributes();
    $pageAttributes = $page->refresh()->getAttributes();
    $widgetAssetAttributes = $widgetAsset->refresh()->getAttributes();

    $component = Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])
        ->set('containers.main.meta.html_class', 'unauthorised-layout-change')
        ->set('layoutModified', true);
    $writes = [];
    contentFirstTrackDatabaseWrites($writes);

    $component
        ->dispatch('save-layout', withNotifications: true)
        ->assertForbidden()
        ->assertNotDispatched('capell-layout-builder-authoring-saved');

    expect($writes)->toBeEmpty()
        ->and($layout->refresh()->getAttributes())->toBe($layoutAttributes)
        ->and($page->refresh()->getAttributes())->toBe($pageAttributes)
        ->and($widgetAsset->refresh()->getAttributes())->toBe($widgetAssetAttributes);
});

it('keeps direct untouched layout saves forbidden for page editors without shared layout permission', function (): void {
    [$site, $layout, $page, $editor, $widgetAsset] = contentFirstPageSaveFixture();
    $layoutAttributes = $layout->refresh()->getAttributes();
    $widgetAssetAttributes = $widgetAsset->refresh()->getAttributes();

    $component = Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])->assertSet('layoutModified', false);
    $writes = [];
    contentFirstTrackDatabaseWrites($writes);

    $component
        ->call('saveLayout', withNotifications: true)
        ->assertForbidden()
        ->assertNotDispatched('capell-layout-builder-authoring-saved');

    expect($writes)->toBeEmpty()
        ->and($layout->refresh()->getAttributes())->toBe($layoutAttributes)
        ->and($widgetAsset->refresh()->getAttributes())->toBe($widgetAssetAttributes);
});

it('forbids an automatic untouched layout save after its site no longer matches the page', function (): void {
    [$site, $layout, $page] = contentFirstPageSaveFixture();
    $component = Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])->assertSet('layoutModified', false);

    $layout->update(['site_id' => Site::factory()->create()->getKey()]);
    $layoutAttributes = $layout->refresh()->getAttributes();
    $writes = [];
    contentFirstTrackDatabaseWrites($writes);

    $component
        ->dispatch('save-layout', withNotifications: true)
        ->assertForbidden()
        ->assertNotDispatched('capell-layout-builder-authoring-saved');

    expect($writes)->toBeEmpty()
        ->and($layout->refresh()->getAttributes())->toBe($layoutAttributes);
});

it('forbids an automatic untouched layout save after the page editors site role is revoked', function (): void {
    [$site, $layout, $page, $editor] = contentFirstPageSaveFixture();
    $layoutAttributes = $layout->refresh()->getAttributes();
    $component = Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])->assertSet('layoutModified', false);

    $editor->removeRoleForSite($site, 'page-save-content-editor');
    expect($editor->getAssignedSiteIds())->toBeEmpty();
    $writes = [];
    contentFirstTrackDatabaseWrites($writes);

    $component
        ->dispatch('save-layout', withNotifications: true)
        ->assertForbidden()
        ->assertNotDispatched('capell-layout-builder-authoring-saved');

    expect($writes)->toBeEmpty()
        ->and($layout->refresh()->getAttributes())->toBe($layoutAttributes);
});

it('rejects an automatic untouched layout save after the page editor signs out', function (): void {
    [$site, $layout, $page] = contentFirstPageSaveFixture();
    $layoutAttributes = $layout->refresh()->getAttributes();
    $component = Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
    ])->assertSet('layoutModified', false);

    Filament::auth()->logout();
    test()->withoutExceptionHandling();
    $writes = [];
    contentFirstTrackDatabaseWrites($writes);

    expect(function () use ($component): void {
        $component->dispatch('save-layout', withNotifications: true);
    })->toThrow(AuthenticationException::class);

    $component->assertNotDispatched('capell-layout-builder-authoring-saved');

    expect($writes)->toBeEmpty()
        ->and($layout->refresh()->getAttributes())->toBe($layoutAttributes);
});

it('persists authorised automatic layout saves with the existing notifications and lifecycle', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('insertContainerAtPosition', 1)
        ->assertSet('layoutModified', true)
        ->dispatch('save-layout', withNotifications: true)
        ->assertOk()
        ->assertHasNoErrors()
        ->assertSet('layoutModified', false)
        ->assertDispatched('layout-builder-reset')
        ->assertDispatched('capell-layout-builder-authoring-saved', status: 'published', redirectUrl: null)
        ->assertNotified(Notification::make('layout-saved')
            ->body(__('capell-layout-builder::message.layout_saved'))
            ->success());

    expect(array_keys($layout->refresh()->containers))->toBe(['main', 'container-2'])
        ->and($layout->containers['container-2']['widgets'])->toBe([]);
});

it('reports stale layout saves without writes and recovers using the other editors layout', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('insertContainerAtPosition', 1)
        ->assertSet('layoutModified', true);

    $otherEditorContainers = [
        'main' => ['widgets' => [], 'meta' => ['html_class' => 'other-editor']],
        'other-editor' => ['widgets' => [], 'meta' => []],
    ];
    Layout::query()->whereKey($layout->getKey())->firstOrFail()->forceFill([
        'containers' => $otherEditorContainers,
        'updated_at' => now()->addMinute(),
    ])->save();
    $otherEditorAttributes = $layout->refresh()->getAttributes();
    $writes = [];
    contentFirstTrackDatabaseWrites($writes);

    $component
        ->dispatch('save-layout', withNotifications: true)
        ->assertOk()
        ->assertHasErrors(['layoutConflict' => 'The layout changed in another editor. Reload before saving.'])
        ->assertSet('layout.id', $layout->getKey())
        ->assertSet('containers', $otherEditorContainers)
        ->assertSet('expectedLayoutUpdatedAt', $layout->updated_at?->toIso8601String())
        ->assertNotDispatched('capell-layout-builder-authoring-saved')
        ->assertNotDispatched('layout-builder-reset');

    expect($writes)->toBeEmpty()
        ->and($layout->refresh()->getAttributes())->toBe($otherEditorAttributes);

    $component
        ->call('insertContainerAtPosition', 2)
        ->dispatch('save-layout', withNotifications: true)
        ->assertOk()
        ->assertHasNoErrors()
        ->assertSet('layoutModified', false)
        ->assertDispatched('layout-builder-reset')
        ->assertDispatched('capell-layout-builder-authoring-saved', status: 'published', redirectUrl: null);

    expect($writes)->not->toBeEmpty()
        ->and($layout->refresh()->containers)->toBe([
            ...$otherEditorContainers,
            'container-3' => ['widgets' => []],
        ]);
});

it('reports a permanently deleted layout as a conflict without writes', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('insertContainerAtPosition', 1);

    $instance = $component->instance();
    if (! $instance instanceof LayoutBuilder) {
        throw new RuntimeException('Expected a Layout Builder component.');
    }

    $instance->hydrate();
    Layout::query()->whereKey($layout->getKey())->firstOrFail()->forceDelete();
    $writes = [];
    contentFirstTrackDatabaseWrites($writes);

    expect($instance->saveLayout(withNotifications: true))->toBeFalse()
        ->and($instance->getErrorBag()->first('layoutConflict'))->toBe('The layout no longer exists.')
        ->and($writes)->toBeEmpty()
        ->and(Layout::query()->withoutGlobalScopes()->whereKey($layout->getKey())->exists())->toBeFalse();
});

it('clears cached pages affected by frontend authoring widget edits', function (): void {
    Storage::fake('page_cache');
    config(['capell-admin.auto_refresh_cache' => false]);

    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $siteDomain = SiteDomain::factory()
        ->for($site)
        ->for($language)
        ->create([
            'scheme' => 'https',
            'domain' => 'example.test',
            'path' => null,
            'status' => true,
        ]);
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);
    $page = Page::factory()->for($site)->create([
        'layout_id' => $layout->getKey(),
    ]);

    $pathResolver = resolve(HtmlCachePathResolver::class);
    $urls = [
        'https://example.test/page-using-hero',
        'https://example.test/another-page-using-hero',
    ];

    foreach ($urls as $url) {
        $cachePath = $pathResolver->pathForUrl(parse_url($url, PHP_URL_PATH) ?: '/', $siteDomain);

        Storage::disk('page_cache')->put($cachePath, 'stale cached html');

        CachedModelUrl::query()->create([
            'cacheable_type' => $widget->getMorphClass(),
            'cacheable_id' => $widget->getKey(),
            'url' => $url,
            'url_hash' => CachedModelUrl::hashUrl($url),
            'site_id' => $site->getKey(),
            'site_domain_id' => $siteDomain->getKey(),
            'language_id' => $language->getKey(),
            'path' => parse_url($url, PHP_URL_PATH) ?: '/',
            'cached_at' => now(),
        ]);
    }

    $imageUrl = 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=720&q=75';

    Livewire::test(LayoutBuilder::class, [
        'siteId' => $site->getKey(),
        'layoutId' => $layout->getKey(),
        'pageId' => $page->getKey(),
        'pageClass' => Page::class,
        'initialContainerKey' => 'main',
        'initialWidgetIndex' => 0,
    ])
        ->mountAction('editWidget', arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
        ])
        ->assertSchemaComponentExists('meta.image_source.type')
        ->assertSchemaComponentExists('meta.image_source.url')
        ->setActionData([
            ...$widget->attributesToArray(),
            'name' => 'Updated hero banner',
            'meta' => [
                ...(array) $widget->meta,
                'image_source' => [
                    'type' => 'url',
                    'url' => $imageUrl,
                ],
            ],
        ])
        ->callMountedAction([
            'containerKey' => 'main',
            'widgetIndex' => 0,
        ])
        ->assertHasNoActionErrors()
        ->assertDispatched('capell-layout-builder-authoring-saved');

    expect($widget->fresh()->meta['image_source']['url'] ?? null)->toBe($imageUrl);

    foreach ($urls as $url) {
        $cachePath = $pathResolver->pathForUrl(parse_url($url, PHP_URL_PATH) ?: '/', $siteDomain);

        expect(Storage::disk('page_cache')->exists($cachePath))->toBeFalse()
            ->and(CachedModelUrl::query()->where('url', $url)->exists())->toBeFalse();
    }
});

it('contributes frontend authoring regions for page layout widgets and widget assets', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $siteDomain = SiteDomain::factory()
        ->for($site)
        ->for($language)
        ->create([
            'scheme' => 'https',
            'domain' => 'example.test',
            'path' => null,
            'status' => true,
        ]);
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);
    $page = Page::factory()->for($site)->create([
        'layout_id' => $layout->getKey(),
    ]);
    $translation = Translation::factory()
        ->translatable($page)
        ->language($language)
        ->create();
    $pageUrl = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->page($page)
        ->create(['url' => '/layout-authoring']);

    $page->setRelation('translation', $translation);
    $pageUrl->setRelation('pageable', $page);
    $pageUrl->setRelation('siteDomain', $siteDomain);

    $regions = (new LayoutBuilderEditableRegionContributor)($pageUrl);

    expect($regions)->toHaveCount(3)
        ->and(collect($regions)->pluck('surface')->unique()->values()->all())->toBe([EditableRegionSurface::LayoutBuilder])
        ->and(collect($regions)->pluck('field')->all())->toBe(['layout', 'widget', 'assets'])
        ->and($regions[1]->selector)->toBe(LayoutBuilderEditableRegionContributor::widgetSelector((int) $layout->getKey(), 'main', 0))
        ->and($regions[1]->context)->toMatchArray([
            'layoutId' => $layout->getKey(),
            'siteId' => $site->getKey(),
            'pageId' => $page->getKey(),
            'pageClass' => Page::class,
            'initialContainerKey' => 'main',
            'initialWidgetIndex' => 0,
        ]);

    $payload = resolve(EditableRegionSigner::class)->decode(resolve(EditableRegionSigner::class)->encode($regions[1]));

    expect($payload->surface)->toBe(EditableRegionSurface::LayoutBuilder)
        ->and($payload->target)->toBe('layout.widget.main.0');
});

it('renders the signed frontend authoring layout builder editor surface', function (): void {
    $this->artisan('migrate', [
        '--path' => dirname(__DIR__, 4) . '/frontend-authoring/database/migrations',
        '--realpath' => true,
    ])->assertExitCode(0);

    view()->addNamespace('capell', __DIR__ . '/../../../../frontend-authoring/resources/views');

    app()->instance(AdminAccessCheckerInterface::class, new readonly class implements AdminAccessCheckerInterface
    {
        public function isAdmin(Authenticatable $user): bool
        {
            return true;
        }
    });
    $registry = new EditorSurfaceRegistry;
    $registry->register(new FieldEditorSurface);
    $registry->register(new LayoutBuilderEditorSurface);

    app()->instance(EditorSurfaceRegistry::class, $registry);
    if (! Route::has('capell-frontend.authoring.edit')) {
        Route::get('authoring/regions/{payload}', EditRegionController::class)
            ->middleware(['web', 'auth', 'signed'])
            ->name('capell-frontend.authoring.edit');
    }

    Gate::before(fn (Authenticatable $user, string $ability): ?bool => $ability === 'frontend-authoring.edit' ? true : null);

    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $siteDomain = SiteDomain::factory()
        ->for($site)
        ->for($language)
        ->create([
            'scheme' => 'https',
            'domain' => 'example.test',
            'path' => null,
            'status' => true,
        ]);
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);
    $page = Page::factory()->for($site)->create([
        'layout_id' => $layout->getKey(),
    ]);
    $translation = Translation::factory()
        ->translatable($page)
        ->language($language)
        ->create();
    $pageUrl = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->page($page)
        ->create(['url' => '/layout-authoring']);

    $page->setRelation('translation', $translation);
    $pageUrl->setRelation('pageable', $page);
    $pageUrl->setRelation('siteDomain', $siteDomain);

    $regions = (new LayoutBuilderEditableRegionContributor)($pageUrl);
    $signedUrl = resolve(EditableRegionSigner::class)->signedEditUrl($regions[1]);

    $response = $this->get($signedUrl);

    $response
        ->assertOk()
        ->assertSee('capell-layout-builder-authoring')
        ->assertSee('css/capell-layout-builder/capell-layout-builder-filament.css')
        ->assertSee('capell-authoring:editor-loaded')
        ->assertSee('capell-layout-builder-authoring-saved')
        ->assertSee('Hero banner');

    $response
        ->assertElementExists('html.fi[lang="en"]')
        ->assertElementExists('[x-cloak]')
        ->assertElementExists('[wire\:snapshot]');
});

it('resolves the saved admin widget preview view without checking the filesystem', function (): void {
    $previewData = new AdminWidgetPreviewData(
        view: 'capell-layout-builder::filament.layout-builder.previews.custom',
        label: 'Custom preview',
        title: null,
        excerpt: null,
        image: null,
        typeLabel: null,
        icon: null,
        assetCount: 0,
        hasPageAssets: false,
        usesPageContent: false,
    );

    $component = new LayoutBuilder;

    expect($component->resolveAdminWidgetPreviewView($previewData))
        ->toBe('capell-layout-builder::filament.layout-builder.previews.custom');
});

it('keeps legacy editor mode transitions available inside the visual editor from the package namespace', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertElementExists('[data-layout-builder-surface="content-editor"]')
        ->call('showAdvancedLayout', 'main:0:1:page:1:0')
        ->assertSet('editorMode', 'layout_first')
        ->assertElementExists('[data-layout-builder-surface="visual-editor"]')
        ->call('showContentEditor')
        ->assertSet('editorMode', 'content_first')
        ->assertSet('returnToContentItemKey', 'main:0:1:page:1:0')
        ->assertElementExists('[data-layout-builder-surface="content-editor"]');
});

it('lets content editors use content first without advanced layout access from the package namespace', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditContent:Layout'));

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first')
        ->assertElementExists('[data-layout-builder-surface="content-editor"]')
        ->assertDontSeeHtml("mountAction('addWidget')");

    expect((new RenderedHtml($component->html()))->count('//*[contains(concat(" ", normalize-space(@class), " "), " layout-builder-add-container-button ")]'))->toBe(0);

    $component
        ->call('showAdvancedLayout')
        ->assertForbidden();
});

it('widgets content only editors from direct layout widget meta mutation', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditContent:Layout'));

    $widget = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('editLayoutWidget', 'main', 0, ['html_class' => 'content-only-change'])
        ->assertForbidden();
});

it('lets content editors submit widget asset edits without layout access from the package namespace', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');
    Permission::findOrCreate('View:Page');

    config(['permission.teams' => true]);
    resolve(PermissionRegistrar::class)->teams = true;
    $site = Site::factory()->withTranslations()->create();
    $roleName = 'layout-content-editor';
    $role = Role::findOrCreate($roleName, 'web');
    $role->givePermissionTo(['EditContent:Layout', 'View:Page']);

    $editor = User::factory()->create();
    $editor->assignRoleForSite($site, $roleName);

    resolve(PermissionRegistrar::class)->setPermissionsTeamId($site);
    test()->actingAs($editor);

    expect($editor->isGlobalAdmin())->toBeFalse()
        ->and($editor->getAssignedSiteIds()->all())->toBe([$site->getKey()]);

    $widget = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->site($site)->withTranslations()->create(['name' => 'Featured page']);
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editWidgetAsset', data: [
            'meta' => ['variant' => 'featured'],
        ], arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'index' => 0,
            'type' => 'page',
        ])
        ->assertHasNoActionErrors();

    expect($widgetAsset->fresh()->meta['variant'] ?? null)->toBe('default');
});

it('keeps saving non publishable builder asset records through the existing path', function (): void {
    SchemaFacade::create('layout_builder_non_publishable_assets', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->timestamps();
    });

    CapellCore::registerAsset(new AssetData(
        name: LayoutBuilderNonPublishableAssetEnum::Nonpublishable->name,
        model: LayoutBuilderNonPublishableAsset::class,
        label: 'Non publishable asset',
    ));

    CapellAdmin::registerAsset(
        LayoutBuilderNonPublishableAssetEnum::Nonpublishable,
        new AdminAssetData(formClass: LayoutBuilderNonPublishableAssetForm::class),
    );
    Relation::morphMap(['nonpublishable' => LayoutBuilderNonPublishableAsset::class]);

    $widget = Widget::factory()->create(['key' => 'asset-list', 'name' => 'Asset list']);
    $asset = LayoutBuilderNonPublishableAsset::query()->create(['title' => 'Original title']);
    WidgetAsset::factory()
        ->widget($widget)
        ->occurrence(1)
        ->create([
            'asset_type' => 'nonpublishable',
            'asset_id' => $asset->getKey(),
            'order' => 1,
        ]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editWidgetAsset', data: [
            'asset' => ['title' => 'Updated title'],
        ], arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'index' => 0,
            'type' => 'nonpublishable',
        ])
        ->assertHasNoActionErrors();

    $freshAsset = capell_test_instance($asset->fresh(), LayoutBuilderNonPublishableAsset::class);

    expect($freshAsset->title)->toBe('Updated title');
});

it('renders widget rows in the structure tree and wires preview widget actions from the package namespace', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');

    $widget = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    $tree = BuildLayoutBuilderTreeAction::run(
        containers: $layout->containers,
        containerWidgets: ['main' => [$widget]],
        assets: ['main' => [[]]],
        page: null,
        selectedContainerKey: 'main',
        selectedWidgetIndex: 0,
    );

    expect($tree->containers[0]->nodeId)
        ->toBe(hash('xxh128', 'container:main'))
        ->and($tree->containers[0]->widgets[0]->nodeId)
        ->toBe(hash('xxh128', 'widget:main:0'));

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::form.search_layout_tree'))
        ->assertSee(__('capell-layout-builder::button.clear_layout_tree_search'))
        ->assertSee(__('capell-layout-builder::message.layout_tree_search_empty'))
        ->assertSee(__('capell-layout-builder::message.layout_tree_search_result'))
        ->assertSee('Featured')
        ->assertElementExists('[data-layout-builder-tree-search]')
        ->assertElementExists('[data-layout-builder-tree-container]')
        ->assertElementExists('[data-layout-builder-tree-item="main"]')
        ->assertElementExists('[data-layout-builder-tree-widget]')
        ->assertElementExists('[role="tree"]')
        ->assertElementExists('[data-layout-builder-tree-container][role="treeitem"][aria-level="1"][aria-expanded]')
        ->assertElementExists('[data-layout-builder-tree-container][role="treeitem"] > [role="group"]')
        ->assertElementExists('[role="treeitem"][aria-level="2"]')
        ->assertElementExists('[role="treeitem"][aria-level="1"] > .layout-builder-tree-row-container[tabindex="-1"]')
        ->assertElementExists('[data-layout-builder-surface="tree"]')
        ->assertElementExists('[data-layout-builder-surface="visual-editor"]')
        ->assertElementExists('[data-layout-builder-surface="toolbar"]')
        ->assertElementExists('[data-layout-builder-surface="preview-canvas"]')
        ->assertElementExists('[data-layout-builder-surface="inspector"]')
        ->assertElementExists('[data-clb-preview-node]')
        ->call('selectWidget', 'main', 0)
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedWidgetIndex', 0)
        ->assertSee(__('capell-layout-builder::button.edit_widget'));

    $document = new RenderedHtml($component->html());
    expect($document->count('//*[contains(concat(" ", normalize-space(@class), " "), " visual-inspector ")]'))->toBe(0)
        ->and($document->count('//*[@data-layout-content-action="editWidgetAsset"]'))->toBe(0);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->set('visualPreviewNodeMap', [])
        ->call('selectPreviewNode', hash('xxh128', 'widget:main:0'))
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedWidgetIndex', 0);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout]);
    $outcomes = contentFirstEditorOutcomes($component->__get('effects'), <<<'JS'
const editor = window.capellLayoutBuilderVisualEditor({actionLabels: {treeSearchResult: '1 match', treeSearchResults: ':count matches'}});
let focused = null;
let activated = null;
const item = (id, label, visible = true) => ({
    dataset: {layoutBuilderTreeNode: id, layoutBuilderTreeSearch: label},
    offsetParent: visible ? {} : null,
    focus() { focused = id; },
    click() { activated = id; },
    matches() { return false; },
    closest() { return null; },
});
const widgets = [item('first', 'Featured story'), item('hidden', 'Hidden story', false), item('last', 'Other story')];
const container = {dataset: {layoutBuilderTreeNode: 'main', layoutBuilderTreeSearch: 'Main'}, querySelectorAll() { return widgets; }};
const scope = {querySelectorAll(selector) { return selector.includes('tree-container') ? [container] : widgets; }};
editor.$el = {closest() { return scope; }};
editor.$refs = {treeSearchInput: {focus() { focused = 'search'; }}};
editor.$nextTick = callback => callback();
editor.search = '  FEATURED  ';
const search = {matches: widgets.map(widget => editor.widgetMatches(widget)), parentVisible: editor.containerMatches(container), count: editor.treeSearchResultCount(), label: editor.treeSearchResultLabel()};
const key = (key, target) => editor.handleTreeKeydown({key, preventDefault() {}}, target);
key('ArrowDown', widgets[0]);
const down = focused;
key('ArrowUp', widgets[2]);
const up = focused;
key('End', widgets[0]);
const end = focused;
key('Home', widgets[2]);
const home = focused;
key('Enter', widgets[0]);
const enter = activated;
key(' ', widgets[2]);
const space = activated;
editor.clearTreeSearch();
console.log(JSON.stringify({search, down, up, end, home, enter, space, cleared: editor.search, focus: focused, allVisible: widgets.every(widget => editor.widgetMatches(widget))}));
JS);
    expect($outcomes)->toBe([
        'search' => ['matches' => [true, false, false], 'parentVisible' => true, 'count' => 1, 'label' => '1 match'],
        'down' => 'last', 'up' => 'first', 'end' => 'last', 'home' => 'first',
        'enter' => 'first', 'space' => 'last', 'cleared' => '', 'focus' => 'search', 'allVisible' => true,
    ]);
});

it('renders widget copy in the visual preview from the package namespace', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');

    $language = Language::factory()->create();
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1]);

    Translation::query()->create([
        'translatable_type' => $widget->getMorphClass(),
        'translatable_id' => $widget->getKey(),
        'language_id' => $language->getKey(),
        'title' => 'Every section can be rebuilt in the layout builder',
        'content' => '<p>Widget-owned support copy.</p>',
    ]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee('Every section can be rebuilt in the layout builder')
        ->assertSeeHtml('Widget-owned support copy.')
        ->assertElementExists('[data-clb-preview-node-type="widget"]');
});

it('sends layout only editors straight to the advanced layout editor from the package namespace', function (): void {
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditLayout:Layout'));

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'layout_first')
        ->assertDontSee($layout->name)
        ->assertSee(__('capell-layout-builder::heading.layout_structure'));
});

it('moves responsive layout mutations through undo and redo stacks from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'widgets' => [],
            'meta' => [
                'responsive' => [
                    'tablet' => ['colspan' => 6],
                ],
            ],
        ],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', 'tablet')
        ->call('resetResponsiveContainerOverride', 'main');

    expect($component->get('containers')['main']['meta']['responsive'])->toBe([])
        ->and($component->get('layoutUndoSnapshots'))->toHaveCount(1)
        ->and($component->get('layoutRedoSnapshots'))->toBe([]);

    $component->call('undoLayoutMutation');

    expect($component->get('containers')['main']['meta']['responsive']['tablet']['colspan'])->toBe(6)
        ->and($component->get('layoutUndoSnapshots'))->toBe([])
        ->and($component->get('layoutRedoSnapshots'))->toHaveCount(1);

    $component->call('redoLayoutMutation');

    expect($component->get('containers')['main']['meta']['responsive'])->toBe([])
        ->and($component->get('layoutUndoSnapshots'))->toHaveCount(1)
        ->and($component->get('layoutRedoSnapshots'))->toBe([]);
});

it('clears redo history when a new layout mutation follows undo from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'widgets' => [],
            'meta' => [
                'responsive' => [
                    'tablet' => ['colspan' => 6],
                ],
            ],
        ],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', 'tablet')
        ->call('resetResponsiveContainerOverride', 'main')
        ->call('undoLayoutMutation')
        ->call('resetResponsiveContainerOverride', 'main');

    expect($component->get('containers')['main']['meta']['responsive'])->toBe([])
        ->and($component->get('layoutUndoSnapshots'))->toHaveCount(1)
        ->and($component->get('layoutRedoSnapshots'))->toBe([]);
});

it('widgets content only editors from layout undo and redo from the package namespace', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditContent:Layout'));

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    $snapshot = [
        'containers' => ['main' => ['widgets' => [], 'meta' => []]],
        'assets' => ['main' => []],
        'originalAssets' => ['main' => []],
        'selectedRecords' => ['main' => []],
    ];

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->set('layoutUndoSnapshots', [$snapshot])
        ->call('undoLayoutMutation')
        ->assertForbidden();

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->set('layoutRedoSnapshots', [$snapshot])
        ->call('redoLayoutMutation')
        ->assertForbidden();
});

it('rejects stale content first asset saves from the package namespace', function (): void {
    $widget = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editWidgetAsset', data: [
            'meta' => ['variant' => 'featured'],
        ], arguments: [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'index' => 0,
            'type' => 'page',
            'contentInventorySignature' => 'stale-signature',
        ])
        ->assertNotified(__('capell-layout-builder::message.content_stale'));

    expect($widgetAsset->fresh()->meta['variant'] ?? null)->toBe('default');
});

/**
 * @property string|null $title
 */

/** @return array{Site, Layout, Page, User, WidgetAsset} */
function contentFirstPageSaveFixture(): array
{
    foreach (['View:Page', 'Update:Page', 'EditLayout:Page', 'EditContent:Layout', 'EditLayout:Layout', 'Update:Layout'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    config(['permission.teams' => true]);
    resolve(PermissionRegistrar::class)->teams = true;

    $site = Site::factory()->withTranslations()->create();
    $widget = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $widget->key, 'occurrence' => 1],
        ]],
    ]]);
    $page = Page::factory()->for($site)->create(['layout_id' => $layout->getKey()]);
    $otherPage = Page::factory()->for($site)->withTranslations()->create(['layout_id' => $layout->getKey()]);
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($otherPage)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);
    $role = Role::findOrCreate('page-save-content-editor', 'web');
    $role->givePermissionTo(['View:Page', 'Update:Page', 'EditLayout:Page']);

    $editor = User::factory()->create();
    $editor->assignRoleForSite($site, $role);

    resolve(PermissionRegistrar::class)->setPermissionsTeamId($site);
    test()->actingAs($editor);

    return [$site, $layout, $page, $editor, $widgetAsset];
}

/** @param list<string> $writes */
function contentFirstTrackDatabaseWrites(array &$writes): void
{
    DB::listen(function (QueryExecuted $query) use (&$writes): void {
        if (preg_match('/^\s*(?:insert|update|delete|replace|alter|create|drop|truncate)\b/i', $query->sql) === 1) {
            $writes[] = $query->sql;
        }
    });
}

/** @return array<string, mixed> */
function contentFirstEditorOutcomes(mixed $effects, string $scenario): array
{
    $node = (new ExecutableFinder)->find('node');
    if ($node === null || ! is_dir(dirname(__DIR__, 5) . '/node_modules')) {
        throw new RuntimeException('Visual editor interactions require provisioned Node tooling.');
    }
    $scripts = data_get($effects, 'scripts', []);
    throw_unless(is_array($scripts), RuntimeException::class, 'Expected the rendered Livewire scripts.');
    $source = '';
    foreach ($scripts as $script) {
        throw_unless(is_string($script), RuntimeException::class, 'Expected a rendered script.');
        preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $script, $matches);
        $source .= implode("\n", $matches[1]);
    }
    $process = new Process([$node, '-e', 'global.window = {}; ' . $source . "\n" . $scenario], dirname(__DIR__, 5));
    $process->setTimeout(10)->run();
    expect($process->getExitCode())->toBe(0, $process->getErrorOutput());

    $outcomes = [];
    foreach (capell_json_array($process->getOutput()) as $key => $value) {
        throw_unless(is_string($key), RuntimeException::class, 'Expected named interaction outcomes.');
        $outcomes[$key] = $value;
    }

    return $outcomes;
}
