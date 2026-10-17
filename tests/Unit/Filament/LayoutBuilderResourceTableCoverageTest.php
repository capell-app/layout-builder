<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\WidgetSelect;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Tables\LayoutsTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetAssetsTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetSelectionTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetsTable;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\Filament\AdminSurfaceAssertions;
use Capell\Tests\Support\RenderedHtml;
use Capell\Tests\Support\SchemaStateHarness;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

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
        ->and(WidgetResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_websites'))
        ->and(WidgetResource::getNavigationParentItem())->toBeNull()
        ->and(WidgetResource::getNavigationIcon())->toBe('heroicon-o-puzzle-piece')
        ->and(WidgetResource::getActiveNavigationIcon())->toBe('heroicon-s-puzzle-piece')
        ->and(WidgetResource::getSlug())->toBe('layout-builder/widgets')
        ->and(WidgetResource::getModelLabel())->toBe(__('capell-layout-builder::model_labels.widget'))
        ->and(WidgetResource::getPluralModelLabel())->toBe(__('capell-layout-builder::model_labels.widgets'))
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
    test()->actingAsAdmin();

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

    $columns = array_values(configureLayoutBuilderWidgetsTable()->getColumns());
    $filters = layoutBuilderTableComponents(array_values(configureLayoutBuilderWidgetsTable()->getFilters()));

    $table = configureLayoutBuilderWidgetsTable();
    $contentColumn = $table->getColumn('translation.content');
    $componentColumn = $table->getColumn('component');
    throw_unless($contentColumn instanceof TextColumn && $componentColumn instanceof TextColumn, RuntimeException::class);
    $isFirst = true;
    $contentSearchQuery = $contentColumn->applySearchConstraint(Widget::query(), 'Needle content', $isFirst);
    $isFirst = true;
    $componentSearchQuery = $componentColumn->applySearchConstraint(Widget::query(), 'hero-card', $isFirst);

    $languageFilter = firstLayoutBuilderTableComponent($filters, 'filter');
    $unusedFilter = firstLayoutBuilderTableComponent($filters, 'unused', Filter::class);
    $unusedWidget = Widget::factory()->create(['key' => 'unused-widget', 'status' => true]);
    $usedWidget = Widget::factory()->create(['key' => 'used-widget', 'status' => true]);
    Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $usedWidget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);
    if (! $unusedFilter instanceof Filter) {
        throw new RuntimeException('Expected an unused widget filter.');
    }

    $unusedQuery = Widget::query();
    $unusedQuery->scopes('withLayoutsCount');
    $unusedWidgetIds = $unusedFilter->apply($unusedQuery, ['isActive' => true])->pluck('id');

    expect($columns)->toContainOnlyInstancesOf(Column::class)
        ->and($filters)->toHaveCount(6)
        ->and($contentSearchQuery->whereKey($widget->getKey())->exists())->toBeTrue()
        ->and($componentSearchQuery->whereKey($widget->getKey())->exists())->toBeTrue()
        ->and($languageFilter)->not->toBeNull()
        ->and($unusedFilter)->toBeInstanceOf(Filter::class)
        ->and($unusedWidgetIds)->toContain($unusedWidget->getKey())
        ->and($unusedWidgetIds)->not->toContain($usedWidget->getKey());
});

it('links widget usage to layouts by key and preselects the bulk change source', function (): void {
    test()->actingAsAdmin();
    $widget = Widget::factory()->create(['key' => 'customer-feature', 'status' => true]);
    Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]],
    ]]);
    $usageQuery = Widget::query();
    $usageQuery->scopes('withLayoutsCount');
    $widget = $usageQuery->whereKey($widget)->firstOrFail();
    $usageCount = $widget->getAttribute('layouts_count');
    throw_unless(is_int($usageCount), RuntimeException::class);
    $table = configureLayoutBuilderWidgetsTable();
    $column = $table->getColumn('layouts_count');
    throw_unless($column instanceof TextColumn, RuntimeException::class);
    $column->record($widget);
    $formatted = $column->formatState($usageCount);
    throw_unless($formatted instanceof Htmlable, RuntimeException::class);
    $document = new RenderedHtml($formatted->toHtml());
    $query = parse_url($document->element('//a')->getAttribute('href'), PHP_URL_QUERY);
    throw_unless(is_string($query), RuntimeException::class);
    parse_str($query, $parameters);

    expect(data_get($parameters, 'filters.widget_key.value'))->toBe('customer-feature')
        ->and(data_get($parameters, 'filters.widget_id'))->toBeNull();

    Livewire::test(ListWidgets::class)
        ->assertTableActionExists('moveOrReplaceInLayouts')
        ->mountTableAction('moveOrReplaceInLayouts', $widget)
        ->assertTableActionDataSet(['source_widget_key' => 'customer-feature']);
});

