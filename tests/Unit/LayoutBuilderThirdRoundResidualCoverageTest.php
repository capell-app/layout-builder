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
use Capell\LayoutBuilder\Actions\AddHeroBlockToLayoutAction;
use Capell\LayoutBuilder\Actions\CreateLayoutBuilderDemoSiteAction;
use Capell\LayoutBuilder\Actions\GenerateLayoutPreviewImageAction;
use Capell\LayoutBuilder\Actions\InvalidateBlockLayoutPreviewImagesAction;
use Capell\LayoutBuilder\Actions\Mutations\CreateLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\Mutations\PasteLayoutFragmentAction;
use Capell\LayoutBuilder\Actions\ResolveAdminBlockPreviewDataAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Enums\ContainerAlignmentEnum;
use Capell\LayoutBuilder\Enums\LayoutPreviewStatusEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\ResponsiveVisibilityEnum;
use Capell\LayoutBuilder\Enums\TypeEnum;
use Capell\LayoutBuilder\Exceptions\MissingBlockAssetException;
use Capell\LayoutBuilder\Filament\Components\Forms\ActionsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\AssetTypeSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\BlockSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\TagSelect;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\DefaultLayoutContainerConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Types\BlockTypeConfigurator;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Pages\EditBlock;
use Capell\LayoutBuilder\Filament\Resources\Blocks\RelationManagers\LayoutsRelationManager;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Tables\BlockAssetsTable;
use Capell\LayoutBuilder\Filament\Resources\Pages\Tables\PageSelectionTable;
use Capell\LayoutBuilder\Filament\Widgets\LayoutHealthWidgetAbstract;
use Capell\LayoutBuilder\Filament\Widgets\RecentActivityWidgetAbstract;
use Capell\LayoutBuilder\Listeners\LayoutLoaded;
use Capell\LayoutBuilder\Livewire\Filament\Actions\LayoutBuilderActionFactory;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Livewire\Filament\ModalTableSelect;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Models\BlockAsset;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\Creator\BlockCreator;
use Capell\LayoutBuilder\Support\Creator\ContentCreator;
use Capell\LayoutBuilder\Support\Creator\DemoCreator;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewRenderer;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewSignature;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class LayoutBuilderResidualModalTableSelect extends ModalTableSelect
{
    public function exposeTableQuery(): Builder
    {
        return $this->getTableQuery();
    }

    public function exposeCanSubmitSelectedRecords(): bool
    {
        return $this->canSubmitSelectedRecords();
    }
}

final class LayoutBuilderResidualAssetHarness extends LayoutBuilder
{
    public function assertCanUpdateLayout(): void {}

    public function assertCanEditContent(): void {}

    public function assertCanEditLayout(): void {}

    /**
     * @param  array<string, array<int, Block>>  $containerBlocks
     */
    public function setContainerBlocks(array $containerBlocks): void
    {
        $this->containerBlocks = $containerBlocks;
    }

    public function exposeAddAssets(
        string $containerKey,
        int $blockIndex,
        ?bool $hasPageAssets,
        string $type,
        mixed $assets,
        array $assetsMeta = [],
    ): void {
        $this->addAssets($containerKey, $blockIndex, $hasPageAssets, $type, $assets, $assetsMeta);
    }

    public function exposeUpdateAssets(string $containerKey, int $blockIndex, ?string $oldContainerKey = null): void
    {
        $this->updateAssets($containerKey, $blockIndex, $oldContainerKey);
    }

    public function exposeLoadBlockAssetsFor(Block $block, string $containerKey, int $blockIndex): Collection
    {
        return $this->loadBlockAssetsFor($block, $containerKey, $blockIndex);
    }

    public function exposeLoadBlockAssets(Block $block, string $containerKey, int $blockOccurrence): Collection
    {
        return $this->loadBlockAssets($block, $containerKey, $blockOccurrence);
    }

    public function exposePreloadAllBlockAssets(): ?Collection
    {
        return $this->preloadAllBlockAssets();
    }

    public function exposeActiveBlockAssetIds(Block $block): array
    {
        return $this->activeBlockAssetIds($block);
    }

    public function exposeCreateBlockAsset(
        Block $block,
        string $containerKey,
        int $occurrence,
        bool $hasPageAssets,
        int $order,
        array $asset,
    ): BlockAsset {
        return $this->createBlockAsset($block, $containerKey, $occurrence, $hasPageAssets, $order, $asset);
    }

    public function exposeDeleteRemovedBlockAssets(): void
    {
        $this->deleteRemovedBlockAssets();
    }
}

