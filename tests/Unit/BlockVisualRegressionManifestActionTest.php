<?php

declare(strict_types=1);

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\BlockVariantData;
use Capell\ContentBlocks\Data\BlockVariantKey;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\LayoutBuilder\Actions\BuildBlockVisualRegressionManifestAction;

it('builds deterministic block visual regression fixture entries', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Hero',
        description: 'Hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.hero',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::blocks.variants.split_media'),
        ],
    ));

    $firstRun = BuildBlockVisualRegressionManifestAction::run(block: 'marketing.hero', variant: 'split-media', theme: 'foundation');
    $secondRun = BuildBlockVisualRegressionManifestAction::run(block: 'marketing.hero', variant: 'split-media', theme: 'foundation');

    expect($firstRun)->toBe($secondRun)
        ->and($firstRun)->toHaveCount(18)
        ->and($firstRun[0]['artifact'])->toBe('blocks/marketing-hero/foundation/split-media-default-mobile.png')
        ->and(collect($firstRun)->pluck('scenario')->unique()->values()->all())->toBe([
            'default',
            'long-content',
            'max-cards',
            'dark-surface',
            'image-surface-missing-alt',
            'cta-hidden',
        ])
        ->and(collect($firstRun)->firstWhere('scenario', 'image-surface-missing-alt')['fixture']['media'])->not->toHaveKey('alt')
        ->and(collect($firstRun)->firstWhere('scenario', 'cta-hidden')['fixture']['cta'])->toBe([])
        ->and(json_encode($firstRun, JSON_THROW_ON_ERROR))->not->toContain('signed')
        ->and(json_encode($firstRun, JSON_THROW_ON_ERROR))->not->toContain('http')
        ->and(json_encode($firstRun, JSON_THROW_ON_ERROR))->not->toContain((string) now()->year);
});

it('limits visual regression entries for ci runs', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'marketing.cards',
        label: 'Cards',
        description: 'Card block.',
        category: 'marketing',
        view: 'vendor-package::blocks.cards',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
        ],
    ));

    expect(BuildBlockVisualRegressionManifestAction::run(limit: 2))->toHaveCount(2);
});
