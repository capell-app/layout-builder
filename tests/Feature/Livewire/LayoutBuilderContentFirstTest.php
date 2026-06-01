<?php

declare(strict_types=1);

use Capell\Admin\Data\AdminAssetData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Contracts\FormConfigurator;
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
use Capell\LayoutBuilder\Data\AdminBlockPreviewData;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditableRegionContributor;
use Capell\LayoutBuilder\Support\FrontendAuthoring\LayoutBuilderEditorSurface;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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
        ->assertDontSee('Inspector')
        ->assertSee(__('capell-layout-builder::message.preview_status_current'))
        ->assertSee(__('capell-layout-builder::message.container_empty'))
        ->assertSeeHtml('layout-builder-visual-editor-empty')
        ->assertSeeHtml('layout-builder-visual-grid-empty')
        ->assertSeeHtml('layout-builder-shadow-preview-empty')
        ->assertSeeHtml('data-capell-layout-builder-admin-preview="true"')
        ->assertSeeHtml('capell-layout-builder:request-page-state');
});

it('renders a full width empty page preview when a layout has no containers', function (): void {
    $layout = Layout::factory()->create(['containers' => []]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::message.layout_empty'))
        ->assertSeeHtml('clb-preview-empty-page')
        ->assertSeeHtml('layout-builder-shadow-preview-empty');

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

it('selects the requested block when mounted from frontend authoring', function (): void {
    $site = Site::factory()->create();
    $firstBlock = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $secondBlock = Widget::factory()->create(['key' => 'proof', 'name' => 'Proof block']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $firstBlock->key, 'occurrence' => 1],
            ['widget_key' => $secondBlock->key, 'occurrence' => 1],
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
        'initialBlockIndex' => 1,
    ])
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedBlockIndex', 1);

    expect($component->instance()->selectedBlock()?->name)->toBe('Proof block');
});

it('dispatches frontend authoring dirty and saved lifecycle events', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('layoutUpdated')
        ->assertDispatched('capell-layout-builder-authoring-dirty')
        ->call('saveLayout')
        ->assertDispatched('capell-layout-builder-authoring-saved');
});

it('clears cached pages affected by frontend authoring block edits', function (): void {
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
    $block = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
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
            'cacheable_type' => $block->getMorphClass(),
            'cacheable_id' => $block->getKey(),
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
        'initialBlockIndex' => 0,
    ])
        ->mountAction('editBlock', arguments: [
            'containerKey' => 'main',
            'blockIndex' => 0,
        ])
        ->assertSchemaComponentExists('meta.image_source.type')
        ->assertSchemaComponentExists('meta.image_source.url')
        ->setActionData([
            ...$block->attributesToArray(),
            'name' => 'Updated hero banner',
            'meta' => [
                ...(array) $block->meta,
                'image_source' => [
                    'type' => 'url',
                    'url' => $imageUrl,
                ],
            ],
        ])
        ->callMountedAction([
            'containerKey' => 'main',
            'blockIndex' => 0,
        ])
        ->assertHasNoActionErrors()
        ->assertDispatched('capell-layout-builder-authoring-saved');

    expect($block->fresh()->meta['image_source']['url'] ?? null)->toBe($imageUrl);

    foreach ($urls as $url) {
        $cachePath = $pathResolver->pathForUrl(parse_url($url, PHP_URL_PATH) ?: '/', $siteDomain);

        expect(Storage::disk('page_cache')->exists($cachePath))->toBeFalse()
            ->and(CachedModelUrl::query()->where('url', $url)->exists())->toBeFalse();
    }
});