final class LayoutBuilderResidualSuccessfulPreviewRenderer extends LayoutPreviewRenderer
{
    public function render(Layout $layout): string
    {
        return 'png:' . $layout->getKey();
    }
}

final class LayoutBuilderResidualFailingPreviewRenderer extends LayoutPreviewRenderer
{
    public function render(Layout $layout): string
    {
        throw new RuntimeException('Renderer failed with a deliberately long message for coverage.');
    }
}

final class LayoutBuilderResidualFrontendContextForLoadedLayout
{
    public function __construct(
        private readonly Layout $layout,
        private readonly Language $language,
        private readonly Page $page,
    ) {}

    public function layout(): Layout
    {
        return $this->layout;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function page(): Page
    {
        return $this->page;
    }
}

final class LayoutBuilderResidualEditBlockPage extends EditBlock
{
    public function __construct(public Block $testRecord)
    {
        $this->record = $testRecord;
    }

    public function getRecord(): Model
    {
        return $this->testRecord;
    }

    public function getRecordTitle(): string
    {
        return $this->testRecord->name;
    }

    public function exposeRelationManagers(): array
    {
        return $this->getRelationManagers();
    }

    public function exposeSubheading(): string
    {
        return (string) $this->getSubheading();
    }

    public function exposeBaseHeaderActions(): array
    {
        return $this->getBaseHeaderActions();
    }

    public function exposeRecordSwitcherColumns(): array
    {
        return $this->getRecordSwitcherColumns();
    }

    public function exposeRecordSwitcherSearchColumns(): array
    {
        return self::getRecordSwitcherSearchColumns();
    }

