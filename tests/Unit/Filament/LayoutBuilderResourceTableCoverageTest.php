<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Blocks\BlockResource;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Tables\BlockAssetsTable;
use Capell\LayoutBuilder\Filament\Resources\Blocks\Tables\BlocksTable;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Tables\LayoutsTable;
use Capell\LayoutBuilder\Models\Block;
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

it('exposes block resource metadata search details and soft-deleted query scope', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    $block = Block::factory()->create(['name' => 'Hero Block', 'key' => 'hero-block']);
    $block->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Promo Hero',
    ]);
    $block->load('translation');

    expect(BlockResource::getModel())->toBe(Block::class)
        ->and(BlockResource::getResourceType())->toBe(ConfiguratorTypeEnum::Block)
        ->and(BlockResource::getNavigationLabel())->toBe(__('capell-layout-builder::navigation.blocks'))
        ->and(BlockResource::getNavigationGroup())->toBe(__('capell-admin::navigation.group_layouts'))
        ->and(BlockResource::getPluralModelLabel())->toBe(__('capell-layout-builder::navigation.blocks'))
        ->and(BlockResource::shouldRegisterNavigation())->toBeTrue()
        ->and(BlockResource::getGloballySearchableAttributes())->toContain('translations.title')
        ->and(BlockResource::getGlobalSearchResultDetails($block))->toBe([
            __('capell-admin::generic.title') => 'Promo Hero',
        ])
        ->and(LayoutResource::getModel())->toBe(Layout::class);

    $block->delete();

    expect(BlockResource::getEloquentQuery()->whereKey($block->getKey())->exists())->toBeTrue()
        ->and(BlockResource::getRelations())->toHaveCount(1)
        ->and(BlockResource::getPages())->toHaveKeys(['index', 'edit', 'create']);
});

it('builds block table columns filters and search query branches', function (): void {
    $language = Language::factory()->create(['name' => 'English', 'code' => 'en']);
    $block = Block::factory()->create([
        'component' => 'hero-card',
        'component_item' => 'hero-card-item',
        'view_file' => 'blocks.hero-card',
    ]);
    $block->translations()->create([
        'language_id' => $language->getKey(),
        'title' => 'Hero',
        'content' => 'Needle content',
    ]);

    $columns = invokeLayoutBuilderTableMethod(BlocksTable::class, 'getTableColumns');
    $filters = invokeLayoutBuilderTableMethod(BlocksTable::class, 'getTableFilters');

    $contentSearchQuery = invokeLayoutBuilderTableMethod(
        BlocksTable::class,
        'applyContentSearch',
        Block::query(),
        'Needle content',
    );
    $componentSearchQuery = invokeLayoutBuilderTableMethod(
        BlocksTable::class,
        'applyComponentSearch',
        Block::query(),
        'hero-card',
    );

    $languageFilter = collect($filters)
        ->first(fn (mixed $filter): bool => method_exists($filter, 'getName') && $filter->getName() === 'filter');

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

    $lookupKey = invokeLayoutBuilderTableMethod(BlockAssetsTable::class, 'buildLookupKey', 'page', 123);
    $blankAssetTypes = invokeLayoutBuilderTableMethod(BlockAssetsTable::class, 'getAssetTypes', '');
    $missingAssetTypes = invokeLayoutBuilderTableMethod(BlockAssetsTable::class, 'getAssetTypes', 'missing');
    $pageAssetTypes = invokeLayoutBuilderTableMethod(BlockAssetsTable::class, 'getAssetTypes', 'page');

    expect($lookupKey)->toBe('page:123')
        ->and($blankAssetTypes)->toBe([])
        ->and($missingAssetTypes)->toBe([])
        ->and($pageAssetTypes)->toHaveKey($pageType->getKey())
        ->and($pageType->exists)->toBeTrue();
});

it('adds layout-builder specific layout table filters columns and query relations', function (): void {
    $block = Block::factory()->create(['key' => 'hero', 'name' => 'Hero']);

    $filters = invokeLayoutBuilderTableMethod(LayoutsTable::class, 'getTableFilters');
    $columns = invokeLayoutBuilderTableMethod(LayoutsTable::class, 'getTableColumns');
    $query = invokeLayoutBuilderTableMethod(
        LayoutsTable::class,
        'getTableQueryModifier',
        Layout::query(),
    );

    $blockFilter = collect($filters)
        ->first(fn (mixed $filter): bool => $filter instanceof SelectFilter && $filter->getName() === 'block_key');

    expect($blockFilter)->not->toBeNull()
        ->and($columns)->not->toBeEmpty()
        ->and($query)->toBeInstanceOf(Builder::class)
        ->and($block->exists)->toBeTrue()
        ->and(collect($columns)->contains(
            fn (mixed $column): bool => $column instanceof TextColumn && in_array($column->getName(), ['layoutBlocks.name', 'admin.' . LayoutPreviewMetaKey::STATUS], true),
        ))->toBeBool();
});