it('contributes frontend authoring regions for page layout blocks and block assets', function (): void {
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
    $block = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
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
        ->and(collect($regions)->pluck('field')->all())->toBe(['layout', 'block', 'assets'])
        ->and($regions[1]->selector)->toBe(LayoutBuilderEditableRegionContributor::blockSelector((int) $layout->getKey(), 'main', 0))
        ->and($regions[1]->context)->toMatchArray([
            'layoutId' => $layout->getKey(),
            'siteId' => $site->getKey(),
            'pageId' => $page->getKey(),
            'pageClass' => Page::class,
            'initialContainerKey' => 'main',
            'initialBlockIndex' => 0,
        ]);

    $payload = resolve(EditableRegionSigner::class)->decode(resolve(EditableRegionSigner::class)->encode($regions[1]));

    expect($payload->surface)->toBe('layout-builder')
        ->and($payload->target)->toBe('layout.block.main.0');
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
    $block = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->site($site)->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
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
        ->assertSee('lang="en"', false)
        ->assertSee('class="fi"', false)
        ->assertSee('capell-layout-builder-authoring')
        ->assertSee('css/capell-layout-builder/capell-layout-builder-filament.css')
        ->assertSee("[x-cloak='']", false)
        ->assertSee('capell-authoring:editor-loaded')
        ->assertSee('capell-layout-builder-authoring-saved')
        ->assertSee('wire:snapshot', false)
        ->assertSee('Hero banner');
});

it('resolves the saved admin block preview view without checking the filesystem', function (): void {
    $previewData = new AdminBlockPreviewData(
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

    expect($component->resolveAdminBlockPreviewView($previewData))
        ->toBe('capell-layout-builder::filament.layout-builder.previews.custom');
});

it('keeps legacy editor mode transitions available inside the visual editor from the package namespace', function (): void {
    $block = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
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
        ->assertDontSee(__('capell-layout-builder::button.add_container'))
        ->assertDontSee(__('capell-layout-builder::button.add_block'));

    $component
        ->call('showAdvancedLayout')
        ->assertForbidden();
});

it('blocks content only editors from direct layout block meta mutation', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');

    test()->actingAs(test()->createUserWithPermission('EditContent:Layout'));

    $block = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('editLayoutBlock', 'main', 0, ['html_class' => 'content-only-change'])
        ->assertForbidden();
});

it('lets content editors submit block asset edits without layout access from the package namespace', function (): void {
    Permission::findOrCreate('EditContent:Layout');
    Permission::findOrCreate('EditLayout:Layout');
    Permission::findOrCreate('Update:Layout');
    Permission::findOrCreate('View:Page');

    test()->actingAs(test()->createUserWithPermission(['EditContent:Layout', 'View:Page']));

    $block = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    $blockAsset = WidgetAsset::factory()
        ->block($block)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editBlockAsset', data: [
            'meta' => ['variant' => 'featured'],
        ], arguments: [
            'containerKey' => 'main',
            'blockIndex' => 0,
            'index' => 0,
            'type' => 'page',
        ])
        ->assertHasNoActionErrors();

    expect($blockAsset->fresh()->meta['variant'] ?? null)->toBe('default');
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

    $block = Widget::factory()->create(['key' => 'asset-list', 'name' => 'Asset list']);
    $asset = LayoutBuilderNonPublishableAsset::query()->create(['title' => 'Original title']);
    WidgetAsset::factory()
        ->block($block)
        ->occurrence(1)
        ->create([
            'asset_type' => 'nonpublishable',
            'asset_id' => $asset->getKey(),
            'order' => 1,
        ]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editBlockAsset', data: [
            'asset' => ['title' => 'Updated title'],
        ], arguments: [
            'containerKey' => 'main',
            'blockIndex' => 0,
            'index' => 0,
            'type' => 'nonpublishable',
        ])
        ->assertHasNoActionErrors();

    $freshAsset = $asset->fresh();

    expect($freshAsset instanceof LayoutBuilderNonPublishableAsset ? $freshAsset->title : null)->toBe('Updated title');
});

