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
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

afterEach(function (): void {
    SchemaFacade::dropIfExists('layout_builder_non_publishable_assets');
});

it('renders the visual layout builder by default from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first')
        ->assertSee(__('capell-layout-builder::heading.layout_structure'))
        ->assertElementExists(fn (AssertElement $body): BaseAssert => $body->doesntContain('.visual-inspector'))
        ->assertSee(__('capell-layout-builder::message.container_empty'))
        ->assertElementExists('.layout-builder-visual-toolbar')
        ->assertElementExists('.layout-builder-command-group')
        ->assertElementExists('.layout-builder-command-save')
        ->assertElementExists('.layout-builder-preview-command-label')
        ->assertElementExists('.layout-builder-history-actions')
        ->assertElementExists('.layout-builder-panel-collapse-toggle')
        ->assertElementExists('.layout-builder-breakpoint-controls')
        ->assertElementExists('.layout-builder-visual-editor-empty')
        ->assertElementExists('.layout-builder-visual-grid-empty')
        ->assertElementExists('.layout-builder-shadow-preview-empty')
        ->assertElementExists('[data-capell-layout-builder-admin-preview="true"]')
        ->assertSeeHtml('applyPreviewBreakpoint')
        ->assertDontSeeHtml('layout-builder-preview-status-row')
        ->assertDontSeeHtml('capell-layout-builder:request-page-state');
});

it('renders a full width empty page preview when a layout has no containers', function (): void {
    $layout = Layout::factory()->create(['containers' => []]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::message.layout_empty'))
        ->assertElementExists('.clb-preview-empty-page')
        ->assertElementExists('.layout-builder-shadow-preview-empty');

    expect(file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/visual-editor.blade.php'))
        ->toContain('.clb-preview-empty-page { grid-column: 1 / -1; }');
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

    expect($component->instance()->selectedWidget()?->name)->toBe('Proof widget');
});

it('dispatches frontend authoring dirty and saved lifecycle events', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('layoutUpdated')
        ->assertDispatched('capell-layout-builder-authoring-dirty')
        ->assertNotified(__('capell-layout-builder::message.layout_unsaved'))
        ->call('saveLayout')
        ->assertDispatched('capell-layout-builder-authoring-saved');
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
            'path' => '/',
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
            'path' => '/',
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
        ->and(collect($regions)->pluck('surface')->unique()->values()->all())->toBe(['layout-builder'])
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

    expect($payload->surface)->toBe('layout-builder')
        ->and($payload->target)->toBe('layout.widget.main.0');
});

it('renders the signed frontend authoring layout builder editor surface', function (): void {
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
            'path' => '/',
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

    $this->get($signedUrl)
        ->assertOk()
        ->assertElementExists('html.fi[lang="en"]')
        ->assertSee('capell-layout-builder-authoring')
        ->assertSee('css/capell-layout-builder/capell-layout-builder-filament.css')
        ->assertElementExists('[x-cloak]')
        ->assertSee('capell-authoring:editor-loaded')
        ->assertSee('capell-layout-builder-authoring-saved')
        ->assertElementExists('[wire\:snapshot]')
        ->assertSee('Hero banner');
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
        ->call('showAdvancedLayout', 'main:0:1:page:1:0')
        ->assertSet('editorMode', 'layout_first')
        ->assertSee(__('capell-layout-builder::heading.layout_structure'))
        ->call('showContentEditor')
        ->assertSet('editorMode', 'content_first')
        ->assertSet('returnToContentItemKey', 'main:0:1:page:1:0');
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
        ->assertSee(__('capell-layout-builder::heading.layout_structure'))
        ->assertElementExists(fn (AssertElement $body): BaseAssert => $body->doesntContain('.layout-builder-add-container-button'))
        ->assertDontSeeHtml("mountAction('addWidget')");

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

    test()->actingAs(test()->createUserWithPermission(['EditContent:Layout', 'View:Page']));

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

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::form.search_layout_tree'))
        ->assertSee(__('capell-layout-builder::button.clear_layout_tree_search'))
        ->assertSee(__('capell-layout-builder::message.layout_tree_search_empty'))
        ->assertSee(__('capell-layout-builder::message.layout_tree_search_result'))
        ->assertSee('Featured')
        ->assertElementExists('[data-layout-builder-tree-search]')
        ->assertElementExists('[data-layout-builder-tree-container]')
        ->assertElementExists('[data-layout-builder-tree-widget]')
        ->assertElementExists('[data-clb-preview-node]')
        ->assertSeeHtml('previewWidgetActions')
        ->assertSeeHtml('runPreviewAction')
        ->assertSeeHtml('editWidget')
        ->assertSeeHtml('duplicateWidget')
        ->assertSeeHtml('removeWidget')
        ->assertElementExists(fn (AssertElement $body): BaseAssert => $body->doesntContain('.visual-inspector'))
        ->call('selectWidget', 'main', 0)
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedWidgetIndex', 0)
        ->assertSee(__('capell-layout-builder::button.edit_widget'))
        ->assertSeeHtml('mountAction')
        ->assertElementExists(fn (AssertElement $body): BaseAssert => $body->doesntContain('[data-layout-content-action="editWidgetAsset"]'));

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->set('visualPreviewNodeMap', [])
        ->call('selectPreviewNode', hash('xxh128', 'widget:main:0'))
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedWidgetIndex', 0);

    $treeView = file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/visual-tree.blade.php');
    $editorView = file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/visual-editor.blade.php');

    expect($treeView)
        ->not->toContain('$this->editWidgetAssetAction')
        ->toContain('x-show="containerMatches($el)"')
        ->toContain('x-show="widgetMatches($el)"')
        ->toContain('treeContainerOpen(open, $el.closest')
        ->toContain('treeSearchResultLabel()')
        ->toContain('clearTreeSearch()')
        ->toContain('data-layout-builder-tree-widget')
        ->and($editorView)
        ->not->toContain('visual-inspector')
        ->toContain('treeSearchActive()')
        ->toContain('treeSearchScope()')
        ->toContain('treeSearchResultCount()')
        ->toContain('containerHasMatchingChild(element)')
        ->toContain('containerMatches(element)')
        ->toContain('widgetMatches(element)')
        ->toContain('mountAction(actionName, args)')
        ->toContain("node.setAttribute('role', 'button')")
        ->toContain("node.setAttribute('aria-label', label)")
        ->toContain("['Enter', ' '].includes(event.key)")
        ->toContain('aria-haspopup="menu"')
        ->toContain('aria-expanded="false"')
        ->toContain('role="menu"')
        ->toContain('role="menuitem"');
});

it('renders widget copy in the visual preview from the package namespace', function (): void {
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
        ->assertSee(__('capell-layout-builder::heading.layout_record', ['name' => $layout->name]))
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