    public function exposeSelectChangerItemLabel(Block $block): string
    {
        return $this->selectChangerItemLabel($block);
    }
}

function invokeLayoutBuilderResidualMethod(string|object $classOrObject, string $methodName, mixed ...$arguments): mixed
{
    $method = new ReflectionMethod($classOrObject, $methodName);
    $method->setAccessible(true);

    return $method->invoke(is_string($classOrObject) ? null : $classOrObject, ...$arguments);
}

it('covers residual filament component and table configuration setup branches', function (): void {
    $blockSelect = BlockSelect::make('block_id')
        ->withCreateForm()
        ->withEditForm();
    $assetsRepeater = AssetsRepeater::make('assets');
    $actionsRepeater = ActionsRepeater::make('actions');
    $blockAssetColumns = invokeLayoutBuilderResidualMethod(BlockAssetsTable::class, 'getTableColumns');
    $blockAssetFilters = invokeLayoutBuilderResidualMethod(BlockAssetsTable::class, 'getTableFilters');

    expect($blockSelect)->toBeInstanceOf(BlockSelect::class)
        ->and($assetsRepeater)->toBeInstanceOf(Repeater::class)
        ->and($actionsRepeater)->toBeInstanceOf(Repeater::class)
        ->and($blockAssetColumns)->not->toBeEmpty()
        ->and($blockAssetFilters)->not->toBeEmpty();
});

it('covers modal table query label action and disabled submission branches', function (): void {
    $component = new LayoutBuilderResidualModalTableSelect;
    $component->tableArguments = ['siteId' => 5];
    $component->tableQuery = Block::query();
    $component->isDisabled = true;
    $component->selectedTableRecords = [];

    expect($component->getTableArguments())->toBe(['siteId' => 5])
        ->and($component->getSelectRecordsLabel())->toBe(__('capell-layout-builder::button.select_records'))
        ->and($component->selectRecordsAction())->toBeInstanceOf(Action::class)
        ->and($component->exposeTableQuery())->toBeInstanceOf(Builder::class)
        ->and($component->exposeCanSubmitSelectedRecords())->toBeFalse();

    $component->isDisabled = false;
    $component->selectedTableRecords = [1];
    $component->tableQuery = fn (): Builder => Block::query();

    expect($component->exposeTableQuery())->toBeInstanceOf(Builder::class)
        ->and($component->exposeCanSubmitSelectedRecords())->toBeTrue();
});

it('aggregates layout health widget data for grouped unused and least-used blocks', function (): void {
    $publishedType = Blueprint::factory()->create([
        'name' => 'Marketing',
        'type' => 'block',
        'group' => 'marketing',
    ]);
    $pendingType = Blueprint::factory()->create([
        'name' => 'Commerce',
        'type' => 'block',
        'group' => 'commerce',
    ]);

    $publishedBlock = Block::factory()->create([
        'name' => 'Published Hero',
        'blueprint_id' => $publishedType->getKey(),
        'visible_from' => now()->subDay(),
        'visible_until' => null,
    ]);
    $pendingBlock = Block::factory()->create([
        'name' => 'Pending CTA',
        'blueprint_id' => $pendingType->getKey(),
        'visible_from' => now()->addDay(),
        'visible_until' => null,
    ]);
    $expiredBlock = Block::factory()->create([
        'name' => 'Expired Banner',
        'blueprint_id' => $publishedType->getKey(),
        'visible_from' => now()->subDays(3),
        'visible_until' => now()->subDay(),
    ]);

    BlockAsset::factory()->block($publishedBlock)->asset(Page::factory()->create())->create();

    $widget = new LayoutHealthWidgetAbstract;
    $viewData = invokeLayoutBuilderResidualMethod($widget, 'getViewData');
    $data = $viewData['data'];

    expect($data->totalBlocks)->toBeGreaterThanOrEqual(3)
        ->and($data->blocksByGroup->pluck('group')->all())->toContain('marketing', 'commerce')
        ->and($data->leastUsedBlocks->pluck('name')->all())->toContain($pendingBlock->name)
        ->and($data->unusedBlocks->pluck('name')->all())->toContain($pendingBlock->name, $expiredBlock->name)
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

    expect($layout->refresh()->admin[LayoutPreviewMetaKey::STATUS] ?? null)->toBeNull();

    GenerateLayoutPreviewImageAction::run((int) $layout->getKey(), 'matching-signature');

    $layout->refresh();
    $path = $layout->admin[LayoutPreviewMetaKey::IMAGE];

    Storage::disk('public')->assertExists($path);

    expect($layout->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Ready->value)
        ->and($layout->admin[LayoutPreviewMetaKey::ERROR])->toBeNull();

    app()->instance(LayoutPreviewRenderer::class, new LayoutBuilderResidualFailingPreviewRenderer);

    GenerateLayoutPreviewImageAction::run((int) $layout->getKey(), 'matching-signature');

    expect($layout->refresh()->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Failed->value)
        ->and($layout->admin[LayoutPreviewMetaKey::IMAGE])->toBeNull()
        ->and($layout->admin[LayoutPreviewMetaKey::ERROR])->toContain('Renderer failed');
});

it('invalidates generated previews for layouts containing changed block keys', function (): void {
    Storage::fake('public');
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'blocks' => [
                    ['block_key' => 'hero'],
                ],
            ],
        ],
        'admin' => [
            LayoutPreviewMetaKey::IMAGE => 'generated-layout-previews/old.png',
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Ready->value,
        ],
    ]);
    DB::table('layouts')
        ->where('id', $layout->getKey())
        ->update(['blocks' => json_encode(['hero', 'cta'], JSON_THROW_ON_ERROR)]);
    Layout::factory()->create(['blocks' => ['other']]);
    Storage::disk('public')->put('generated-layout-previews/old.png', 'old');

    $invalidated = InvalidateBlockLayoutPreviewImagesAction::run(['', null, 'hero', 'hero']);

    expect($invalidated)->toBe(1)
        ->and($layout->refresh()->admin[LayoutPreviewMetaKey::STATUS])
        ->toBeIn([LayoutPreviewStatusEnum::Pending->value, LayoutPreviewStatusEnum::Ready->value])
        ->and($layout->admin[LayoutPreviewMetaKey::SIGNATURE] ?? null)->toBeString();
    Storage::disk('public')->assertMissing('generated-layout-previews/old.png');
});

it('resolves admin block preview data for page content custom views and loaded images', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $page = Page::factory()->withTranslations($language)->create(['name' => 'Fallback Page']);
    $page->translation->forceFill([
        'title' => '',
        'content' => ['intro' => ['Nested <strong>content</strong>']],
    ])->save();
    $page->load('translation');

    $pageContentBlock = Block::factory()->create([
        'component' => BlockComponentEnum::PageContent->value,
        'admin' => [
            'admin_preview_view' => 'capell-layout-builder::filament.layout-builder.previews.custom',
            'type' => 'Page copy',
            'icon' => 'heroicon-o-document-text',
        ],
    ]);

    $pageContentPreview = ResolveAdminBlockPreviewDataAction::run(
        $pageContentBlock,
        ['meta' => ['name' => 'Layout Label']],
        $page,
        2,
        true,
    );

    $block = Block::factory()->create(['admin' => ['admin_preview_view' => 'not-a-preview-view']]);
    $block->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Block title',
        'content' => '<p>Block excerpt</p>',
    ]);
    $block->load('translation');

    $blockPreview = ResolveAdminBlockPreviewDataAction::run($block, [], null, 0, false);

    expect($pageContentPreview->view)->toBe('capell-layout-builder::filament.layout-builder.previews.custom')
        ->and($pageContentPreview->label)->toBe('Layout Label')
        ->and($pageContentPreview->title)->toBe('Fallback Page')
        ->and($pageContentPreview->excerpt)->toContain('Nested content')
        ->and($pageContentPreview->typeLabel)->toBe('Page copy')
        ->and($pageContentPreview->icon)->toBe('heroicon-o-document-text')
        ->and($blockPreview->view)->toBe('capell-layout-builder::filament.layout-builder.previews.default')
        ->and($blockPreview->title)->toBe('Block title')
        ->and($blockPreview->excerpt)->toBe('Block excerpt');
});

