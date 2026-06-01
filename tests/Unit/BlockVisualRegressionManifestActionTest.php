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
    $missingAltScenario = capell_test_array(collect($firstRun)->firstWhere('scenario', 'image-surface-missing-alt'));
    $missingAltFixture = capell_test_array($missingAltScenario['fixture'] ?? null);
    $ctaHiddenScenario = capell_test_array(collect($firstRun)->firstWhere('scenario', 'cta-hidden'));
    $ctaHiddenFixture = capell_test_array($ctaHiddenScenario['fixture'] ?? null);

    expect($firstRun)->toBe($secondRun)
        ->and($firstRun)->toHaveCount(18)
        ->and($firstRun[0]['artifact'])->toBe('widgets/marketing-hero/foundation/split-media-default-mobile.png')
        ->and(collect($firstRun)->pluck('scenario')->unique()->values()->all())->toBe([
            'default',
            'long-content',
            'max-cards',
            'dark-surface',
            'image-surface-missing-alt',
            'cta-hidden',
        ])
        ->and(capell_test_array($missingAltFixture['media'] ?? null))->not->toHaveKey('alt')
        ->and($ctaHiddenFixture['cta'] ?? null)->toBe([])
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

it('emits visual regression manifest entries from the console command', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'marketing.command',
        label: 'Command',
        description: 'Command block.',
        category: 'marketing',
        view: 'vendor-package::blocks.command',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
        ],
    ));

    test()->artisan('capell:layout-builder-block-visual-regression', [
        'mode' => 'capture',
        '--block' => 'marketing.command',
        '--variant' => 'default',
        '--theme' => 'foundation',
        '--changed' => true,
        '--concurrency' => 0,
        '--ci-limit' => 1,
    ])
        ->assertSuccessful();
});

it('rejects unsupported visual regression command modes', function (): void {
    test()->artisan('capell:layout-builder-block-visual-regression', [
        'mode' => 'diff',
    ])
        ->assertFailed()
        ->expectsOutputToContain('Mode must be capture or assert.');
});
