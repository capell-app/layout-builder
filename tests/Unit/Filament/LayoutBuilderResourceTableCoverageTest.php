<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Tables\LayoutsTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetAssetsTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetsTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

function invokeLayoutBuilderTableMethod(string $className, string $methodName, mixed ...$arguments): mixed
{
    $method = new ReflectionMethod($className, $methodName);

    return $method->invoke(null, ...$arguments);
}

function createEnglishLayoutBuilderLanguage(): Language
{
    return Language::query()->create([
        'name' => 'English',
        'locale' => 'en',
        'code' => 'en',
        'flag' => 'gb-eng',
        'status' => true,
        'default' => true,
        'order' => 1,
    ]);
}

it('exposes widget resource metadata search details and soft-deleted query scope', function (): void {
    $language = createEnglishLayoutBuilderLanguage();
    $widget = Widget::factory()->create(['name' => 'Hero Widget', 'key' => 'hero-widget']);
    $widget->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Promo Hero',
    ]);
    $widget->load('translation');

    expect(WidgetResource::getModel())->toBe(Widget::class)
        ->and(WidgetResource::getResourceType())->toBe(ConfiguratorTypeEnum::Widget)
        ->and(WidgetResource::getNavigationLabel())->toBe(__('capell-layout-builder::navigation.widgets'))
        ->and(WidgetResource::getNavigationGroup())->toBeNull()
        ->and(WidgetResource::getNavigationParentItem())->toBe((string) __('capell-admin::navigation.website'))
        ->and(WidgetResource::getNavigationIcon())->toBe('heroicon-o-puzzle-piece')
        ->and(WidgetResource::getActiveNavigationIcon())->toBe('heroicon-s-puzzle-piece')
        ->and(WidgetResource::getSlug())->toBe('layout-builder/widgets')
        ->and(WidgetResource::getModelLabel())->toBe(__('capell-layout-builder::navigation.widget'))
        ->and(WidgetResource::getPluralModelLabel())->toBe(__('capell-layout-builder::navigation.widgets'))
        ->and(WidgetResource::shouldRegisterNavigation())->toBeTrue()
        ->and(WidgetResource::getGloballySearchableAttributes())->toContain('translations.title')
        ->and(WidgetResource::getGlobalSearchResultDetails($widget))->toBe([
            __('capell-admin::generic.title') => 'Promo Hero',
        ])
        ->and(LayoutResource::getModel())->toBe(Layout::class);

    $widget->delete();

    expect(WidgetResource::getEloquentQuery()->whereKey($widget->getKey())->exists())->toBeTrue()
        ->and(WidgetResource::getRelations())->toBe([])
        ->and(WidgetResource::getPages())->toHaveKeys(['index', 'edit', 'create']);
});

it('builds widget table columns filters and search query branches', function (): void {
    $language = createEnglishLayoutBuilderLanguage();
    $widget = Widget::factory()->create([
        'component' => 'hero-card',
        'component_item' => 'hero-card-item',
        'view_file' => 'widgets.hero-card',
    ]);
    $widget->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Hero',
        'content' => 'Needle content',
    ]);

    $columns = invokeLayoutBuilderTableMethod(WidgetsTable::class, 'getTableColumns');
    $filters = invokeLayoutBuilderTableMethod(WidgetsTable::class, 'getTableFilters');
    $tableSource = file_get_contents(__DIR__ . '/../../../src/Filament/Resources/Widgets/Tables/WidgetsTable.php');

    $contentSearchQuery = invokeLayoutBuilderTableMethod(
        WidgetsTable::class,
        'applyContentSearch',
        Widget::query(),
        'Needle content',
    );
    $componentSearchQuery = invokeLayoutBuilderTableMethod(
        WidgetsTable::class,
        'applyComponentSearch',
        Widget::query(),
        'hero-card',
    );

    $languageFilter = firstLayoutBuilderTableComponent($filters, 'filter');

    expect($columns)->toContainOnlyInstancesOf(Column::class)
        ->and($filters)->toHaveCount(5)
        ->and($contentSearchQuery->whereKey($widget->getKey())->exists())->toBeTrue()
        ->and($componentSearchQuery->whereKey($widget->getKey())->exists())->toBeTrue()
        ->and($languageFilter)->not->toBeNull()
        ->and($tableSource)->toContain('moveOrReplaceInLayouts')
        ->and($tableSource)->toContain('filters[widget_key][value]')
        ->and($tableSource)->not->toContain('filters[widget_id][value]');
});

