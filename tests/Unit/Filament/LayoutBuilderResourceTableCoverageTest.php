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
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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

it('exposes block resource metadata search details and soft-deleted query scope', function (): void {
    $language = createEnglishLayoutBuilderLanguage();
    $block = Widget::factory()->create(['name' => 'Hero Widget', 'key' => 'hero-block']);
    $block->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Promo Hero',
    ]);
    $block->load('translation');

    expect(WidgetResource::getModel())->toBe(Widget::class)
        ->and(WidgetResource::getResourceType())->toBe(ConfiguratorTypeEnum::Widget)
        ->and(WidgetResource::getNavigationLabel())->toBe(__('capell-layout-builder::navigation.blocks'))
        ->and(WidgetResource::getNavigationGroup())->toBe(__('capell-admin::navigation.group_layouts'))
        ->and(WidgetResource::getSlug())->toBe('widgets')
        ->and(WidgetResource::getModelLabel())->toBe(__('capell-layout-builder::navigation.block'))
        ->and(WidgetResource::getPluralModelLabel())->toBe(__('capell-layout-builder::navigation.blocks'))
        ->and(WidgetResource::shouldRegisterNavigation())->toBeTrue()
        ->and(WidgetResource::getGloballySearchableAttributes())->toContain('translations.title')
        ->and(WidgetResource::getGlobalSearchResultDetails($block))->toBe([
            __('capell-admin::generic.title') => 'Promo Hero',
        ])
        ->and(LayoutResource::getModel())->toBe(Layout::class);

    $block->delete();

    expect(WidgetResource::getEloquentQuery()->whereKey($block->getKey())->exists())->toBeTrue()
        ->and(WidgetResource::getRelations())->toHaveCount(1)
        ->and(WidgetResource::getPages())->toHaveKeys(['index', 'edit', 'create']);
});

it('builds block table columns filters and search query branches', function (): void {
    $language = createEnglishLayoutBuilderLanguage();
    $block = Widget::factory()->create([
        'component' => 'hero-card',
        'component_item' => 'hero-card-item',
        'view_file' => 'blocks.hero-card',
    ]);
    $block->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Hero',
        'content' => 'Needle content',
    ]);

    $columns = invokeLayoutBuilderTableMethod(WidgetsTable::class, 'getTableColumns');
    $filters = invokeLayoutBuilderTableMethod(WidgetsTable::class, 'getTableFilters');

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
        ->and($contentSearchQuery->whereKey($block->getKey())->exists())->toBeTrue()
        ->and($componentSearchQuery->whereKey($block->getKey())->exists())->toBeTrue()
        ->and($languageFilter)->not->toBeNull();
});

it('covers block asset table lookup and type helper branches', function (): void {
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

it('adds layout-builder specific layout table filters columns and query relations', function (): void {
    $block = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero']);

    $filters = invokeLayoutBuilderTableMethod(LayoutsTable::class, 'getTableFilters');
    $columns = invokeLayoutBuilderTableMethod(LayoutsTable::class, 'getTableColumns');
    $query = invokeLayoutBuilderTableMethod(
        LayoutsTable::class,
        'getTableQueryModifier',
        Layout::query(),
    );

    $blockFilter = firstLayoutBuilderTableComponent($filters, 'widget_key', SelectFilter::class);

    expect($blockFilter)->not->toBeNull()
        ->and($columns)->not->toBeEmpty()
        ->and($query)->toBeInstanceOf(Builder::class)
        ->and($block->exists)->toBeTrue()
        ->and(layoutBuilderTableContainsColumn($columns, ['layoutBlocks.name', 'admin.' . LayoutPreviewMetaKey::STATUS]))->toBeBool();
});

/**
 * @param  array<int, mixed>  $components
 * @param  class-string|null  $expectedClass
 */
function firstLayoutBuilderTableComponent(array $components, string $name, ?string $expectedClass = null): ?object
{
    foreach ($components as $component) {
        if (! is_object($component) || ! method_exists($component, 'getName')) {
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
