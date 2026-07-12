<?php

declare(strict_types=1);

use Capell\BlockLibrary\Data\BlockAccessibilityContractData;
use Capell\BlockLibrary\Data\BlockContentContractData;
use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\PublicBlockPresentationData;
use Capell\LayoutBuilder\Actions\WidgetContractValidatorAction;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;

it('reports missing required content and over-limit items', function (): void {
    $definition = new BlockDefinitionData(
        key: 'cards',
        label: 'Cards',
        description: 'Card widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.cards',
        contentContract: new BlockContentContractData(
            requiredFields: ['heading'],
            maxItems: 2,
            requiresCta: true,
        ),
    );

    $diagnostics = WidgetContractValidatorAction::run(
        definition: $definition,
        presentation: new PublicBlockPresentationData(showCta: true),
        payload: [
            'heading' => '',
            'items' => [['title' => 'One'], ['title' => 'Two'], ['title' => 'Three']],
        ],
        containerKey: 'main',
        widgetIndex: 0,
    );

    expect($diagnostics)->toHaveCount(3)
        ->and($diagnostics[0]->severity)->toBe(LayoutDiagnosticSeverity::Blocking)
        ->and($diagnostics[0]->code)->toBe('missing_required_widget_field')
        ->and($diagnostics[1]->code)->toBe('too_many_widget_items')
        ->and($diagnostics[2]->code)->toBe('empty_widget_cta');
});

it('reports accessibility contract violations for media alt text and cta labels', function (): void {
    $definition = new BlockDefinitionData(
        key: 'media-cta',
        label: 'Media CTA',
        description: 'Media CTA widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.media-cta',
        contentContract: new BlockContentContractData(
            requiresCta: true,
            accessibilityRules: ['requires_cta_accessible_name'],
        ),
        accessibilityContract: new BlockAccessibilityContractData(
            contrastPairs: ['foreground/surface'],
            mediaRules: ['requires_image_alt'],
        ),
    );

    $diagnostics = WidgetContractValidatorAction::run(
        definition: $definition,
        presentation: new PublicBlockPresentationData(showCta: true),
        payload: [
            'cta' => ['url' => '#action'],
            'media' => ['ratio' => '16:9'],
        ],
        containerKey: 'main',
        widgetIndex: 0,
    );

    expect(collect($diagnostics)->pluck('code')->all())
        ->toContain('missing_widget_cta_label')
        ->toContain('missing_widget_image_alt')
        ->toContain('unverified_widget_contrast_pairs');
});

it('reports missing alt text for asset-backed media items', function (): void {
    $definition = new BlockDefinitionData(
        key: 'asset-media',
        label: 'Asset media',
        description: 'Asset media widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.asset-media',
        accessibilityContract: new BlockAccessibilityContractData(
            mediaRules: ['requires_image_alt'],
        ),
    );

    $missingAltDiagnostics = WidgetContractValidatorAction::run(
        definition: $definition,
        presentation: new PublicBlockPresentationData,
        payload: [
            'items' => [
                [
                    'asset_id' => 123,
                    'asset_type' => 'media',
                    'meta' => [],
                ],
            ],
        ],
    );

    $decorativeDiagnostics = WidgetContractValidatorAction::run(
        definition: $definition,
        presentation: new PublicBlockPresentationData,
        payload: [
            'items' => [
                [
                    'asset_id' => 123,
                    'asset_type' => 'media',
                    'meta' => ['decorative' => true],
                ],
            ],
        ],
    );

    $altTextDiagnostics = WidgetContractValidatorAction::run(
        definition: $definition,
        presentation: new PublicBlockPresentationData,
        payload: [
            'items' => [
                [
                    'asset_id' => 123,
                    'asset_type' => 'media',
                    'meta' => ['alt_text' => 'Product detail'],
                ],
            ],
        ],
    );

    expect(collect($missingAltDiagnostics)->pluck('code')->all())
        ->toContain('missing_widget_image_alt')
        ->and(collect($decorativeDiagnostics)->pluck('code')->all())
        ->not->toContain('missing_widget_image_alt')
        ->and(collect($altTextDiagnostics)->pluck('code')->all())
        ->not->toContain('missing_widget_image_alt');
});
