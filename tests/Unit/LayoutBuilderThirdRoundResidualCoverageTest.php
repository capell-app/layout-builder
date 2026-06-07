<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\AddHeroWidgetToLayoutAction;
use Capell\LayoutBuilder\Actions\CreateLayoutBuilderDemoSiteAction;
use Capell\LayoutBuilder\Actions\GenerateLayoutPreviewImageAction;
use Capell\LayoutBuilder\Actions\InvalidateWidgetLayoutPreviewImagesAction;
use Capell\LayoutBuilder\Actions\Mutations\CreateLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\Mutations\PasteLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\RenderAdminLayoutPreviewAction;
use Capell\LayoutBuilder\Actions\ResolveAdminWidgetPreviewDataAction;
use Capell\LayoutBuilder\Data\AdminLayoutPreviewData;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Capell\LayoutBuilder\Enums\ContainerAlignmentEnum;
use Capell\LayoutBuilder\Enums\LayoutPreviewStatusEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\ResponsiveVisibilityEnum;
use Capell\LayoutBuilder\Enums\TypeEnum;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Exceptions\MissingWidgetAssetException;
use Capell\LayoutBuilder\Filament\Components\Forms\ActionsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetTypeSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\TagSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\WidgetSelect;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\DefaultLayoutContainerConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\LayoutBuilder\Filament\Resources\Pages\Tables\PageSelectionTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetAssetsTable;
use Capell\LayoutBuilder\Filament\Widgets\LayoutHealthWidgetAbstract;
use Capell\LayoutBuilder\Filament\Widgets\RecentActivityWidgetAbstract;
use Capell\LayoutBuilder\Listeners\LayoutLoaded;
use Capell\LayoutBuilder\Livewire\Filament\Support\LayoutBuilderActionFactory;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\Creator\ContentCreator;
use Capell\LayoutBuilder\Support\Creator\DemoCreator;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewRenderer;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewSignature;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderResidualAssetHarness;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderResidualEditWidgetPage;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderResidualFailingPreviewRenderer;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderResidualFrontendContextForLoadedLayout;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderResidualModalTableSelect;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderResidualSuccessfulPreviewRenderer;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

function invokeLayoutBuilderResidualMethod(string|object $classOrObject, string $methodName, mixed ...$arguments): mixed
{
    $method = new ReflectionMethod($classOrObject, $methodName);

    return $method->invoke(is_string($classOrObject) ? null : $classOrObject, ...$arguments);
}

/**
 * @param  array<string, mixed>  $data
 * @param  array<string, mixed>  $arguments
 */
function callLayoutBuilderResidualAction(Action $action, LayoutBuilderResidualAssetHarness $harness, array $data = [], array $arguments = []): mixed
{
    return $action
        ->livewire($harness)
        ->schemaContainer(Schema::make($harness))
        ->arguments($arguments)
        ->data($data)
        ->call();
}

it('covers residual filament component and table configuration setup branches', function (): void {
    $widgetSelect = WidgetSelect::make('widget_id')
        ->withCreateForm()
        ->withEditForm();
    $assetsRepeater = AssetsRepeater::make('assets');
    $actionsRepeater = ActionsRepeater::make('actions');
    $widgetAssetColumns = invokeLayoutBuilderResidualMethod(WidgetAssetsTable::class, 'getTableColumns');
    $widgetAssetFilters = invokeLayoutBuilderResidualMethod(WidgetAssetsTable::class, 'getTableFilters');

    capell_expect($widgetSelect)->toBeInstanceOf(WidgetSelect::class)
        ->and($assetsRepeater)->toBeInstanceOf(Repeater::class)
        ->and($actionsRepeater)->toBeInstanceOf(Repeater::class)
        ->and($widgetAssetColumns)->not->toBeEmpty()
        ->and($widgetAssetFilters)->not->toBeEmpty();
});

it('covers modal table query label action and disabled submission branches', function (): void {
    $component = new LayoutBuilderResidualModalTableSelect;
    $component->tableArguments = ['siteId' => 5];
    $component->tableQuery = Widget::query();
    $component->isDisabled = true;
    $component->selectedTableRecords = [];

    capell_expect($component->getTableArguments())->toBe(['siteId' => 5])
        ->and($component->getSelectRecordsLabel())->toBe(__('capell-layout-builder::button.select_records'))
        ->and($component->selectRecordsAction())->toBeInstanceOf(Action::class)
        ->and($component->exposeTableQuery())->toBeInstanceOf(Builder::class)
        ->and($component->exposeCanSubmitSelectedRecords())->toBeFalse();

    $component->isDisabled = false;
    $component->selectedTableRecords = [1];
    $component->tableQuery = fn (): Builder => Widget::query();

    capell_expect($component->exposeTableQuery())->toBeInstanceOf(Builder::class)
        ->and($component->exposeCanSubmitSelectedRecords())->toBeTrue();
});

it('aggregates layout health widget data for grouped unused and least-used widgets', function (): void {
    $publishedType = Blueprint::factory()->create([
        'name' => 'Marketing',
        'type' => 'widget',
        'group' => 'marketing',
    ]);
    $pendingType = Blueprint::factory()->create([
        'name' => 'Commerce',
        'type' => 'widget',
        'group' => 'commerce',
    ]);

    $publishedWidget = Widget::factory()->create([
        'name' => 'Published Hero',
        'blueprint_id' => $publishedType->getKey(),
        'visible_from' => now()->subDay(),
        'visible_until' => null,
    ]);
    $pendingWidget = Widget::factory()->create([
        'name' => 'Pending CTA',
        'blueprint_id' => $pendingType->getKey(),
        'visible_from' => now()->addDay(),
        'visible_until' => null,
    ]);
    $expiredWidget = Widget::factory()->create([
        'name' => 'Expired Banner',
        'blueprint_id' => $publishedType->getKey(),
        'visible_from' => now()->subDays(3),
        'visible_until' => now()->subDay(),
    ]);

    WidgetAsset::factory()->widget($publishedWidget)->asset(Page::factory()->create())->create();

    $layoutHealthWidget = new LayoutHealthWidgetAbstract;
    $viewData = invokeLayoutBuilderResidualMethod($layoutHealthWidget, 'getViewData');
    $data = $viewData['data'];

    capell_expect($data->totalWidgets)->toBeGreaterThanOrEqual(3)
        ->and($data->widgetsByGroup->pluck('group')->all())->toContain('marketing', 'commerce')
        ->and($data->leastUsedWidgets->pluck('name')->all())->toContain($pendingWidget->name)
        ->and($data->unusedWidgets->pluck('name')->all())->toContain($pendingWidget->name, $expiredWidget->name)
        ->and(PublishStatusEnum::published)->toBe(PublishStatusEnum::published);
});