it('describes an empty widget table with translated filter-safe copy', function (): void {
    $table = configureLayoutBuilderWidgetsTable();

    expect(__('capell-layout-builder::table.widgets_empty_heading'))->toBe('No widgets found')
        ->and(__('capell-layout-builder::table.widgets_empty_description'))
        ->toBe('No widgets are available for the current selection. Try adjusting your search or filters.')
        ->and($table->getEmptyStateHeading())->toBe('No widgets found')
        ->and($table->getEmptyStateDescription())
        ->toBe('No widgets are available for the current selection. Try adjusting your search or filters.');
});

it('keeps the widget empty state truthful when a real filter excludes an existing widget', function (): void {
    test()->actingAsAdmin();

    $widget = Widget::factory()->create([
        'key' => 'filtered-widget',
        'status' => true,
    ]);
    $table = configureLayoutBuilderWidgetsTable();
    $statusFilter = firstLayoutBuilderTableComponent(
        layoutBuilderTableComponents(array_values(configureLayoutBuilderWidgetsTable()->getFilters())),
        'status',
        SelectFilter::class,
    );
    $query = Widget::query();

    if (! $statusFilter instanceof SelectFilter) {
        throw new RuntimeException('Expected the widgets table to expose a status filter.');
    }

    $filteredQuery = $statusFilter->apply(clone $query, ['value' => false]);

    expect($query->whereKey($widget)->exists())->toBeTrue()
        ->and($filteredQuery->count())->toBe(0)
        ->and($filteredQuery->whereKey($widget)->exists())->toBeFalse()
        ->and($table->getEmptyStateDescription())
        ->toBe('No widgets are available for the current selection. Try adjusting your search or filters.');
});

it('includes soft-deleted unused widgets when the unused and trashed filters are combined', function (): void {
    test()->actingAsAdmin();

    $filters = layoutBuilderTableComponents(array_values(configureLayoutBuilderWidgetsTable()->getFilters()));
    $unusedFilter = firstLayoutBuilderTableComponent($filters, 'unused', Filter::class);
    $trashedFilter = firstLayoutBuilderTableComponent($filters, 'trashed', TrashedFilter::class);
    $unusedWidget = Widget::factory()->create(['key' => 'soft-deleted-unused-widget', 'status' => true]);
    $unusedWidget->delete();

    if (! $unusedFilter instanceof Filter || ! $trashedFilter instanceof TrashedFilter) {
        throw new RuntimeException('Expected unused and trashed widget filters.');
    }

    $unusedQuery = Widget::query();
    $unusedQuery->scopes('withLayoutsCount');
    $filteredWidgetIds = $unusedFilter->apply(
        $trashedFilter->apply(
            $unusedQuery,
            ['value' => false],
        ),
        ['isActive' => true],
    )->pluck('id');

    expect($filteredWidgetIds)->toContain($unusedWidget->getKey());
});

