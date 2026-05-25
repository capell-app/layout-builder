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
use Capell\Core\Models\Translation;
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

it('uses the content first editor mode by default from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => []],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first')
        ->assertSee(__('capell-layout-builder::generic.content_first_editor'))
        ->assertSee(__('capell-layout-builder::message.content_inventory_empty'));
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

it('can switch from content first to advanced layout and back from the package namespace', function (): void {
    $block = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [
            ['widget_key' => $block->key, 'occurrence' => 1],
        ]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('showAdvancedLayout', 'main:0:1:page:1:0')
        ->assertSet('editorMode', 'layout_first')
        ->assertSee(__('capell-layout-builder::button.return_to_content'))
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
        ->assertSee(__('capell-layout-builder::generic.content_first_editor'))
        ->assertDontSee(__('capell-layout-builder::button.advanced_layout'));

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

it('renders content first rows as custom action triggers instead of per row action schemas from the package namespace', function (): void {
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

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::form.search_content_inventory'))
        ->assertSee(__('capell-layout-builder::message.content_inventory_search_empty'))
        ->assertSee(__('capell-layout-builder::message.content_inventory_search_hint'))
        ->assertSeeHtml('data-layout-content-search-input')
        ->assertSeeHtml('data-layout-content-search-empty')
        ->assertSeeHtml('data-layout-content-search=')
        ->assertSeeHtml('data-layout-content-source-field=')
        ->assertSeeHtml('data-layout-content-action="editBlockAsset"')
        ->assertSeeHtml('mountAction')
        ->assertSeeHtml('wire:key="layout-content-group-')
        ->assertSeeHtml('wire:key="layout-content-item-');

    $contentFirstView = file_get_contents(__DIR__ . '/../../../resources/views/livewire/filament/layout-builder/content-first.blade.php');
    $assetRowView = file_get_contents(__DIR__ . '/../../../resources/views/components/filament/layout-builder/asset.blade.php');

    expect($contentFirstView)
        ->not->toContain('$this->editBlockAssetAction')
        ->and($assetRowView)
        ->not->toContain('$this->editBlockAssetAction');
});

it('renders block copy source rows with an edit action from the package namespace', function (): void {
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

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSee(__('capell-layout-builder::generic.block_content_sources'))
        ->assertSee(__('capell-layout-builder::generic.rendered_text'))
        ->assertSee('Every section can be rebuilt in the layout builder')
        ->assertSee(__('capell-layout-builder::button.edit_block_copy'))
        ->assertSeeHtml('data-layout-content-action="editBlock"');
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
        ->assertDontSee(__('capell-layout-builder::generic.content_first_editor'));
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