it('generates layout preview images for matching signatures and records failures', function (): void {
    Storage::fake('public');

    $layout = Layout::factory()->create([
        'admin' => [
            LayoutPreviewMetaKey::SIGNATURE => 'matching-signature',
        ],
    ]);

    app()->instance(LayoutPreviewRenderer::class, new LayoutBuilderResidualSuccessfulPreviewRenderer);

    GenerateLayoutPreviewImageAction::run((int) $layout->getKey(), 'stale-signature');

    capell_expect($layout->refresh()->admin[LayoutPreviewMetaKey::STATUS] ?? null)->toBeNull();

    GenerateLayoutPreviewImageAction::run((int) $layout->getKey(), 'matching-signature');

    $layout->refresh();
    $path = $layout->admin[LayoutPreviewMetaKey::IMAGE];

    Storage::disk('public')->assertExists($path);

    capell_expect($layout->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Ready->value)
        ->and($layout->admin[LayoutPreviewMetaKey::ERROR])->toBeNull();

    app()->instance(LayoutPreviewRenderer::class, new LayoutBuilderResidualFailingPreviewRenderer);

    GenerateLayoutPreviewImageAction::run((int) $layout->getKey(), 'matching-signature');

    capell_expect($layout->refresh()->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Failed->value)
        ->and($layout->admin[LayoutPreviewMetaKey::IMAGE])->toBeNull()
        ->and($layout->admin[LayoutPreviewMetaKey::ERROR])->toContain('Renderer failed');
});

it('invalidates generated previews for layouts containing changed widget keys', function (): void {
    Storage::fake('public');
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'hero'],
                ],
            ],
        ],
        'admin' => [
            LayoutPreviewMetaKey::IMAGE => 'generated-layout-previews/old.png',
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Ready->value,
        ],
    ]);
    Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'other'],
                ],
            ],
        ],
    ]);
    Storage::disk('public')->put('generated-layout-previews/old.png', 'old');

    $invalidated = InvalidateWidgetLayoutPreviewImagesAction::run(['', null, 'hero', 'hero']);

    capell_expect($invalidated)->toBe(1)
        ->and($layout->refresh()->admin[LayoutPreviewMetaKey::STATUS])
        ->toBeIn([LayoutPreviewStatusEnum::Pending->value, LayoutPreviewStatusEnum::Ready->value])
        ->and($layout->admin[LayoutPreviewMetaKey::SIGNATURE] ?? null)->toBeString();
    Storage::disk('public')->assertMissing('generated-layout-previews/old.png');
});

it('resolves admin widget preview data for page content custom views and loaded images', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $page = Page::factory()->withTranslations($language)->create(['name' => 'Fallback Page']);
    $page->translation->forceFill([
        'title' => '',
        'content' => ['intro' => ['Nested <strong>content</strong>']],
    ])->save();
    $page->load('translation');

    $pageContentWidget = Widget::factory()->create([
        'component' => WidgetComponentEnum::PageContent->value,
        'admin' => [
            'admin_preview_view' => 'capell-layout-builder::filament.layout-builder.previews.custom',
            'type' => 'Page copy',
            'icon' => 'heroicon-o-document-text',
        ],
    ]);

    $pageContentPreview = ResolveAdminWidgetPreviewDataAction::run(
        $pageContentWidget,
        ['meta' => ['name' => 'Layout Label']],
        $page,
        2,
        true,
    );

    $widget = Widget::factory()->create(['admin' => ['admin_preview_view' => 'not-a-preview-view']]);
    $widget->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Widget title',
        'content' => '<p>Widget excerpt</p>',
    ]);
    $widget->load('translation');

    $widgetPreview = ResolveAdminWidgetPreviewDataAction::run($widget, [], null, 0, false);

    capell_expect($pageContentPreview->view)->toBe('capell-layout-builder::filament.layout-builder.previews.custom')
        ->and($pageContentPreview->label)->toBe('Layout Label')
        ->and($pageContentPreview->title)->toBe('Fallback Page')
        ->and($pageContentPreview->excerpt)->toContain('Nested content')
        ->and($pageContentPreview->typeLabel)->toBe('Page copy')
        ->and($pageContentPreview->icon)->toBe('heroicon-o-document-text')
        ->and($widgetPreview->view)->toBe('capell-layout-builder::filament.layout-builder.previews.default')
        ->and($widgetPreview->title)->toBe('Widget title')
        ->and($widgetPreview->excerpt)->toBe('Widget excerpt');
});