it('describes an empty widget asset table with translated filter-safe copy', function (): void {
    $asset = WidgetAsset::factory()->create();
    $table = WidgetAssetsTable::configure(layoutBuilderWidgetAssetsTable(WidgetAsset::query()));
    $integrityFilter = firstLayoutBuilderTableComponent($table->getFilters(), 'integrity', SelectFilter::class);
    $query = WidgetAsset::query();

    if (! $integrityFilter instanceof SelectFilter) {
        throw new RuntimeException('Expected a widget asset integrity filter.');
    }

    $filteredQuery = $integrityFilter->apply(clone $query, ['value' => 'broken_reference']);

    expect($query->whereKey($asset)->exists())->toBeTrue()
        ->and($filteredQuery->whereKey($asset)->exists())->toBeFalse()
        ->and(__('capell-layout-builder::table.widget_assets_empty_heading'))->toBe('No widget assets found')
        ->and(__('capell-layout-builder::table.widget_assets_empty_description'))
        ->toBe('No assets are available for this widget. Try adjusting your search or filters.')
        ->and($table->getEmptyStateHeading())->toBe('No widget assets found')
        ->and($table->getEmptyStateDescription())
        ->toBe('No assets are available for this widget. Try adjusting your search or filters.');
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

    $page = Page::query()->where('blueprint_id', $pageType->getKey())->sole();
    WidgetAsset::factory()->create(['pageable_type' => $page->getMorphClass(), 'pageable_id' => $page->getKey()]);
    $table = WidgetAssetsTable::configure(layoutBuilderWidgetAssetsTable(WidgetAsset::query()));
    $filter = $table->getFilter('filter');
    throw_unless($filter instanceof Filter, RuntimeException::class);
    $owner = new SchemaStateHarness;
    $schema = Schema::make($owner)->statePath('data')->components($filter->getSchemaComponents());
    $fields = $schema->getFlatFields(withHidden: true);
    $pages = $fields['pages'] ?? null;
    $types = $fields['blueprint_id'] ?? null;
    throw_unless($pages instanceof Select && $types instanceof Select, RuntimeException::class);
    $pageOptions = $pages->evaluate(new ReflectionProperty($pages, 'options')->getValue($pages), ['livewire' => $table->getLivewire()]);
    $owner->data = ['type' => ''];
    $blankAssetTypes = $types->getOptions();
    $owner->data = ['type' => 'missing'];
    $missingAssetTypes = $types->getOptions();
    $owner->data = ['type' => 'page'];
    $pageAssetTypes = $types->getOptions();

    $pageId = $page->getKey();
    throw_unless(is_int($pageId), RuntimeException::class);
    expect($pageOptions)->toHaveKey($page->getMorphClass() . ':' . $pageId)
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

    expect($table->getRecordUrl($existingAsset->fresh()))->toBeNull();

    test()->actingAs(test()->createUser());

    expect($table->getRecordUrl($existingAsset->fresh()))->toBeNull();

    test()->actingAsAdmin();

    expect($table->getRecordUrl($existingAsset->fresh()))->toBeString();

    $orphanedAsset = WidgetAsset::factory()
        ->widget($widget)
        ->create([
            'asset_type' => $page->getMorphClass(),
            'asset_id' => 999999,
        ]);

    expect($table->getRecordUrl($orphanedAsset->fresh()))->toBeNull();

    $unscopedAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($page)
        ->create();
    $partialTypeScopedAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($page)
        ->create([
            'pageable_type' => $page->getMorphClass(),
            'pageable_id' => null,
        ]);
    $partialIdScopedAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($page)
        ->create([
            'pageable_type' => null,
            'pageable_id' => $page->getKey(),
        ]);

    $filter = firstLayoutBuilderTableComponent($table->getFilters(), 'filter');
    expect($filter)->not->toBeNull();

    $filter = layoutBuilderTableObject($filter);
    $components = $filter->getSchemaComponents();
    $pagesSelect = firstLayoutBuilderTableComponent($components, 'pages', Select::class);

    expect($pagesSelect)->toBeInstanceOf(Select::class);

    $integrityFilter = firstLayoutBuilderTableComponent($table->getFilters(), 'integrity', SelectFilter::class);
    expect($integrityFilter)->toBeInstanceOf(SelectFilter::class);

    if (! $integrityFilter instanceof SelectFilter) {
        throw new RuntimeException('Expected a widget asset integrity filter.');
    }

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
    $brokenReferenceIds = $integrityFilter->apply(WidgetAsset::query(), ['value' => 'broken_reference'])->pluck('id');
    $unscopedIds = $integrityFilter->apply(WidgetAsset::query(), ['value' => 'unscoped'])->pluck('id');

    $createAction = collect($table->getHeaderActions())
        ->first(fn (mixed $action): bool => $action instanceof CreateAction);
    expect($createAction)->toBeInstanceOf(CreateAction::class);

    $relationManager = new RelationManager;
    $relationManager->ownerRecord = $widget;

    layoutBuilderWidgetAsset($createAction->process(null, [
        'data' => [
            'asset_id' => [$page->getKey(), $secondPage->getKey()],
            'asset_type' => $page->getMorphClass(),
        ],
        'livewire' => $relationManager,
    ]));

    $pageId = $page->getKey();
    throw_unless(is_int($pageId), RuntimeException::class);
    expect($pageOptions)->toHaveKey($page->getMorphClass() . ':' . $pageId)
        ->and($filteredQuery->toSql())->toContain('asset_type', 'blueprint_id', 'pageable_type', 'pageable_id')
        ->and($indicators)->toHaveKeys(['asset_type', 'blueprint_id', 'page'])
        ->and($brokenReferenceIds)->toContain($orphanedAsset->getKey())
        ->and($brokenReferenceIds)->not->toContain($existingAsset->getKey())
        ->and($unscopedIds)->toContain($unscopedAsset->getKey())
        ->and($unscopedIds)->toContain($partialTypeScopedAsset->getKey())
        ->and($unscopedIds)->toContain($partialIdScopedAsset->getKey())
        ->and($unscopedIds)->not->toContain($orphanedAsset->getKey())
        ->and(WidgetAsset::query()->where('widget_id', $widget->getKey())->count())->toBe(7);

});