it('copies and pastes layout fragments with unique container and block anchors', function (): void {
    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'blocks' => [
                    [
                        'block_key' => 'hero',
                        'meta' => [
                            'block_settings' => [
                                'anchor_id' => 'Shared Anchor',
                            ],
                        ],
                    ],
                ],
            ],
            'main-copy' => [
                'blocks' => [],
            ],
            'aside' => [
                'blocks' => [
                    [
                        'block_key' => 'cta',
                        'meta' => [
                            'block_settings' => [
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

    expect($containerResult->state->containers)->toHaveKey('main-copy-2')
        ->and($containerResult->state->containers['main-copy-2']['blocks'][0]['meta']['block_settings']['anchor_id'])
        ->toBe('shared-anchor-2');

    $blockFragment = CreateLayoutFragmentAction::run($state, 'main', 0);
    $blockResult = PasteLayoutFragmentAction::run($state, $blockFragment, 'aside', 0);

    expect($blockResult->state->containers['aside']['blocks'][0]['block_key'])->toBe('hero')
        ->and($blockResult->state->containers['aside']['blocks'][0]['meta']['block_settings']['anchor_id'])
        ->toBe('shared-anchor-2')
        ->and($blockResult->state->assets['aside'][0])->toBe([['asset_id' => 1, 'asset_type' => 'page']]);

    $missingFragment = CreateLayoutFragmentAction::run($state, 'missing', null);
    $unchangedResult = PasteLayoutFragmentAction::run($state, $missingFragment, 'missing');

    expect($missingFragment->container)->toBeNull()
        ->and($unchangedResult->state->containers)->toBe($state->containers);
});

it('covers edit block page relation metadata and relation manager table setup', function (): void {
    $type = Blueprint::factory()->create(['name' => 'Hero Type', 'type' => 'block']);
    $block = Block::factory()->create([
        'name' => 'Editable Hero',
        'blueprint_id' => $type->getKey(),
    ]);
    $block->setRelation('type', $type);

    $page = new LayoutBuilderResidualEditBlockPage($block);
    $relationManager = new LayoutsRelationManager;
    $relationTable = $relationManager->table(Table::make($relationManager));

    expect((string) $page->getTitle())->toContain('Editable Hero')
        ->and($page->exposeSubheading())->toContain('Hero Type')
        ->and($page->exposeBaseHeaderActions())->not->toBeEmpty()
        ->and($page->exposeRecordSwitcherColumns())->toBe(['name', 'admin'])
        ->and($page->exposeRecordSwitcherSearchColumns())->toBe(['name', '`key`', 'admin->notes'])
        ->and($page->exposeSelectChangerItemLabel($block))->toBe('Editable Hero')
        ->and(LayoutsRelationManager::getTitle($block, EditBlock::class))->toBe(__('capell-admin::generic.layouts'))
        ->and($relationTable)->toBeInstanceOf(Table::class);
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
            'main' => ['blocks' => []],
        ],
    ]);
    $page = Page::factory()
        ->home()
        ->site($site)
        ->withTranslations($language)
        ->create(['layout_id' => $layout->getKey()]);
    $site->setRelation('languages', new Collection([$language]));

    $demoCreator = Mockery::mock(DemoCreator::class);
    $demoCreator->shouldReceive('createPageCardsBlock')->twice()->andReturn(
        layoutBuilderResidualBlock('page-cards'),
        layoutBuilderResidualBlock('page-cards-two'),
    );
    $demoCreator->shouldReceive('createGalleryBlock')->once()->andReturn(layoutBuilderResidualBlock('gallery'));
    $demoCreator->shouldReceive('createMediaCarouselBlock')->once()->andReturn(layoutBuilderResidualBlock('carousel'));
    $demoCreator->shouldReceive('createFaqBlock')->once()->andReturn(layoutBuilderResidualBlock('faq'));
    $demoCreator->shouldReceive('createStaticNavigationBlock')->once()->andReturn(layoutBuilderResidualBlock('static-nav'));
    $demoCreator->shouldReceive('createModernFeatureListBlock')->once()->andReturn(layoutBuilderResidualBlock('modern-feature-list'));
    $demoCreator->shouldReceive('createTeamPortfolioBlock')->once()->andReturn(layoutBuilderResidualBlock('team-portfolio'));
    $demoCreator->shouldReceive('createModernTeamMembersBlock')->once()->andReturn(layoutBuilderResidualBlock('modern-team'));
    $demoCreator->shouldReceive('createBannerImageBlock')->once()->andReturn(layoutBuilderResidualBlock('banner-image'));
    $demoCreator->shouldReceive('createContentBlock')->once()->andReturn(layoutBuilderResidualBlock('content'));
    $demoCreator->shouldReceive('createStatisticsBlock')->once()->andReturn(layoutBuilderResidualBlock('statistics'));
    $demoCreator->shouldReceive('createModernPricingTableBlock')->once()->andReturn(layoutBuilderResidualBlock('pricing'));
    $demoCreator->shouldReceive('createBusinessFeaturesBlock')->once()->andReturn(layoutBuilderResidualBlock('business-features'));
    $demoCreator->shouldReceive('createBannersBlock')->once()->andReturn(layoutBuilderResidualBlock('banners'));
    $demoCreator->shouldReceive('createClientLogosBlock')->once()->andReturn(layoutBuilderResidualBlock('client-logos'));
    $demoCreator->shouldReceive('createModernTestimonialsBlock')->once()->andReturn(layoutBuilderResidualBlock('testimonials'));
    $demoCreator->shouldReceive('createModernFaqBlock')->once()->andReturn(layoutBuilderResidualBlock('modern-faq'));
    $demoCreator->shouldReceive('createModernStatsSectionBlock')->once()->andReturn(layoutBuilderResidualBlock('modern-stats'));
    $demoCreator->shouldReceive('createModernAlternatingContentBlock')->once()->andReturn(layoutBuilderResidualBlock('alternating'));
    $demoCreator->shouldReceive('createModernProcessStepsBlock')->once()->andReturn(layoutBuilderResidualBlock('process'));
    $demoCreator->shouldReceive('createModernImageGalleryBlock')->once()->andReturn(layoutBuilderResidualBlock('modern-gallery'));
    $demoCreator->shouldReceive('createApHeroBannerBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-hero'));
    $demoCreator->shouldReceive('createApCardGridBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-card'));
    $demoCreator->shouldReceive('createApFeatureListBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-feature'));
    $demoCreator->shouldReceive('createApCtaSectionBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-cta'));
    $demoCreator->shouldReceive('createApImageGalleryBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-gallery'));
    $demoCreator->shouldReceive('createSplitContentBlock')->once()->andReturn(layoutBuilderResidualBlock('split-content'));
    $demoCreator->shouldReceive('addSplitTwoBackgroundMedia')->once()->with(Mockery::type(Layout::class));

    $blockCreator = Mockery::mock(BlockCreator::class);
    $blockCreator->shouldReceive('apHeroBannerBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-hero-catalog'));
    $blockCreator->shouldReceive('apCardGridBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-card-catalog'));
    $blockCreator->shouldReceive('apFeatureListBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-feature-catalog'));
    $blockCreator->shouldReceive('apCtaSectionBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-cta-catalog'));
    $blockCreator->shouldReceive('apImageGalleryBlock')->once()->andReturn(layoutBuilderResidualBlock('ap-gallery-catalog'));
    app()->instance(BlockCreator::class, $blockCreator);

    $action = new CreateLayoutBuilderDemoSiteAction;
    $demoCreatorProperty = new ReflectionProperty($action, 'demoCreator');
    $demoCreatorProperty->setAccessible(true);
    $demoCreatorProperty->setValue($action, $demoCreator);

    invokeLayoutBuilderResidualMethod($action, 'setupHomepage', $page, new Collection([$language]));

    $containers = $layout->refresh()->containers;

    expect($page->refresh()->layout_id)->toBe($layout->getKey())
        ->and($containers['main']['blocks'])->toHaveCount(4)
        ->and($containers['faq-main']['blocks'][0]['block_key'])->toBe('faq')
        ->and($containers['faq-col']['blocks'][0]['block_key'])->toBe('static-nav')
        ->and($containers['secondary']['blocks'])->toHaveCount(16)
        ->and($containers['ap-blocks']['blocks'])->toHaveCount(5)
        ->and($containers['split-two']['blocks'][0]['block_key'])->toBe('split-content');
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

    expect($createdContent)->toHaveCount(2)
        ->and($createdContent[0]['translations']['en']['title'])->toBe('Parent')
        ->and($createdContent[1]['parent_id'])->not->toBeNull();
});