it('copies and pastes layout fragments with unique container and widget anchors', function (): void {
    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => 'hero',
                        'meta' => [
                            'widget_settings' => [
                                'anchor_id' => 'Shared Anchor',
                            ],
                        ],
                    ],
                ],
            ],
            'main-copy' => [
                'widgets' => [],
            ],
            'aside' => [
                'widgets' => [
                    [
                        'widget_key' => 'cta',
                        'meta' => [
                            'widget_settings' => [
                                'anchor_id' => 'shared-anchor',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        assets: [
            'main' => [[['asset_id' => 1, 'asset_type' => 'page']]],
            'aside' => [[]],
        ],
        originalAssets: [
            'main' => [[['asset_id' => 1, 'asset_type' => 'page']]],
            'aside' => [[]],
        ],
        selectedRecords: [
            'main' => [['page.1']],
            'aside' => [[]],
        ],
    );

    $containerFragment = CreateLayoutFragmentAction::run($state, 'main', null);
    $containerResult = PasteLayoutFragmentAction::run($state, $containerFragment, 'aside');

    capell_expect($containerResult->state->containers)->toHaveKey('main-copy-2')
        ->and($containerResult->state->containers['main-copy-2']['widgets'][0]['meta']['widget_settings']['anchor_id'])
        ->toBe('shared-anchor-2');

    $widgetFragment = CreateLayoutFragmentAction::run($state, 'main', 0);
    $widgetResult = PasteLayoutFragmentAction::run($state, $widgetFragment, 'aside', 0);

    capell_expect($widgetResult->state->containers['aside']['widgets'][0]['widget_key'])->toBe('hero')
        ->and($widgetResult->state->containers['aside']['widgets'][0]['meta']['widget_settings']['anchor_id'])
        ->toBe('shared-anchor-2')
        ->and($widgetResult->state->assets['aside'][0])->toBe([['asset_id' => 1, 'asset_type' => 'page']]);

    $missingFragment = CreateLayoutFragmentAction::run($state, 'missing', null);
    $unchangedResult = PasteLayoutFragmentAction::run($state, $missingFragment, 'missing');

    capell_expect($missingFragment->container)->toBeNull()
        ->and($unchangedResult->state->containers)->toBe($state->containers);
});

it('covers edit widget page relation metadata and relation manager table setup', function (): void {
    $type = Blueprint::factory()->create(['name' => 'Hero Type', 'type' => 'widget']);
    $widget = Widget::factory()->create([
        'name' => 'Editable Hero',
        'blueprint_id' => $type->getKey(),
    ]);
    $widget->setRelation('type', $type);

    $page = new LayoutBuilderResidualEditWidgetPage($widget);
    $title = $page->getTitle();
    $titleText = $title instanceof Htmlable ? $title->toHtml() : $title;

    capell_expect($titleText)->toContain('Editable Hero')
        ->and($page->exposeSubheading())->toContain('Hero Type')
        ->and($page->exposeBaseHeaderActions())->not->toBeEmpty()
        ->and($page->exposeRecordSwitcherColumns())->toBe(['name', 'admin'])
        ->and($page->exposeRecordSwitcherSearchColumns())->toBe(['name', '`key`', 'admin->notes'])
        ->and($page->exposeSelectChangerItemLabel($widget))->toBe('Editable Hero');
});

it('orchestrates demo site layout population with creator collaborators', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();
    $layout = Layout::factory()->create([
        'key' => LayoutEnum::Home->value,
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);
    $page = Page::factory()
        ->home()
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey()]);
    $site->setRelation('languages', new Collection([$language]));

    $demoCreator = Mockery::mock(DemoCreator::class);
    $demoCreator->shouldReceive('createPageCardsWidget')->twice()->andReturn(
        layoutBuilderResidualWidget('page-cards'),
        layoutBuilderResidualWidget('page-cards-two'),
    );
    $demoCreator->shouldReceive('createGalleryWidget')->once()->andReturn(layoutBuilderResidualWidget('gallery'));
    $demoCreator->shouldReceive('createMediaCarouselWidget')->once()->andReturn(layoutBuilderResidualWidget('carousel'));
    $demoCreator->shouldReceive('createFaqWidget')->once()->andReturn(layoutBuilderResidualWidget('faq'));
    $demoCreator->shouldReceive('createStaticNavigationWidget')->once()->andReturn(layoutBuilderResidualWidget('static-nav'));
    $demoCreator->shouldReceive('createModernFeatureListWidget')->once()->andReturn(layoutBuilderResidualWidget('modern-feature-list'));
    $demoCreator->shouldReceive('createTeamPortfolioWidget')->once()->andReturn(layoutBuilderResidualWidget('team-portfolio'));
    $demoCreator->shouldReceive('createModernTeamMembersWidget')->once()->andReturn(layoutBuilderResidualWidget('modern-team'));
    $demoCreator->shouldReceive('createBannerImageWidget')->once()->andReturn(layoutBuilderResidualWidget('banner-image'));
    $demoCreator->shouldReceive('createContentWidget')->once()->andReturn(layoutBuilderResidualWidget('content'));
    $demoCreator->shouldReceive('createStatisticsWidget')->once()->andReturn(layoutBuilderResidualWidget('statistics'));
    $demoCreator->shouldReceive('createModernPricingTableWidget')->once()->andReturn(layoutBuilderResidualWidget('pricing'));
    $demoCreator->shouldReceive('createBusinessFeaturesWidget')->once()->andReturn(layoutBuilderResidualWidget('business-features'));
    $demoCreator->shouldReceive('createBannersWidget')->once()->andReturn(layoutBuilderResidualWidget('banners'));
    $demoCreator->shouldReceive('createClientLogosWidget')->once()->andReturn(layoutBuilderResidualWidget('client-logos'));
    $demoCreator->shouldReceive('createModernTestimonialsWidget')->once()->andReturn(layoutBuilderResidualWidget('testimonials'));
    $demoCreator->shouldReceive('createModernFaqWidget')->once()->andReturn(layoutBuilderResidualWidget('modern-faq'));
    $demoCreator->shouldReceive('createModernStatsSectionWidget')->once()->andReturn(layoutBuilderResidualWidget('modern-stats'));
    $demoCreator->shouldReceive('createModernAlternatingContentWidget')->once()->andReturn(layoutBuilderResidualWidget('alternating'));
    $demoCreator->shouldReceive('createModernProcessStepsWidget')->once()->andReturn(layoutBuilderResidualWidget('process'));
    $demoCreator->shouldReceive('createModernImageGalleryWidget')->once()->andReturn(layoutBuilderResidualWidget('modern-gallery'));
    $demoCreator->shouldReceive('createApHeroBannerWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-hero'));
    $demoCreator->shouldReceive('createApCardGridWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-card'));
    $demoCreator->shouldReceive('createApFeatureListWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-feature'));
    $demoCreator->shouldReceive('createApCtaSectionWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-cta'));
    $demoCreator->shouldReceive('createApImageGalleryWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-gallery'));
    $demoCreator->shouldReceive('createSplitContentWidget')->once()->andReturn(layoutBuilderResidualWidget('split-content'));
    $demoCreator->shouldReceive('addSplitTwoBackgroundMedia')->once()->with(Mockery::type(Layout::class));

    $widgetCreator = Mockery::mock(WidgetCreator::class);
    $widgetCreator->shouldReceive('apHeroBannerWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-hero-catalog'));
    $widgetCreator->shouldReceive('apCardGridWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-card-catalog'));
    $widgetCreator->shouldReceive('apFeatureListWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-feature-catalog'));
    $widgetCreator->shouldReceive('apCtaSectionWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-cta-catalog'));
    $widgetCreator->shouldReceive('apImageGalleryWidget')->once()->andReturn(layoutBuilderResidualWidget('ap-gallery-catalog'));
    app()->instance(WidgetCreator::class, $widgetCreator);

    $action = new CreateLayoutBuilderDemoSiteAction;
    $demoCreatorProperty = new ReflectionProperty($action, 'demoCreator');
    $demoCreatorProperty->setValue($action, $demoCreator);

    invokeLayoutBuilderResidualMethod($action, 'setupHomepage', $page, new Collection([$language]));

    $containers = $layout->refresh()->containers;

    capell_expect($page->refresh()->layout_id)->toBe($layout->getKey())
        ->and($containers['main']['widgets'])->toHaveCount(4)
        ->and($containers['faq-main']['widgets'][0]['widget_key'])->toBe('faq')
        ->and($containers['faq-col']['widgets'][0]['widget_key'])->toBe('static-nav')
        ->and($containers['secondary']['widgets'])->toHaveCount(16)
        ->and($containers['ap-widgets']['widgets'])->toHaveCount(5)
        ->and($containers['split-two']['widgets'][0]['widget_key'])->toBe('split-content');
});

it('creates demo site content recursively and stops when the site is already large', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();
    $site->setRelation('languages', new Collection([$language]));

    $createdContent = [];

    $contentCreator = Mockery::mock(ContentCreator::class);
    $contentCreator->shouldReceive('createContent')
        ->times(2)
        ->andReturnUsing(function (array $data, Site $targetSite) use (&$createdContent): Page {
            $page = Page::factory()->site($targetSite)->create([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
            ]);
            $createdContent[] = $data;

            return $page;
        });

    $action = new CreateLayoutBuilderDemoSiteAction;

    invokeLayoutBuilderResidualMethod(
        $action,
        'createSiteContents',
        $contentCreator,
        [
            'name' => ['en' => 'Parent'],
            'children' => [
                ['name' => ['en' => 'Child']],
            ],
        ],
        $site,
        new Collection([$language]),
    );

    Page::factory()->count(29)->site($site)->create();

    invokeLayoutBuilderResidualMethod(
        $action,
        'createSiteContents',
        $contentCreator,
        ['name' => ['en' => 'Skipped']],
        $site,
        new Collection([$language]),
    );

    capell_expect($createdContent)->toHaveCount(2)
        ->and($createdContent[0]['translations']['en']['title'])->toBe('Parent')
        ->and($createdContent[1]['parent_id'])->not->toBeNull();
});