it('keeps disabled widget state visible while selection and select labels distinguish usage', function (): void {
    test()->actingAsAdmin();

    $usedWidget = Widget::factory()->create(['key' => 'used-widget', 'status' => true]);
    $disabledWidget = Widget::factory()->create([
        'key' => 'disabled-widget',
        'status' => false,
    ]);

    Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $usedWidget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    $widgetSelect = WidgetSelect::make('widget_id')->withCreateForm();
    $selectionTable = WidgetSelectionTable::configure(layoutBuilderWidgetAssetsTable(Widget::query()));

    expect($widgetSelect->getOptionLabelFromRecord($usedWidget))
        ->toContain('1 layout')
        ->and($widgetSelect->getOptionLabelFromRecord($disabledWidget))
        ->toContain(__('capell-admin::generic.disabled'))
        ->toContain(__('capell-layout-builder::table.widget_usage_unused'))
        ->and($selectionTable->isRecordSelectable($usedWidget))->toBeTrue()
        ->and($selectionTable->isRecordSelectable($disabledWidget))->toBeFalse();

    expect($widgetSelect->getOptions())
        ->toHaveKey($usedWidget->getKey())
        ->not->toHaveKey($disabledWidget->getKey());

    $disabledWidget->delete();

    expect($widgetSelect->getOptionLabelFromRecord($disabledWidget))
        ->toContain(__('capell-admin::widget.unavailable'));
});