it('adds updates preloads and deletes page-scoped block assets through the editor concern', function (): void {
    $site = Site::factory()->default()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();
    $firstAssetPage = Page::factory()->site($site)->withTranslations()->create();
    $secondAssetPage = Page::factory()->site($site)->withTranslations()->create();
    $block = Block::factory()->create([
        'key' => 'asset-block',
        'admin' => [
            'asset_types' => ['page'],
        ],
    ]);
    $block->setRelation('assets', new Collection);

    $harness = new LayoutBuilderResidualAssetHarness;
    $harness->layout = Layout::factory()->create();
    $harness->page = $page;
    $harness->containers = [
        'main' => [
            'blocks' => [
                ['block_key' => $block->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = ['main' => [[]]];
    $harness->selectedRecords = ['main' => [[]]];
    $harness->originalAssets = ['main' => [[]]];
    $harness->setContainerBlocks(['main' => [$block]]);

    $harness->exposeAddAssets('main', 0, true, 'missing-type', [$firstAssetPage->getKey()]);
    $createdAsset = $harness->exposeCreateBlockAsset(
        $block,
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

    expect($harness->assets['main'][0])->toBe([])
        ->and($createdAsset->pageable_id)->toBe($page->getKey())
        ->and($createdAsset->container)->toBe('main')
        ->and($harness->exposeActiveBlockAssetIds($block))->toBe([]);

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
    $block->load('assets');
    $harness->setContainerBlocks(['main' => [$block]]);

    expect(BlockAsset::query()->where('block_id', $block->getKey())->count())->toBe(2)
        ->and($createdAsset->refresh()->order)->toBe(2)
        ->and($createdAsset->meta)->toBe(['caption' => 'Updated'])
        ->and($harness->exposeLoadBlockAssets($block, 'main', 1))->toHaveCount(2)
        ->and($harness->exposeLoadBlockAssetsFor($block, 'main', 0))->toBeInstanceOf(Collection::class)
        ->and($harness->exposePreloadAllBlockAssets())->toBeInstanceOf(Collection::class);

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
                    'original_block_id' => $block->getKey(),
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
            ],
        ],
    ];
    $harness->assets['main'][0] = [];

    $harness->exposeDeleteRemovedBlockAssets();

    expect(BlockAsset::query()->whereKey($createdAsset->getKey())->exists())->toBeFalse();
});

it('covers layout builder public editor helpers and action factory private branches', function (): void {
    $site = Site::factory()->default()->create();
    $layout = Layout::factory()->create(['site_id' => $site->getKey()]);
    $page = Page::factory()->site($site)->withTranslations()->create(['layout_id' => $layout->getKey()]);
    $otherPage = Page::factory()->site($site)->withTranslations()->create(['layout_id' => $layout->getKey()]);
    $block = Block::factory()->create(['key' => 'factory-block']);
    $assetPage = Page::factory()->site($site)->withTranslations()->create();
    $blockAsset = BlockAsset::factory()
        ->block($block)
        ->asset($assetPage)
        ->create([
            'container' => 'main',
            'occurrence' => 1,
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
        ]);
    $block->load('assets');

    $harness = new LayoutBuilderResidualAssetHarness;
    $harness->site = $site;
    $harness->layout = $layout;
    $harness->page = $page;
    $harness->containers = [
        'main' => [
            'blocks' => [
                ['block_key' => $block->key, 'occurrence' => 1],
            ],
        ],
    ];
    $harness->assets = [
        'main' => [
            [
                [
                    'id' => $blockAsset->getKey(),
                    'asset_type' => $blockAsset->asset_type,
                    'asset_id' => $blockAsset->asset_id,
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
    $harness->setContainerBlocks(['main' => [$block]]);

    $factory = new LayoutBuilderActionFactory($harness);
    $assetType = CapellCore::getAssets()->keys()->first();

    $record = invokeLayoutBuilderResidualMethod($factory, 'makeBlockAssetRecordForCreate', [
        'containerKey' => 'main',
        'blockIndex' => 0,
        'type' => $assetType,
    ]);
    $editableAsset = invokeLayoutBuilderResidualMethod($factory, 'resolveEditableBlockAsset', [
        'containerKey' => 'main',
        'blockIndex' => 0,
        'index' => 0,
        'type' => $blockAsset->asset_type,
    ]);
    $pageHeading = invokeLayoutBuilderResidualMethod($factory, 'getEditBlockAssetModalHeading', $harness, ['type' => $blockAsset->asset_type]);
    $pageDescription = invokeLayoutBuilderResidualMethod($factory, 'getEditBlockAssetModalDescription', $harness, [
        'containerKey' => 'main',
        'blockIndex' => 0,
        'index' => 0,
    ]);
    $changeLayoutSchema = invokeLayoutBuilderResidualMethod($factory, 'getChangeLayoutSchema');

    expect($harness->layoutPagesCount())->toBeGreaterThanOrEqual(2)
        ->and($harness->layoutIsUsedByPages())->toBeTrue()
        ->and($harness->otherPagesUsingLayoutCount())->toBe(1)
        ->and($harness->layoutIsSharedWithOtherPages())->toBeTrue()
        ->and($harness->getPagesUsingLayoutUrl())->toContain('layout_id')
        ->and($harness->getCurrentResource())->toBeString()
        ->and($harness->getPageResource())->toBeString()
        ->and($harness->placeholder(['label' => 'Loading'])->name())->toBe('capell-admin::components.placeholder')
        ->and($record)->toBeInstanceOf(BlockAsset::class)
        ->and($record->block_id)->toBe($block->getKey())
        ->and($editableAsset->getKey())->toBe($blockAsset->getKey())
        ->and($pageHeading)->toContain(str($blockAsset->asset_type)->title()->toString())
        ->and($pageDescription)->toContain($page->name)
        ->and($changeLayoutSchema)->not->toBeEmpty()
        ->and($otherPage->exists)->toBeTrue();

    $harness->page = null;
    invokeLayoutBuilderResidualMethod($factory, 'changePageLayout', $layout->getKey());

    expect($harness->otherPagesUsingLayoutCount())->toBeGreaterThanOrEqual(2)
        ->and(invokeLayoutBuilderResidualMethod($factory, 'getEditBlockAssetModalDescription', $harness, [
            'containerKey' => 'main',
            'blockIndex' => 0,
            'index' => 0,
        ]))->toBeNull();
});

it('renders deterministic layout preview images and signatures for varied containers', function (): void {
    $block = Block::factory()->create([
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
                'blocks' => [
                    ['block_key' => $block->key],
                    ['block_key' => 'missing-block', 'meta' => ['name' => 'Missing']],
                ],
            ],
            'aside' => [
                'meta' => ['colspan' => 4],
                'blocks' => [],
            ],
            'content' => [
                'meta' => ['colspan' => 8],
                'blocks' => [
                    ['block_key' => $block->key, 'occurrence' => 2],
                ],
            ],
            'overflow-one' => ['meta' => ['colspan' => 12], 'blocks' => array_fill(0, 12, ['block_key' => $block->key])],
            'overflow-two' => ['meta' => ['colspan' => 12], 'blocks' => array_fill(0, 12, ['block_key' => $block->key])],
        ],
    ]);

    $signature = resolve(LayoutPreviewSignature::class);
    $payload = $signature->payload($layout);
    $png = resolve(LayoutPreviewRenderer::class)->render($layout);

    expect($signature->forLayout($layout))->toHaveLength(64)
        ->and($payload['containers'])->toHaveCount(5)
        ->and($payload['containers'][0]['blocks'][0]['name'])->toBe('Preview Hero')
        ->and($png)->toStartWith("\x89PNG");
});

it('covers simple residual form configurators widgets enums and block model branches', function (): void {
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
        'type' => LayoutTypeEnum::Block->value,
        'component' => 'type-component',
        'component_item' => 'type-item',
        'view_file' => 'type.view',
        'is_livewire' => true,
        'meta' => ['livewire' => false],
    ]);
    $block = Block::factory()->create([
        'blueprint_id' => $type->getKey(),
        'meta' => [
            'component' => 'meta-component',
            'component_item' => 'meta-item',
            'view_file' => 'meta.view',
            'livewire' => true,
            'extra' => 'kept',
        ],
    ]);
    $block->setRelation('blueprint', $type);

    expect($containerSchema)->toHaveCount(2)
        ->and($recentActivityData->items)->toHaveCount(3)
        ->and($tagSelect)->toBeInstanceOf(TagSelect::class)
        ->and($assetTypeSelect)->toBeInstanceOf(AssetTypeSelect::class)
        ->and(ActionLinkEnum::Page->getLabel())->toBeString()
        ->and(ActionLinkEnum::Link->getLabel())->toBeString()
        ->and(ContainerAlignmentEnum::Stretch->getLabel())->toBeString()
        ->and(LayoutTypeEnum::Block->getLabel())->toBeString()
        ->and(LayoutTypeEnum::Block->getResource())->toBeString()
        ->and(LayoutTypeEnum::Block->getModel())->toBe(Block::class)
        ->and(LayoutTypeEnum::Block->getTable())->toBe('blocks')
        ->and(LayoutTypeEnum::Block->getCreatorClass())->toBeNull()
        ->and(TypeEnum::Block->value)->toBeString()
        ->and(TypeEnum::Block->getModel())->toBe(Block::class)
        ->and(TypeEnum::Block->getLabel())->toBeString()
        ->and(ResponsiveVisibilityEnum::Mobile->getLabel())->toBeString()
        ->and(ResponsiveVisibilityEnum::Tablet->getLabel())->toBeString()
        ->and(ResponsiveVisibilityEnum::Desktop->getLabel())->toBeString()
        ->and($block->refresh()->getMetaComponent())->toBe('meta-component')
        ->and($block->getComponentItem())->toBe('meta-item')
        ->and($block->getViewFile())->toBe('meta.view')
        ->and($block->getMetaComponentType())->toBe('livewire')
        ->and($block->meta)->toBe(['extra' => 'kept']);
});

it('adds hero blocks to new and existing layout containers once', function (): void {
    $block = Block::factory()->create(['key' => 'hero-block']);
    $layout = Layout::factory()->create(['containers' => []]);

    AddHeroBlockToLayoutAction::run($block, $layout);
    AddHeroBlockToLayoutAction::run($block, $layout->refresh());

    expect($layout->refresh()->containers)->toHaveKey('hero')
        ->and($layout->containers['hero']['meta']['container'])->toBe('full')
        ->and($layout->containers['hero']['blocks'])->toHaveCount(1)
        ->and($layout->containers['hero']['blocks'][0]['block_key'])->toBe('hero-block');
});

it('covers cold type configurator and modal page table setup with livewire table owner', function (): void {
    $blockTypeConfigurator = resolve(BlockTypeConfigurator::class);
    $schema = Mockery::mock(Schema::class)->shouldIgnoreMissing();
    $blockTypeSchema = $blockTypeConfigurator->make($schema);
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

    expect($blockTypeSchema)->toHaveCount(4)
        ->and($configuredTable)->toBeInstanceOf(Table::class)
        ->and($modalConfiguredTable)->toBeInstanceOf(Table::class)
        ->and($component->form(Mockery::mock(Schema::class)->shouldIgnoreMissing()))->toBeInstanceOf(Schema::class)
        ->and($component->render()->name())->toBe('capell-layout-builder::livewire.filament.layout-builder.blocks-table-select');
});

it('loads frontend layout blocks into the layout manager and formats missing asset context', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $page = Page::factory()->withTranslations($language)->create();
    $block = Block::factory()->create(['key' => 'loaded-hero']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'blocks' => [
                    ['block_key' => 'loaded-hero'],
                    ['block_key' => null],
                    ['block_key' => 'missing-hero', 'occurrence' => 2],
                ],
            ],
        ],
    ]);

    $loader = Mockery::mock(LayoutLoader::class);
    $loader->shouldReceive('preloadLayoutBlocks')
        ->once()
        ->with($layout, $language, $page);
    $loader->shouldReceive('getLayoutBlock')
        ->once()
        ->with($layout, 'loaded-hero', $language, $page, 'main', 1)
        ->andReturn($block);
    $loader->shouldReceive('getLayoutBlock')
        ->once()
        ->with($layout, 'missing-hero', $language, $page, 'main', 2)
        ->andReturn(null);
    app()->instance(LayoutLoader::class, $loader);
    app()->instance('capell.frontend.context', new LayoutBuilderResidualFrontendContextForLoadedLayout($layout, $language, $page));

    $listener = new LayoutLoaded;
    $listener->handle('otherEvent', new stdClass);
    $listener->handle('loadedLayout', new stdClass);

    $exception = new MissingBlockAssetException($block, 'page', ['id' => 10], ['container' => 'main']);

    expect(CapellLayoutManager::getStoredContainerBlock('main', 'loaded-hero'))->toBe($block)
        ->and($exception->getMessage())->toContain("Missing required 'page' asset")
        ->and($exception->getMessage())->toContain('Context:')
        ->and($exception->getContext())->toBe(['container' => 'main']);
});

function layoutBuilderResidualBlock(string $key): Block
{
    return (new Block)->forceFill([
        'id' => crc32($key),
        'key' => $key,
        'name' => str($key)->headline()->toString(),
    ]);
}