it('covers widget asset table lookup and type helper branches', function (): void {
    $pageType = Blueprint::factory()->create([
        'name' => 'Article',
        'type' => 'page',
        'key' => 'article',
    ]);
    Page::factory()->create([
        'blueprint_id' => $pageType->getKey(),
    ]);

    $lookupKey = invokeLayoutBuilderTableMethod(WidgetAssetsTable::class, 'buildLookupKey', 'page', 123);
    $blankAssetTypes = invokeLayoutBuilderTableMethod(WidgetAssetsTable::class, 'getAssetTypes', '');
    $missingAssetTypes = invokeLayoutBuilderTableMethod(WidgetAssetsTable::class, 'getAssetTypes', 'missing');
    $pageAssetTypes = invokeLayoutBuilderTableMethod(WidgetAssetsTable::class, 'getAssetTypes', 'page');

    expect($lookupKey)->toBe('page:123')
        ->and($blankAssetTypes)->toBe([])
        ->and($missingAssetTypes)->toBe([])
        ->and($pageAssetTypes)->toHaveKey($pageType->getKey())
        ->and($pageType->exists)->toBeTrue();
});

it('filters indicates and creates widget assets through the widget assets table workflow', function (): void {
    $pageType = Blueprint::factory()->create([
        'name' => 'Landing Page',
        'type' => 'page',
        'key' => 'landing-page',
    ]);
    $page = Page::factory()->create([
        'name' => 'Campaign Landing Page',
        'blueprint_id' => $pageType->getKey(),
    ]);
    $secondPage = Page::factory()->create(['name' => 'Secondary Landing Page']);
    $widget = Widget::factory()->create(['name' => 'Campaign Hero']);
    $existingAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($page)
        ->page($page, 'main', 1)
        ->create();

    $table = WidgetAssetsTable::configure(layoutBuilderWidgetAssetsTable(WidgetAsset::query()));

    expect($table->getRecordUrl($existingAsset->fresh()))->toBeString();

    $filter = firstLayoutBuilderTableComponent($table->getFilters(), 'filter');
    expect($filter)->not->toBeNull();

    $filter = layoutBuilderTableObject($filter);
    $components = $filter->getSchemaComponents();
    $pagesSelect = firstLayoutBuilderTableComponent($components, 'pages', Select::class);

    expect($pagesSelect)->toBeInstanceOf(Select::class);

    $livewire = layoutBuilderWidgetAssetsTableLivewire(WidgetAsset::query());
    $pageOptions = layoutBuilderEvaluateComponentProperty(
        $pagesSelect,
        'options',
        ['livewire' => $livewire],
        [HasTable::class => $livewire],
    );

    $filteredQuery = $filter->apply(WidgetAsset::query(), [
        'asset_type' => $page->getMorphClass(),
        'blueprint_id' => $pageType->getKey(),
        'pages' => [
            '',
            $page->getMorphClass() . ':',
            $page->getMorphClass() . ':' . $page->getKey(),
        ],
    ]);
    $indicators = layoutBuilderEvaluateComponentProperty($filter, 'indicateUsing', [
        'data' => [
            'asset_type' => $page->getMorphClass(),
            'blueprint_id' => $pageType->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'pageable_id' => $page->getKey(),
        ],
    ]);

    $createAction = collect($table->getHeaderActions())
        ->first(fn (mixed $action): bool => $action instanceof CreateAction);
    expect($createAction)->toBeInstanceOf(CreateAction::class);

    $relationManager = new RelationManager;
    $relationManager->ownerRecord = $widget;

    $createdAsset = $createAction->process(null, [
        'data' => [
            'asset_id' => [$page->getKey(), $secondPage->getKey()],
            'asset_type' => $page->getMorphClass(),
        ],
        'livewire' => $relationManager,
    ]);

    expect($pageOptions)->toHaveKey($page->getMorphClass() . ':' . $page->getKey())
        ->and($filteredQuery->toSql())->toContain('asset_type', 'blueprint_id', 'pageable_type', 'pageable_id')
        ->and($indicators)->toHaveKeys(['asset_type', 'blueprint_id', 'page'])
        ->and($createdAsset)->toBeInstanceOf(WidgetAsset::class)
        ->and(WidgetAsset::query()->where('widget_id', $widget->getKey())->count())->toBe(3);
});