it('uses actor-scoped layout usage projections without per-widget count queries in select labels', function (): void {
    test()->actingAsAdmin();

    $usedWidget = Widget::factory()->create(['key' => 'projected-used-widget', 'status' => true]);
    $unusedWidget = Widget::factory()->create(['key' => 'projected-unused-widget', 'status' => true]);

    Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $usedWidget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    $widgetSelect = WidgetSelect::make('widget_id')->withCreateForm();
    $optionLabelsUsing = new ReflectionProperty(Select::class, 'getOptionLabelsUsing')->getValue($widgetSelect);

    if (! $optionLabelsUsing instanceof Closure) {
        throw new RuntimeException('Expected a selected widget labels callback.');
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $options = $widgetSelect->getOptions();
    $optionQueries = DB::getQueryLog();

    DB::flushQueryLog();
    $selectedLabels = $optionLabelsUsing($widgetSelect, [$usedWidget->getKey(), $unusedWidget->getKey()]);
    $selectedLabelQueries = DB::getQueryLog();

    $optionUsageQueries = layoutBuilderWidgetUsageQueries($optionQueries);
    $selectedLabelUsageQueries = layoutBuilderWidgetUsageQueries($selectedLabelQueries);

    expect($options[$usedWidget->getKey()] ?? null)->toContain('1 layout')
        ->and($options[$unusedWidget->getKey()] ?? null)->toContain(__('capell-layout-builder::table.widget_usage_unused'))
        ->and($selectedLabels[$usedWidget->getKey()] ?? null)->toContain('1 layout')
        ->and($selectedLabels[$unusedWidget->getKey()] ?? null)->toContain(__('capell-layout-builder::table.widget_usage_unused'))
        ->and($optionUsageQueries)->toHaveCount(1)
        ->and($selectedLabelUsageQueries)->toHaveCount(1)
        ->and($optionUsageQueries[0])->toContain('layout_usage')
        ->and($selectedLabelUsageQueries[0])->toContain('layout_usage');
});

it('adds layout-builder specific layout table filters columns and query relations', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero']);

    $table = LayoutsTable::configure(AdminSurfaceAssertions::table()->query(Layout::query()));
    $filters = layoutBuilderTableComponents(array_values($table->getFilters()));
    $columns = array_values($table->getColumns());
    $query = $table->getQuery();
    $bulkChangeAction = collect($table->getHeaderActions())->first(fn (mixed $action): bool => $action instanceof Action && $action->getName() === 'bulkChangeLayouts');

    $widgetFilter = firstLayoutBuilderTableComponent($filters, 'widget_key', SelectFilter::class);

    expect($widgetFilter)->not->toBeNull()
        ->and($columns)->not->toBeEmpty()
        ->and($query)->toBeInstanceOf(Builder::class)
        ->and($bulkChangeAction)->toBeInstanceOf(Action::class)
        ->and(layoutBuilderTableAction($bulkChangeAction)->getName())->toBe('bulkChangeLayouts')
        ->and($widget->exists)->toBeTrue()
        ->and(layoutBuilderTableContainsColumn($columns, ['layoutWidgets.name', 'admin.' . LayoutPreviewMetaKey::STATUS]))->toBeBool();
});

/**
 * @param  array<array-key, mixed>  $components
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
 * @return array<array-key, mixed>
 */
function layoutBuilderTableComponents(mixed $components): array
{
    if (! is_array($components)) {
        throw new RuntimeException('Expected table components to be an array.');
    }

    return $components;
}

/**
 * @param  array<array{query: string, bindings: array<mixed>, time: float|null}>  $queries
 * @return array<int, string>
 */
function layoutBuilderWidgetUsageQueries(array $queries): array
{
    $widgetsTable = DB::connection()->getQueryGrammar()->wrapTable('widgets');

    return array_values(array_map(
        static fn (array $query): string => $query['query'],
        array_filter(
            $queries,
            static fn (array $query): bool => str_contains($query['query'], 'from ' . $widgetsTable)
                && str_contains($query['query'], 'layouts_count'),
        ),
    ));
}

/**
 * @param  array<int, mixed>  $columns
 * @param  array<int, string>  $names
 */
function layoutBuilderTableContainsColumn(array $columns, array $names): bool
{
    return array_any($columns, fn ($column): bool => $column instanceof TextColumn && in_array($column->getName(), $names, true));
}

function layoutBuilderWidgetAssetsTable(Builder $query): Table
{
    return Table::make(layoutBuilderWidgetAssetsTableLivewire($query))->query($query);
}

function configureLayoutBuilderWidgetsTable(): Table
{
    $livewire = Mockery::mock(HasTable::class);
    $table = Table::make($livewire)->query(Widget::query());

    $livewire->shouldReceive('makeFilamentTranslatableContentDriver')->andReturn(null)->byDefault();
    $livewire->shouldReceive('getTable')->andReturn($table)->byDefault();

    return WidgetsTable::configure($table);
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

function layoutBuilderTableAction(mixed $value): Action
{
    throw_unless($value instanceof Action, RuntimeException::class, 'Expected a Filament table action.');

    return $value;
}

function layoutBuilderWidgetAsset(mixed $value): WidgetAsset
{
    throw_unless($value instanceof WidgetAsset, RuntimeException::class, 'Expected a widget asset.');

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
