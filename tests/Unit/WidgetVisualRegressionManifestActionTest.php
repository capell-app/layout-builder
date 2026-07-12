<?php

declare(strict_types=1);

use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockVariantData;
use Capell\BlockLibrary\Data\BlockVariantKey;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\LayoutBuilder\Actions\BuildWidgetVisualRegressionManifestAction;

it('builds deterministic widget visual regression fixture entries', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Hero',
        description: 'Hero widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.hero',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::widgets.variants.split_media'),
        ],
    ));

    $firstRun = BuildWidgetVisualRegressionManifestAction::run(widget: 'marketing.hero', variant: 'split-media', theme: 'foundation');
    $secondRun = BuildWidgetVisualRegressionManifestAction::run(widget: 'marketing.hero', variant: 'split-media', theme: 'foundation');
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
        description: 'Card widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.cards',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
        ],
    ));

    expect(BuildWidgetVisualRegressionManifestAction::run(limit: 2))->toHaveCount(2);
});

it('emits visual regression manifest entries from the console command', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'marketing.command',
        label: 'Command',
        description: 'Command widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.command',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
        ],
    ));

    test()->artisan('capell:layout-builder-widget-visual-regression', [
        'mode' => 'capture',
        '--widget' => 'marketing.command',
        '--variant' => 'default',
        '--theme' => 'foundation',
        '--changed' => true,
        '--concurrency' => 0,
        '--ci-limit' => 1,
    ])
        ->assertSuccessful();
});

it('rejects unsupported visual regression command modes', function (): void {
    test()->artisan('capell:layout-builder-widget-visual-regression', [
        'mode' => 'diff',
    ])
        ->assertFailed()
        ->expectsOutputToContain('Mode must be capture or assert.');
});