it('renders widget rows in the structure tree and wires preview widget actions from the package namespace', function (): void {
    $block = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    WidgetAsset::factory()
        ->block($block)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
        ]],
    ]]);

    $tree = BuildLayoutBuilderTreeAction::run(
        containers: $layout->containers,
        containerBlocks: ['main' => [$block]],
        assets: ['main' => [[]]],
        page: null,
        selectedContainerKey: 'main',
        selectedBlockIndex: 0,
    );

    expect($tree->containers[0]->nodeId)
        ->toBe(hash('xxh128', 'container:main'))
        ->and($tree->containers[0]->blocks[0]->nodeId)
        ->toBe(hash('xxh128', 'block:main:0'));

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::form.search_layout_tree'))
        ->assertSee('Featured')
        ->assertSeeHtml('data-layout-builder-tree-search')
        ->assertSeeHtml('data-clb-preview-node')
        ->assertSeeHtml('previewBlockActions')
        ->assertSeeHtml('runPreviewAction')
        ->assertSeeHtml('editBlock')
        ->assertSeeHtml('duplicateBlock')
        ->assertSeeHtml('removeBlock')
        ->assertDontSeeHtml('visual-inspector')
        ->call('selectBlock', 'main', 0)
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedBlockIndex', 0)
        ->assertSee(__('capell-layout-builder::button.edit_block'))
        ->assertSeeHtml('mountAction')
        ->assertDontSeeHtml('data-layout-content-action="editBlockAsset"');

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->set('visualPreviewNodeMap', [])
        ->call('selectPreviewNode', hash('xxh128', 'block:main:0'))
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedBlockIndex', 0);

    $treeView = file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/visual-tree.blade.php');
    $editorView = file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/visual-editor.blade.php');

    expect($treeView)
        ->not->toContain('$this->editBlockAssetAction')
        ->and($editorView)
        ->not->toContain('visual-inspector')
        ->toContain('mountAction(actionName, args)')
        ->toContain("node.setAttribute('role', 'button')")
        ->toContain("node.setAttribute('aria-label', label)")
        ->toContain("['Enter', ' '].includes(event.key)")
        ->toContain('aria-haspopup="menu"')
        ->toContain('aria-expanded="false"')
        ->toContain('role="menu"')
        ->toContain('role="menuitem"');
});

it('renders block copy in the visual preview from the package namespace', function (): void {
    $language = Language::factory()->create();
    $block = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    WidgetAsset::factory()
        ->block($block)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1]);

    Translation::query()->create([
        'translatable_type' => $block->getMorphClass(),
        'translatable_id' => $block->getKey(),
        'language_id' => $language->getKey(),
        'title' => 'Every section can be rebuilt in the layout builder',
        'content' => '<p>Widget-owned support copy.</p>',
    ]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
        ]],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee('Every section can be rebuilt in the layout builder')
        ->assertSeeHtml('data-clb-preview-node-type="block"');

    expect($component->get('visualPreviewHtml'))
        ->toContain('Every section can be rebuilt in the layout builder')
        ->toContain('Widget-owned support copy.');
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

it('blocks content only editors from layout undo and redo from the package namespace', function (): void {
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
    $block = Widget::factory()->create(['key' => 'featured', 'name' => 'Featured']);
    $asset = Page::factory()->withTranslations()->create(['name' => 'Featured page']);
    $blockAsset = WidgetAsset::factory()
        ->block($block)
        ->asset($asset)
        ->occurrence(1)
        ->create(['order' => 1, 'meta' => ['variant' => 'default']]);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->callAction('editBlockAsset', data: [
            'meta' => ['variant' => 'featured'],
        ], arguments: [
            'containerKey' => 'main',
            'blockIndex' => 0,
            'index' => 0,
            'type' => 'page',
            'contentInventorySignature' => 'stale-signature',
        ])
        ->assertNotified(__('capell-layout-builder::message.content_stale'));

    expect($blockAsset->fresh()->meta['variant'] ?? null)->toBe('default');
});

enum LayoutBuilderNonPublishableAssetEnum: string
{
    case Nonpublishable = 'nonpublishable';
}

/**
 * @property string|null $title
 */
class LayoutBuilderNonPublishableAsset extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    protected $table = 'layout_builder_non_publishable_assets';

    protected $guarded = [];
}

class LayoutBuilderNonPublishableAssetForm implements FormConfigurator
{
    public static function configure(Schema $configurator, mixed $context = null): Schema
    {
        return $configurator->schema([
            TextInput::make('title'),
        ]);
    }
}