it('creates demo site content from the primary language when english names are unavailable', function (): void {
    $language = Language::factory()->french(isDefault: true)->create();
    $site = Site::factory()
        ->default()
        ->language($language)
        ->withTranslations($language)
        ->create();
    $site->setRelation('languages', new Collection([$language]));

    $createdContent = [];

    $contentCreator = Mockery::mock(ContentCreator::class);
    $contentCreator->shouldReceive('createContent')
        ->once()
        ->andReturnUsing(function (array $data, Site $targetSite) use (&$createdContent): Page {
            $page = Page::factory()->site($targetSite)->create([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
            ]);
            $createdContent[] = $data;

            return $page;
        });

    $action = new CreateLayoutBuilderDemoSiteAction;

    invokeLayoutBuilderResidualMethod(
        $action,
        'createSiteContents',
        $contentCreator,
        ['name' => ['fr' => 'Racine']],
        $site,
        new Collection([$language]),
    );

    capell_expect($createdContent)->toHaveCount(1)
        ->and($createdContent[0]['name'])->toBe('Racine')
        ->and($createdContent[0]['translations']['fr']['title'])->toBe('Racine');
});

it('adds updates preloads and deletes page-scoped widget assets through the editor concern', function (): void {
    $site = Site::factory()->default()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();
    $firstAssetPage = Page::factory()->site($site)->withTranslations()->create();
    $secondAssetPage = Page::factory()->site($site)->withTranslations()->create();
    $widget = Widget::factory()->create([
        'key' => 'asset-widget',
        'admin' => [
            'asset_types' => ['page'],
        ],
    ]);
    $widget->setRelation('assets', new Collection);

    $harness = new LayoutBuilderResidualAssetHarness;
    $harness->layout = Layout::factory()->create();
    $harness->page = $page;
    $harness->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => $widget->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = ['main' => [[]]];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->originalAssets = ['main' => [[]]];
    $harness->setContainerWidgets(['main' => [$widget]]);

    $harness->exposeAddAssets('main', 0, true, 'missing-type', [$firstAssetPage->getKey()]);

    $createdAsset = $harness->exposeCreateWidgetAsset(
        $widget,
        'main',
        1,
        true,
        1,
        [
            'asset_type' => $firstAssetPage->getMorphClass(),
            'asset_id' => $firstAssetPage->getKey(),
            'meta' => ['caption' => 'First'],
        ],
    );

    capell_expect($harness->assets['main'][0])->toBe([])
        ->and($createdAsset->pageable_id)->toBe($page->getKey())
        ->and($createdAsset->container)->toBe('main')
        ->and($harness->exposeActiveWidgetAssetIds($widget))->toBe([]);

    $harness->assets['main'][0] = [
        [
            'id' => $createdAsset->getKey(),
            'asset_type' => $createdAsset->asset_type,
            'asset_id' => $createdAsset->asset_id,
            'meta' => ['caption' => 'Updated'],
            'order' => 2,
            'occurrence' => 1,
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'container' => 'main',
        ],
        [
            'asset_type' => $secondAssetPage->getMorphClass(),
            'asset_id' => $secondAssetPage->getKey(),
            'meta' => ['caption' => 'Second'],
            'order' => 3,
            'occurrence' => 1,
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'container' => 'main',
        ],
    ];

    $harness->exposeUpdateAssets('main', 0);
    $widget->load('assets');
    $harness->setContainerWidgets(['main' => [$widget]]);

    capell_expect(WidgetAsset::query()->where('widget_id', $widget->getKey())->count())->toBe(2)
        ->and($createdAsset->refresh()->order)->toBe(2)
        ->and($createdAsset->meta)->toBe(['caption' => 'Updated'])
        ->and($harness->exposeLoadWidgetAssets($widget, 'main', 1))->toHaveCount(2)
        ->and($harness->exposeLoadWidgetAssetsFor($widget, 'main', 0))->toBeInstanceOf(Collection::class)
        ->and($harness->exposePreloadAllWidgetAssets())->toBeInstanceOf(Collection::class);

    $harness->originalAssets = [
        'main' => [
            [
                [
                    'id' => $createdAsset->getKey(),
                    'asset_type' => $createdAsset->asset_type,
                    'asset_id' => $createdAsset->asset_id,
                    'occurrence' => 1,
                    'workspace_id' => 0,
                    'original_container_key' => 'main',
                    'original_widget_id' => $widget->getKey(),
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
            ],
        ],
    ];
    $harness->assets['main'][0] = [];

    $harness->exposeDeleteRemovedWidgetAssets();

    capell_expect(WidgetAsset::query()->whereKey($createdAsset->getKey())->exists())->toBeFalse();
});

it('adds validated page assets through the editor selection workflow', function (): void {
    Gate::before(static fn (mixed $user = null): bool => true);

    $site = Site::factory()->default()->create();
    $otherSite = Site::factory()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();
    $allowedAssetPage = Page::factory()->site($site)->withTranslations()->create(['name' => 'Allowed asset']);
    $otherSiteAssetPage = Page::factory()->site($otherSite)->withTranslations()->create();
    $widget = Widget::factory()->create([
        'key' => 'validated-asset-widget',
        'admin' => [
            'asset_types' => ['page'],
        ],
    ]);
    $widget->setRelation('assets', new Collection);

    $harness = new LayoutBuilderResidualAssetHarness;
    $harness->site = $site;
    $harness->layout = Layout::factory()->create(['site_id' => $site->getKey()]);
    $harness->page = $page;
    $harness->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => $widget->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = ['main' => [[]]];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->originalAssets = ['main' => [[]]];
    $harness->setContainerWidgets(['main' => [$widget]]);

    $harness->exposeAddAssets(
        containerKey: 'main',
        widgetIndex: 0,
        hasPageAssets: true,
        type: 'page',
        assets: [$page->getKey(), $allowedAssetPage->getKey(), $otherSiteAssetPage->getKey(), ''],
        assetsMeta: [
            $allowedAssetPage->getKey() => ['caption' => 'Selected from modal'],
        ],
    );

    capell_expect($harness->assets['main'][0])->toHaveCount(1)
        ->and($harness->assets['main'][0][0])->toMatchArray([
            'asset_id' => $allowedAssetPage->getKey(),
            'asset_type' => 'page',
            'meta' => ['caption' => 'Selected from modal'],
            'widget_id' => $widget->getKey(),
            'order' => 1,
            'occurrence' => 1,
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'container' => 'main',
        ])
        ->and($widget->assets)->toHaveCount(1)
        ->and($widget->assets->first())->toBeInstanceOf(WidgetAsset::class)
        ->and($widget->assets->first()->exists)->toBeFalse();

    $harness->exposeUpdateAssets('main', 0);

    capell_expect(WidgetAsset::query()->where('widget_id', $widget->getKey())->count())->toBe(1)
        ->and((int) WidgetAsset::query()->first()?->asset_id)->toBe($allowedAssetPage->getKey());
});

it('synchronizes global widget assets by updating kept records and deleting removed records', function (): void {
    $widget = Widget::factory()->create([
        'key' => 'global-assets-widget',
        'admin' => ['asset_types' => ['page']],
    ]);
    $keptPage = Page::factory()->withTranslations()->create(['name' => 'Kept global asset']);
    $removedPage = Page::factory()->withTranslations()->create(['name' => 'Removed global asset']);
    $createdPage = Page::factory()->withTranslations()->create(['name' => 'Created global asset']);

    $keptAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($keptPage)
        ->create([
            'workspace_id' => 0,
            'container' => null,
            'pageable_id' => null,
            'pageable_type' => null,
            'occurrence' => 1,
            'order' => 5,
            'meta' => ['caption' => 'Before sync'],
        ]);
    $removedAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($removedPage)
        ->create([
            'workspace_id' => 0,
            'container' => null,
            'pageable_id' => null,
            'pageable_type' => null,
            'occurrence' => 1,
            'order' => 6,
            'meta' => ['caption' => 'Remove me'],
        ]);
    $widget->load('assets');

    $harness = new LayoutBuilderResidualAssetHarness;
    $harness->layout = Layout::factory()->create();
    $harness->page = null;
    $harness->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => $widget->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = [
        'main' => [
            [
                [
                    'id' => $keptAsset->getKey(),
                    'asset_id' => $keptPage->getKey(),
                    'asset_type' => $keptAsset->asset_type,
                    'meta' => ['caption' => 'After sync'],
                    'order' => 2,
                    'occurrence' => 1,
                ],
            ],
        ],
    ];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->originalAssets = [
        'main' => [
            [
                ['id' => $keptAsset->getKey()],
                ['id' => $removedAsset->getKey()],
            ],
        ],
    ];
    $harness->setContainerWidgets(['main' => [$widget]]);

    $harness->exposeUpdateAssets('main', 0);

    $createdAsset = $harness->exposeCreateWidgetAsset(
        widget: $widget,
        containerKey: 'main',
        occurrence: 1,
        hasPageAssets: false,
        order: 3,
        asset: [
            'asset_type' => $keptAsset->asset_type,
            'asset_id' => $createdPage->getKey(),
            'meta' => ['caption' => 'Created globally'],
        ],
    );

    $keptAsset->refresh();

    capell_expect($keptAsset->meta)->toBe(['caption' => 'After sync'])
        ->and($keptAsset->order)->toBe(2)
        ->and($keptAsset->container)->toBeNull()
        ->and($keptAsset->pageable_id)->toBeNull()
        ->and(WidgetAsset::query()->whereKey($removedAsset->getKey())->exists())->toBeFalse()
        ->and($createdAsset->exists)->toBeTrue()
        ->and($createdAsset->container)->toBeNull()
        ->and($createdAsset->pageable_type)->toBeNull()
        ->and($createdAsset->pageable_id)->toBeNull();
});

it('builds the layout editor action surface from one factory', function (): void {
    $harness = new LayoutBuilderResidualAssetHarness;
    $factory = new LayoutBuilderActionFactory($harness);

    $actions = [
        $factory->saveLayoutAction(),
        $factory->duplicateLayoutAction(),
        $factory->cloneLayoutForPageAction(),
        $factory->undoLayoutMutationAction(),
        $factory->redoLayoutMutationAction(),
        $factory->addContainerAction(),
        $factory->editContainerAction(),
        $factory->removeContainerAction(),
        $factory->moveContainerUpAction(),
        $factory->moveContainerDownAction(),
        $factory->duplicateContainerAction(),
        $factory->editLayoutWidgetAction(),
        $factory->addWidgetAction(),
        $factory->editWidgetAction(),
        $factory->duplicateWidgetAction(),
        $factory->moveWidgetUpAction(),
        $factory->moveWidgetDownAction(),
        $factory->moveWidgetToContainerAction(),
        $factory->removeWidgetAction(),
        $factory->selectAssetAction(),
        $factory->addAssetAction(),
        $factory->editWidgetAssetAction(),
        $factory->moveAssetUpAction(),
        $factory->moveAssetDownAction(),
        $factory->removeAssetsAction(),
        $factory->changeLayoutAction(),
        $factory->togglePageAssetsAction(),
    ];

    capell_expect($actions)
        ->each->toBeInstanceOf(Action::class)
        ->and(array_map(static fn (Action $action): string => $action->getName(), $actions))
        ->toBe([
            'saveLayout',
            'duplicateLayout',
            'cloneLayoutForPage',
            'undoLayoutMutation',
            'redoLayoutMutation',
            'addContainer',
            'editContainer',
            'removeContainer',
            'moveContainerUp',
            'moveContainerDown',
            'duplicateContainer',
            'editLayoutWidget',
            'addWidget',
            'editWidget',
            'duplicateWidget',
            'moveWidgetUp',
            'moveWidgetDown',
            'moveWidgetToContainer',
            'removeWidget',
            'selectAsset',
            'addAsset',
            'editWidgetAsset',
            'moveAssetUp',
            'moveAssetDown',
            'removeAssets',
            'changeLayout',
            'togglePageAssets',
        ]);
});

it('covers layout builder public editor helpers and action factory private branches', function (): void {
    $site = Site::factory()->default()->create();
    $layout = Layout::factory()->create(['site_id' => $site->getKey()]);
    $page = Page::factory()->site($site)->withTranslations()->create(['layout_id' => $layout->getKey()]);
    $otherPage = Page::factory()->site($site)->withTranslations()->create(['layout_id' => $layout->getKey()]);
    $widget = Widget::factory()->create(['key' => 'factory-widget']);
    $assetPage = Page::factory()->site($site)->withTranslations()->create();
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($assetPage)
        ->create([
            'container' => 'main',
            'occurrence' => 1,
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
        ]);
    $widget->load('assets');

    $harness = new LayoutBuilderResidualAssetHarness;
    $harness->site = $site;
    $harness->layout = $layout;
    $harness->page = $page;
    $harness->containers = [
        'main' => [
            'widgets' => [
                ['widget_key' => $widget->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = [
        'main' => [
            [
                [
                    'id' => $widgetAsset->getKey(),
                    'asset_type' => $widgetAsset->asset_type,
                    'asset_id' => $widgetAsset->asset_id,
                    'order' => 1,
                    'occurrence' => 1,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                    'container' => 'main',
                ],
            ],
        ],
    ];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->originalAssets = ['main' => [[]]];
    $harness->knownContainerKeys = ['main'];
    $harness->setContainerWidgets(['main' => [$widget]]);

    $factory = new LayoutBuilderActionFactory($harness);
    $assetType = CapellCore::getAssets()->keys()->first();

    $record = invokeLayoutBuilderResidualMethod($factory, 'makeWidgetAssetRecordForCreate', [
        'containerKey' => 'main',
        'widgetIndex' => 0,
        'type' => $assetType,
    ]);
    $editableAsset = invokeLayoutBuilderResidualMethod($factory, 'resolveEditableWidgetAsset', [
        'containerKey' => 'main',
        'widgetIndex' => 0,
        'index' => 0,
        'type' => $widgetAsset->asset_type,
    ]);
    $pageHeading = invokeLayoutBuilderResidualMethod($factory, 'getEditWidgetAssetModalHeading', $harness, ['type' => $widgetAsset->asset_type]);
    $pageDescription = invokeLayoutBuilderResidualMethod($factory, 'getEditWidgetAssetModalDescription', $harness, [
        'containerKey' => 'main',
        'widgetIndex' => 0,
        'index' => 0,
    ]);
    $changeLayoutSchema = invokeLayoutBuilderResidualMethod($factory, 'getChangeLayoutSchema');

    capell_expect($harness->layoutPagesCount())->toBeGreaterThanOrEqual(2)
        ->and($harness->layoutIsUsedByPages())->toBeTrue()
        ->and($harness->otherPagesUsingLayoutCount())->toBe(1)
        ->and($harness->layoutIsSharedWithOtherPages())->toBeTrue()
        ->and($harness->getPagesUsingLayoutUrl())->toContain('layout_id')
        ->and($harness->getCurrentResource())->toBeString()
        ->and($harness->getPageResource())->toBeString()
        ->and($harness->placeholder(['label' => 'Loading'])->name())->toBe('capell-admin::components.placeholder')
        ->and($record)->toBeInstanceOf(WidgetAsset::class)
        ->and($record->widget_id)->toBe($widget->getKey())
        ->and($editableAsset->getKey())->toBe($widgetAsset->getKey())
        ->and($pageHeading)->toContain(str($widgetAsset->asset_type)->title()->toString())
        ->and($pageDescription)->toContain($page->name)
        ->and($changeLayoutSchema)->not->toBeEmpty()
        ->and($otherPage->exists)->toBeTrue();

    $harness->page = null;
    invokeLayoutBuilderResidualMethod($factory, 'changePageLayout', $layout->getKey());

    capell_expect($harness->otherPagesUsingLayoutCount())->toBeGreaterThanOrEqual(2)
        ->and(invokeLayoutBuilderResidualMethod($factory, 'getEditWidgetAssetModalDescription', $harness, [
            'containerKey' => 'main',
            'widgetIndex' => 0,
            'index' => 0,
        ]))->toBeNull();
});

it('drives layout editor action closures through a page editing workflow', function (): void {
    Gate::before(static fn (mixed $user = null): bool => true);

    app()->instance(RenderAdminLayoutPreviewAction::class, new class
    {
        /**
         * @param  array<array-key, mixed>  $containers
         * @param  array<array-key, mixed>  $containerWidgets
         * @param  array<array-key, mixed>  $assets
         * @param  array<array-key, mixed>  $pageFormState
         */
        public function handle(array $containers, array $containerWidgets, array $assets, mixed $page, array $pageFormState = []): AdminLayoutPreviewData
        {
            expect($containers)->toHaveKey('main')
                ->and($pageFormState['title'] ?? null)->toBe('Preview title');

            return new AdminLayoutPreviewData(
                html: '<section data-preview="layout">Preview title</section>',
                signature: 'workflow-preview-signature',
                nodeMap: [
                    hash('xxh128', 'container:main') => ['type' => 'container', 'containerKey' => 'main'],
                    hash('xxh128', 'widget:main:0') => ['type' => 'widget', 'containerKey' => 'main', 'widgetIndex' => 0],
                ],
            );
        }
    });

    $site = Site::factory()->default()->create();
    $layout = Layout::factory()->create([
        'site_id' => $site->getKey(),
        'containers' => [
            'main' => [
                'meta' => [
                    'area' => 'main',
                    'responsive' => [
                        'tablet' => ['colspan' => 8],
                    ],
                ],
                'widgets' => [
                    ['widget_key' => 'workflow-hero', 'occurrence' => 1],
                ],
            ],
        ],
    ]);
    $page = Page::factory()->site($site)->withTranslations()->create(['layout_id' => $layout->getKey()]);
    $firstAssetPage = Page::factory()->site($site)->withTranslations()->create(['name' => 'Workflow asset one']);
    $secondAssetPage = Page::factory()->site($site)->withTranslations()->create(['name' => 'Workflow asset two']);
    $heroWidget = Widget::factory()->create([
        'key' => 'workflow-hero',
        'admin' => ['asset_types' => ['page']],
    ]);
    $cardsWidget = Widget::factory()->create([
        'key' => 'workflow-cards',
        'admin' => ['asset_types' => ['page']],
    ]);
    $persistedAsset = WidgetAsset::factory()
        ->widget($heroWidget)
        ->asset($firstAssetPage)
        ->create([
            'asset_type' => 'page',
            'asset_id' => $firstAssetPage->getKey(),
            'container' => 'main',
            'occurrence' => 1,
            'order' => 1,
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
        ]);
    $heroWidget->load('assets');
    $cardsWidget->setRelation('assets', new Collection);

    $harness = new LayoutBuilderResidualAssetHarness;
    $harness->site = $site;
    $harness->layout = $layout;
    $harness->page = $page;
    $harness->containers = $layout->containers;
    $harness->assets = [
        'main' => [
            [
                [
                    'id' => $persistedAsset->getKey(),
                    'widget_id' => $heroWidget->getKey(),
                    'workspace_id' => 0,
                    'asset_id' => $firstAssetPage->getKey(),
                    'asset_type' => 'page',
                    'meta' => ['caption' => 'Original'],
                    'order' => 1,
                    'occurrence' => 1,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                    'container' => 'main',
                ],
            ],
        ],
    ];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->originalAssets = $harness->assets;
    $harness->knownContainerKeys = ['main'];
    $harness->setContainerWidgets(['main' => [$heroWidget]]);
    invokeLayoutBuilderResidualMethod($harness, 'captureSavedBaselineState');

    $factory = new LayoutBuilderActionFactory($harness);

    callLayoutBuilderResidualAction($factory->saveLayoutAction(), $harness);
    callLayoutBuilderResidualAction($factory->addContainerAction(), $harness, [
        'key' => 'secondary',
        'meta' => ['area' => 'main'],
    ], ['position' => 1]);
    callLayoutBuilderResidualAction($factory->editContainerAction(), $harness, [
        'key' => 'sidebar',
        'meta' => ['area' => 'aside'],
    ], ['containerKey' => 'secondary']);
    callLayoutBuilderResidualAction($factory->addWidgetAction(), $harness, [
        'widgets' => [$cardsWidget->getKey()],
    ], ['containerKey' => 'sidebar', 'position' => 0]);
    callLayoutBuilderResidualAction($factory->editLayoutWidgetAction(), $harness, [
        'widget_settings' => ['anchor_id' => 'Workflow Hero'],
    ], ['containerKey' => 'main', 'widgetIndex' => 0]);

    $harness->addAssetsToWidget(
        ['containerKey' => 'main', 'widgetIndex' => 0, 'hasPageAssets' => true],
        'page',
        [$secondAssetPage->getKey()],
    );
    callLayoutBuilderResidualAction($factory->moveAssetDownAction(), $harness, [], [
        'containerKey' => 'main',
        'widgetIndex' => 0,
        'assetIndex' => 0,
    ]);
    callLayoutBuilderResidualAction($factory->moveAssetUpAction(), $harness, [], [
        'containerKey' => 'main',
        'widgetIndex' => 0,
        'assetIndex' => 1,
    ]);
    callLayoutBuilderResidualAction($factory->togglePageAssetsAction(), $harness, [], [
        'containerKey' => 'main',
        'widgetIndex' => 0,
    ]);
    $harness->selectedRecords['main'][0] = ['page.' . $secondAssetPage->getKey()];
    callLayoutBuilderResidualAction($factory->removeAssetsAction(), $harness, [], [
        'containerKey' => 'main',
        'widgetIndex' => 0,
    ]);

    callLayoutBuilderResidualAction($factory->duplicateWidgetAction(), $harness, [], [
        'containerKey' => 'main',
        'widgetIndex' => 0,
    ]);
    callLayoutBuilderResidualAction($factory->moveWidgetDownAction(), $harness, [], [
        'containerKey' => 'main',
        'widgetIndex' => 0,
    ]);
    callLayoutBuilderResidualAction($factory->moveWidgetUpAction(), $harness, [], [
        'containerKey' => 'main',
        'widgetIndex' => 1,
    ]);
    callLayoutBuilderResidualAction($factory->moveWidgetToContainerAction(), $harness, [
        'target_container' => 'sidebar',
    ], ['containerKey' => 'main', 'widgetIndex' => 1]);

    $harness->selectContainer('missing');
    $harness->selectContainer('main');
    $harness->selectWidget('main', 0);
    $harness->selectPreviewNode(hash('xxh128', 'container:main'));
    $harness->selectPreviewNode(hash('xxh128', 'widget:main:0'));
    $harness->refreshVisualPreview(['title' => 'Preview title']);
    $harness->showAdvancedLayout('content:hero');
    $harness->showContentEditor();
    $harness->setActiveBreakpoint('tablet');
    $harness->resetResponsiveContainerOverride('main');
    $harness->copyLayoutWidget('main', 0);
    $harness->pasteLayoutFragment('sidebar', 0);
    $harness->copyLayoutContainer('main');
    $harness->pasteLayoutFragment('sidebar');
    $harness->saveLayoutPreset('main', 'Workflow Preset');
    $harness->insertLayoutPreset('Workflow Preset', 'sidebar');

    callLayoutBuilderResidualAction($factory->removeWidgetAction(), $harness, [], [
        'containerKey' => 'sidebar',
        'widgetIndex' => 0,
    ]);
    callLayoutBuilderResidualAction($factory->moveContainerUpAction(), $harness, [], ['containerKey' => 'sidebar']);
    callLayoutBuilderResidualAction($factory->moveContainerDownAction(), $harness, [], ['containerKey' => 'sidebar']);
    callLayoutBuilderResidualAction($factory->duplicateContainerAction(), $harness, [], ['containerKey' => 'sidebar']);
    callLayoutBuilderResidualAction($factory->changeLayoutAction(), $harness, ['layout_id' => $layout->getKey()]);

    expect($harness->saveLayout(withNotifications: true))->toBeTrue()
        ->and($harness->visualPreviewHtml())->toContain('Preview title')
        ->and($harness->visualPreviewSignature)->toBe('workflow-preview-signature')
        ->and($harness->visualPreviewStatus)->toBe('current')
        ->and($harness->selectedContainerKey)->toBe('main')
        ->and($harness->selectedWidgetIndex)->toBe(0)
        ->and($harness->editorMode)->toBe('content_first')
        ->and($harness->returnToContentItemKey)->toBe('content:hero')
        ->and($harness->layoutAreaOptions())->toHaveKey('main')
        ->and($harness->layoutAreaForContainer($harness->containers['main']))->toBe('main')
        ->and($harness->layoutAreaLabel('main'))->toBeString()
        ->and($harness->knownContainerKeys)->toContain('main', 'sidebar')
        ->and($harness->layoutModified)->toBeFalse()
        ->and($harness->layout->refresh()->containers)->toHaveKey('main');
});

it('renders deterministic layout preview images and signatures for varied containers', function (): void {
    $widget = Widget::factory()->create([
        'key' => 'preview-hero',
        'name' => 'Preview Hero',
        'admin' => ['icon' => 'heroicon-o-star'],
    ]);
    $layout = Layout::factory()->create([
        'key' => 'preview-layout',
        'name' => 'Preview Layout With A Very Long Name That Must Be Trimmed',
        'containers' => [
            'hero' => [
                'meta' => ['colspan' => 12],
                'widgets' => [
                    ['widget_key' => $widget->key],
                    ['widget_key' => 'missing-widget', 'meta' => ['name' => 'Missing']],
                ],
            ],
            'aside' => [
                'meta' => ['colspan' => 4],
                'widgets' => [],
            ],
            'content' => [
                'meta' => ['colspan' => 8],
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 2],
                ],
            ],
            'overflow-one' => ['meta' => ['colspan' => 12], 'widgets' => array_fill(0, 12, ['widget_key' => $widget->key])],
            'overflow-two' => ['meta' => ['colspan' => 12], 'widgets' => array_fill(0, 12, ['widget_key' => $widget->key])],
        ],
    ]);

    $signature = resolve(LayoutPreviewSignature::class);
    $payload = $signature->payload($layout);
    $png = resolve(LayoutPreviewRenderer::class)->render($layout);

    capell_expect($signature->forLayout($layout))->toHaveLength(64)
        ->and($payload['containers'])->toHaveCount(5)
        ->and($payload['containers'][0]['widgets'][0]['name'])->toBe('Preview Hero')
        ->and($png)->toStartWith("\x89PNG");
});

it('covers simple residual form configurators widgets enums and widget model branches', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    Page::factory()->withTranslations($language)->create([
        'name' => 'Draft page',
        'visible_from' => null,
    ]);
    Page::factory()->withTranslations($language)->create([
        'name' => 'Scheduled page',
        'visible_from' => now()->addDay(),
    ]);
    Page::factory()->withTranslations($language)->create([
        'name' => 'Expired page',
        'visible_from' => now()->subDays(2),
        'visible_until' => now()->subDay(),
    ]);

    $containerConfigurator = resolve(DefaultLayoutContainerConfigurator::class);
    $containerSchema = $containerConfigurator->make(Mockery::mock(Schema::class));
    $recentActivityData = invokeLayoutBuilderResidualMethod(new RecentActivityWidgetAbstract, 'getViewData')['data'];
    $tagSelect = TagSelect::make('tag');
    $assetTypeSelect = AssetTypeSelect::make('asset_type');

    $type = Blueprint::factory()->create([
        'type' => LayoutTypeEnum::Widget->value,
        'component' => 'type-component',
        'component_item' => 'type-item',
        'view_file' => 'type.view',
        'is_livewire' => true,
        'meta' => ['livewire' => false],
    ]);
    $widget = Widget::factory()->create([
        'blueprint_id' => $type->getKey(),
        'meta' => [
            'component' => 'meta-component',
            'component_item' => 'meta-item',
            'view_file' => 'meta.view',
            'livewire' => true,
            'extra' => 'kept',
        ],
    ]);
    $widget->setRelation('blueprint', $type);

    capell_expect($containerSchema)->toHaveCount(2)
        ->and($recentActivityData->items)->toHaveCount(3)
        ->and($tagSelect)->toBeInstanceOf(TagSelect::class)
        ->and($assetTypeSelect)->toBeInstanceOf(AssetTypeSelect::class)
        ->and(ActionLinkEnum::Page->getLabel())->toBeString()
        ->and(ActionLinkEnum::Link->getLabel())->toBeString()
        ->and(ActionLinkEnum::VideoPopup->getLabel())->toBeString()
        ->and(ActionLinkEnum::VideoPopup->getIcon())->toBe('heroicon-o-play-circle')
        ->and(ContainerAlignmentEnum::Stretch->getLabel())->toBeString()
        ->and(LayoutTypeEnum::Widget->getLabel())->toBeString()
        ->and(LayoutTypeEnum::Widget->getResource())->toBeString()
        ->and(LayoutTypeEnum::Widget->getModel())->toBe(Widget::class)
        ->and(LayoutTypeEnum::Widget->getTable())->toBe('widgets')
        ->and(LayoutTypeEnum::Widget->getCreatorClass())->toBeNull()
        ->and(TypeEnum::Widget->value)->toBeString()
        ->and(TypeEnum::Widget->getModel())->toBe(Widget::class)
        ->and(TypeEnum::Widget->getLabel())->toBeString()
        ->and(ResponsiveVisibilityEnum::Mobile->getLabel())->toBeString()
        ->and(ResponsiveVisibilityEnum::Tablet->getLabel())->toBeString()
        ->and(ResponsiveVisibilityEnum::Desktop->getLabel())->toBeString()
        ->and($widget->refresh()->getMetaComponent())->toBe('meta-component')
        ->and($widget->getComponentItem())->toBe('meta-item')
        ->and($widget->getViewFile())->toBe('meta.view')
        ->and($widget->getMetaComponentType())->toBe('livewire')
        ->and($widget->meta)->toBe(['extra' => 'kept']);
});

it('adds hero widgets to new and existing layout containers once', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero-widget']);
    $layout = Layout::factory()->create(['containers' => []]);

    AddHeroWidgetToLayoutAction::run($widget, $layout);
    AddHeroWidgetToLayoutAction::run($widget, $layout->refresh());

    capell_expect($layout->refresh()->containers)->toHaveKey('hero')
        ->and($layout->containers['hero']['meta']['container'])->toBe('full')
        ->and($layout->containers['hero']['widgets'])->toHaveCount(1)
        ->and($layout->containers['hero']['widgets'][0]['widget_key'])->toBe('hero-widget');
});

it('covers cold type configurator and modal page table setup with livewire table owner', function (): void {
    $widgetTypeConfigurator = resolve(WidgetTypeConfigurator::class);
    $schema = Schema::make()->operation('create');
    $widgetTypeSchema = $widgetTypeConfigurator->make($schema);
    $component = new LayoutBuilderResidualModalTableSelect;
    $component->tableConfiguration = PageSelectionTable::class;
    $component->tableQuery = Page::query();
    $component->tableArguments = [
        'excludeIds' => [123456],
        'pageId' => 654321,
        'siteId' => Site::factory()->create()->getKey(),
    ];

    $configuredTable = PageSelectionTable::configure(Table::make($component));
    $modalConfiguredTable = $component->table(Table::make($component));

    capell_expect($widgetTypeSchema)->toHaveCount(4)
        ->and($configuredTable)->toBeInstanceOf(Table::class)
        ->and($modalConfiguredTable)->toBeInstanceOf(Table::class)
        ->and($component->form(Schema::make()))->toBeInstanceOf(Schema::class)
        ->and($component->render()->name())->toBe('capell-layout-builder::livewire.filament.layout-builder.widgets-table-select');
});

it('loads frontend layout widgets into the layout manager and formats missing asset context', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $page = Page::factory()->withTranslations($language)->create();
    $widget = Widget::factory()->create(['key' => 'loaded-hero']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'loaded-hero'],
                    ['widget_key' => null],
                    ['widget_key' => 'missing-hero', 'occurrence' => 2],
                ],
            ],
        ],
    ]);

    $loader = Mockery::mock(LayoutLoader::class);
    $loader->shouldReceive('preloadLayoutWidgets')
        ->once()
        ->with($layout, $language, $page);
    $loader->shouldReceive('getLayoutWidget')
        ->once()
        ->with($layout, 'loaded-hero', $language, $page, 'main', 1)
        ->andReturn($widget);
    $loader->shouldReceive('getLayoutWidget')
        ->once()
        ->with($layout, 'missing-hero', $language, $page, 'main', 2)
        ->andReturn(null);
    app()->instance(LayoutLoader::class, $loader);
    app()->instance('capell.frontend.context', new LayoutBuilderResidualFrontendContextForLoadedLayout($layout, $language, $page));

    $listener = new LayoutLoaded;
    $listener->handle('otherEvent', new stdClass);
    $listener->handle('loadedLayout', new stdClass);

    $exception = new MissingWidgetAssetException($widget, 'page', ['id' => 10], ['container' => 'main']);

    capell_expect(CapellLayoutManager::getStoredContainerWidget('main', 'loaded-hero'))->toBe($widget)
        ->and($exception->getMessage())->toContain("Missing required 'page' asset")
        ->and($exception->getMessage())->toContain('Context:')
        ->and($exception->getContext())->toBe(['container' => 'main']);
});

function layoutBuilderResidualWidget(string $key): Widget
{
    return (new Widget)->forceFill([
        'id' => crc32($key),
        'key' => $key,
        'name' => str($key)->headline()->toString(),
    ]);
}
