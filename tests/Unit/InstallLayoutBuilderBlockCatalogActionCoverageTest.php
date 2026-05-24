<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Actions\InstallLayoutBuilderBlockCatalogAction;
use Capell\LayoutBuilder\Data\BlockDefinitionData;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Models\Widget;

it('installs the full block catalog with normalized enum metadata and translations', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    Widget::query()->delete();

    InstallLayoutBuilderBlockCatalogAction::run(collect([$language]), extraBlocks: true);

    $defaultDefinitions = BlockDefinitionData::defaultCatalog();
    $extraDefinitions = BlockDefinitionData::extraCatalog();

    expect($defaultDefinitions)->not->toBeEmpty()
        ->and($extraDefinitions)->not->toBeEmpty()
        ->and(collect($extraDefinitions)->firstWhere('key', 'block-navigation')?->hasNavigation())->toBeTrue()
        ->and(Widget::query()->count())->toBe(count($defaultDefinitions) + count($extraDefinitions));

    $announcementBlock = Widget::query()->firstWhere('key', 'announcement-bar');
    $testimonialBlock = Widget::query()->firstWhere('key', 'asset-testimonials');
    $navigationTabsBlock = Widget::query()->firstWhere('key', 'block-navigation-tabs');

    expect($announcementBlock?->component)->toBe(BlockComponentEnum::AnnouncementBar->value)
        ->and($announcementBlock?->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and($testimonialBlock?->meta['background_color'])->toBe('gray')
        ->and($testimonialBlock?->component)->toBe(BlockComponentEnum::AssetTestimonials->value)
        ->and($navigationTabsBlock?->component)->toBe(BlockComponentEnum::NavigationTabs->value);
});

it('preserves existing component metadata while backfilling missing safe defaults', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    Widget::query()->delete();

    $existingBlock = Widget::factory()->create([
        'key' => 'announcement-bar',
    ]);
    $existingBlock->forceFill([
        'component' => 'custom-component',
        'meta' => [
            'component' => 'custom-component',
        ],
    ])->save();

    InstallLayoutBuilderBlockCatalogAction::run(collect([$language]), extraBlocks: false);

    $block = Widget::query()->firstWhere('key', 'announcement-bar');

    expect($block?->component)->toBe('custom-component')
        ->and($block?->meta['container'])->toBe('full')
        ->and($block?->meta['padding'])->toBe(['sm']);
});
