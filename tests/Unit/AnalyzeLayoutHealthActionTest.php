<?php

declare(strict_types=1);

use Capell\BlockLibrary\Data\BlockCompatibilityData;
use Capell\BlockLibrary\Data\BlockContentContractData;
use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockVariantData;
use Capell\BlockLibrary\Data\BlockVariantKey;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\AnalyzeLayoutHealthAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewSignature;

it('reports duplicate anchors and over-limit cards without exposing diagnostics publicly', function (): void {
    Widget::factory()->create(['key' => 'known']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => 'known',
                        'meta' => [
                            'widget_settings' => ['anchor_id' => 'Feature Grid'],
                        ],
                    ],
                    [
                        'widget_key' => 'known',
                        'meta' => [
                            'widget_settings' => ['anchor_id' => 'Feature Grid'],
                        ],
                    ],
                ],
            ],
        ],
        assets: [
            'main' => [
                [],
                [
                    ['asset_id' => 1],
                    ['asset_id' => 2],
                    ['asset_id' => 3],
                    ['asset_id' => 4],
                    ['asset_id' => 5],
                    ['asset_id' => 6],
                    ['asset_id' => 7],
                ],
            ],
        ],
        originalAssets: [],
        selectedRecords: [],
    );

    $diagnostics = AnalyzeLayoutHealthAction::run($state);

    expect(collect($diagnostics)->pluck('code')->all())
        ->toContain('duplicate_widget_anchor')
        ->toContain('too_many_widget_cards');
});

it('includes widget contract and theme compatibility warnings in layout health', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'known',
        label: 'Known',
        description: 'Known widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.known',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
        ],
        contentContract: new BlockContentContractData(
            requiredFields: ['heading'],
        ),
        compatibility: new BlockCompatibilityData(
            themeKeys: ['foundation'],
        ),
    ));

    Widget::factory()->create(['key' => 'known']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => 'known',
                        'meta' => [
                            'content' => ['heading' => ''],
                        ],
                    ],
                ],
            ],
        ],
        assets: [],
        originalAssets: [],
        selectedRecords: [],
    );

    $diagnostics = AnalyzeLayoutHealthAction::run($state, 'unsupported-theme');

    expect(collect($diagnostics)->pluck('code')->all())
        ->toContain('unsupported_widget_variant')
        ->toContain('missing_required_widget_field');

    $unsupportedWidgetVariantDiagnostic = capell_test_instance(
        collect($diagnostics)->firstWhere('code', 'unsupported_widget_variant'),
        LayoutDiagnosticData::class,
    );

    expect($unsupportedWidgetVariantDiagnostic->message)->toContain('Default');
});

it('supports legacy shorthand widget keys in layout health analysis', function (): void {
    Widget::factory()->create(['key' => 'breadcrumbs']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'widgets' => ['breadcrumbs'],
            ],
        ],
        assets: [],
        originalAssets: [],
        selectedRecords: [],
    );

    $diagnostics = AnalyzeLayoutHealthAction::run($state);

    expect(collect($diagnostics)->pluck('code')->all())->not->toContain('unknown_widget');
});

it('includes legacy shorthand widget keys in preview signatures', function (): void {
    Widget::factory()->create(['key' => 'breadcrumbs']);

    $layout = Layout::factory()->make([
        'key' => 'default',
        'containers' => [
            'main' => [
                'widgets' => ['breadcrumbs'],
            ],
        ],
    ]);

    $payload = resolve(LayoutPreviewSignature::class)->payload($layout);

    expect($payload['containers'][0]['widgets'][0]['key'])->toBe('breadcrumbs');
});
