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
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Actions\BuildLayoutBuilderTreeAction;
use Capell\LayoutBuilder\Data\AdminBlockPreviewData;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as SchemaFacade;
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
        ->assertSee(__('capell-layout-builder::heading.inspector'))
        ->assertSee(__('capell-layout-builder::message.preview_status_current'))
        ->assertSee(__('capell-layout-builder::message.container_empty'))
        ->assertSeeHtml('data-capell-layout-builder-admin-preview="true"')
        ->assertSeeHtml('capell-layout-builder:request-page-state');
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

it('renders widget rows in the structure tree and opens the inspector from the package namespace', function (): void {
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
        ->call('selectBlock', 'main', 0)
        ->assertSet('selectedContainerKey', 'main')
        ->assertSet('selectedBlockIndex', 0)
        ->assertSee(__('capell-layout-builder::button.edit_block'))
        ->assertSeeHtml('mountAction')
        ->assertDontSeeHtml('data-layout-content-action="editBlockAsset"');

    $treeView = file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/visual-tree.blade.php');
    $inspectorView = file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/visual-inspector.blade.php');

    expect($treeView)
        ->not->toContain('$this->editBlockAssetAction')
        ->and($inspectorView)
        ->toContain('$this->editBlockAction');
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
