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

    $announcementBlock = capell_test_instance(Widget::query()->firstWhere('key', 'announcement-bar'), Widget::class);
    $testimonialBlock = capell_test_instance(Widget::query()->firstWhere('key', 'asset-testimonials'), Widget::class);
    $testimonialBlockMeta = capell_test_array($testimonialBlock->meta);
    $navigationTabsBlock = capell_test_instance(Widget::query()->firstWhere('key', 'block-navigation-tabs'), Widget::class);

    expect($announcementBlock->component)->toBe(BlockComponentEnum::AnnouncementBar->value)
        ->and($announcementBlock->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and($testimonialBlockMeta['background_color'] ?? null)->toBe('gray')
        ->and($testimonialBlock->component)->toBe(BlockComponentEnum::AssetTestimonials->value)
        ->and($navigationTabsBlock->component)->toBe(BlockComponentEnum::NavigationTabs->value);
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

    $block = capell_test_instance(Widget::query()->firstWhere('key', 'announcement-bar'), Widget::class);
    $blockMeta = capell_test_array($block->meta);

    expect($block->component)->toBe('custom-component')
        ->and($blockMeta['container'] ?? null)->toBe('full')
        ->and($blockMeta['padding'] ?? null)->toBe(['sm']);
});