it('adds layout-builder specific layout table filters columns and query relations', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero']);

    $filters = invokeLayoutBuilderTableMethod(LayoutsTable::class, 'getTableFilters');
    $columns = invokeLayoutBuilderTableMethod(LayoutsTable::class, 'getTableColumns');
    $query = invokeLayoutBuilderTableMethod(
        LayoutsTable::class,
        'getTableQueryModifier',
        Layout::query(),
    );
    $bulkChangeAction = invokeLayoutBuilderTableMethod(LayoutsTable::class, 'getBulkChangeLayoutsAction');

    $widgetFilter = firstLayoutBuilderTableComponent($filters, 'widget_key', SelectFilter::class);

    expect($widgetFilter)->not->toBeNull()
        ->and($columns)->not->toBeEmpty()
        ->and($query)->toBeInstanceOf(Builder::class)
        ->and($bulkChangeAction)->toBeInstanceOf(Action::class)
        ->and($bulkChangeAction->getName())->toBe('bulkChangeLayouts')
        ->and($widget->exists)->toBeTrue()
        ->and(layoutBuilderTableContainsColumn($columns, ['layoutWidgets.name', 'admin.' . LayoutPreviewMetaKey::STATUS]))->toBeBool();
});

/**
 * @param  array<int, mixed>  $components
 * @param  class-string|null  $expectedClass
 */
function firstLayoutBuilderTableComponent(array $components, string $name, ?string $expectedClass = null): ?object
{
    foreach ($components as $component) {
        if (! is_object($component)) {
            continue;
        }

        if (! method_exists($component, 'getName')) {
            continue;
        }

        if ($component->getName() !== $name) {
            continue;
        }

        if ($expectedClass !== null && ! $component instanceof $expectedClass) {
            continue;
        }

        return $component;
    }

    return null;
}

/**
 * @param  array<int, mixed>  $columns
 * @param  array<int, string>  $names
 */
function layoutBuilderTableContainsColumn(array $columns, array $names): bool
{
    foreach ($columns as $column) {
        if ($column instanceof TextColumn && in_array($column->getName(), $names, true)) {
            return true;
        }
    }

    return false;
}

function layoutBuilderWidgetAssetsTable(Builder $query): Table
{
    return Table::make(layoutBuilderWidgetAssetsTableLivewire($query))->query($query);
}

function layoutBuilderWidgetAssetsTableLivewire(Builder $query): HasTable
{
    $livewire = Mockery::mock(HasTable::class);
    $table = Table::make($livewire)->query($query);

    $livewire->shouldReceive('makeFilamentTranslatableContentDriver')->andReturn(null)->byDefault();
    $livewire->shouldReceive('getTable')->andReturn($table)->byDefault();

    return $livewire;
}

function layoutBuilderTableObject(mixed $value): object
{
    throw_unless(is_object($value), RuntimeException::class, 'Expected a table component object.');

    return $value;
}

/**
 * @param  array<string, mixed>  $namedInjections
 * @param  array<class-string, mixed>  $typedInjections
 */
function layoutBuilderEvaluateComponentProperty(
    object $component,
    string $property,
    array $namedInjections = [],
    array $typedInjections = [],
): mixed {
    $reflection = new ReflectionClass($component);

    while ($reflection !== false) {
        if ($reflection->hasProperty($property)) {
            $propertyReflection = $reflection->getProperty($property);
            $value = $propertyReflection->getValue($component);

            return $component->evaluate($value, $namedInjections, $typedInjections);
        }

        $reflection = $reflection->getParentClass();
    }

    throw new RuntimeException(sprintf('Component property [%s] was not found.', $property));
}
