<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Actions\InstallLayoutBuilderBlockCatalogAction;
use Capell\LayoutBuilder\Data\BlockDefinitionData;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Models\Block;

it('installs the full block catalog with normalized enum metadata and translations', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    Block::query()->delete();

    InstallLayoutBuilderBlockCatalogAction::run(collect([$language]), extraBlocks: true);

    $defaultDefinitions = BlockDefinitionData::defaultCatalog();
    $extraDefinitions = BlockDefinitionData::extraCatalog();

    expect($defaultDefinitions)->not->toBeEmpty()
        ->and($extraDefinitions)->not->toBeEmpty()
        ->and(collect($extraDefinitions)->firstWhere('key', 'block-navigation')?->hasNavigation())->toBeTrue()
        ->and(Block::query()->count())->toBe(count($defaultDefinitions) + count($extraDefinitions));

    $announcementBlock = Block::query()->firstWhere('key', 'announcement-bar');
    $testimonialBlock = Block::query()->firstWhere('key', 'asset-testimonials');
    $navigationTabsBlock = Block::query()->firstWhere('key', 'block-navigation-tabs');

    expect($announcementBlock?->component)->toBe(BlockComponentEnum::AnnouncementBar->value)
        ->and($announcementBlock?->translations()->where('language_id', $language->getKey())->exists())->toBeTrue()
        ->and($testimonialBlock?->meta['background_color'])->toBe('gray')
        ->and($testimonialBlock?->component)->toBe(BlockComponentEnum::AssetTestimonials->value)
        ->and($navigationTabsBlock?->component)->toBe(BlockComponentEnum::NavigationTabs->value);
});

it('preserves existing component metadata while backfilling missing safe defaults', function (): void {
    $language = Language::factory()->create(['code' => 'en']);
    Block::query()->delete();

    $existingBlock = Block::factory()->create([
        'key' => 'announcement-bar',
    ]);
    $existingBlock->forceFill([
        'component' => 'custom-component',
        'meta' => [
            'component' => 'custom-component',
        ],
    ])->save();

    InstallLayoutBuilderBlockCatalogAction::run(collect([$language]), extraBlocks: false);

    $block = Block::query()->firstWhere('key', 'announcement-bar');

    expect($block?->component)->toBe('custom-component')
        ->and($block?->meta['container'])->toBe('full')
        ->and($block?->meta['padding'])->toBe(['sm']);
});
