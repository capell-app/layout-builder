<?php

declare(strict_types=1);

use Capell\ContentBlocks\Data\BlockAccessibilityContractData;
use Capell\ContentBlocks\Data\BlockContentContractData;
use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\PublicBlockPresentationData;
use Capell\LayoutBuilder\Actions\BlockContractValidatorAction;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;

it('reports missing required content and over-limit items', function (): void {
    $definition = new BlockDefinitionData(
        key: 'cards',
        label: 'Cards',
        description: 'Card block.',
        category: 'marketing',
        view: 'vendor-package::blocks.cards',
        contentContract: new BlockContentContractData(
            requiredFields: ['heading'],
            maxItems: 2,
            requiresCta: true,
        ),
    );

    $diagnostics = BlockContractValidatorAction::run(
        definition: $definition,
        presentation: new PublicBlockPresentationData(showCta: true),
        payload: [
            'heading' => '',
            'items' => [['title' => 'One'], ['title' => 'Two'], ['title' => 'Three']],
        ],
        containerKey: 'main',
        elementIndex: 0,
    );

    expect($diagnostics)->toHaveCount(3)
        ->and($diagnostics[0]->severity)->toBe(LayoutDiagnosticSeverity::Blocking)
        ->and($diagnostics[0]->code)->toBe('missing_required_block_field')
        ->and($diagnostics[1]->code)->toBe('too_many_block_items')
        ->and($diagnostics[2]->code)->toBe('empty_block_cta');
});

it('reports accessibility contract violations for media alt text and cta labels', function (): void {
    $definition = new BlockDefinitionData(
        key: 'media-cta',
        label: 'Media CTA',
        description: 'Media CTA block.',
        category: 'marketing',
        view: 'vendor-package::blocks.media-cta',
        contentContract: new BlockContentContractData(
            requiresCta: true,
            accessibilityRules: ['requires_cta_accessible_name'],
        ),
        accessibilityContract: new BlockAccessibilityContractData(
            contrastPairs: ['foreground/surface'],
            mediaRules: ['requires_image_alt'],
        ),
    );

    $diagnostics = BlockContractValidatorAction::run(
        definition: $definition,
        presentation: new PublicBlockPresentationData(showCta: true),
        payload: [
            'cta' => ['url' => '#action'],
            'media' => ['ratio' => '16:9'],
        ],
        containerKey: 'main',
        elementIndex: 0,
    );

    expect(collect($diagnostics)->pluck('code')->all())
        ->toContain('missing_block_cta_label')
        ->toContain('missing_block_image_alt')
        ->toContain('unverified_block_contrast_pairs');
});

it('reports missing alt text for asset-backed media items', function (): void {
    $definition = new BlockDefinitionData(
        key: 'asset-media',
        label: 'Asset media',
        description: 'Asset media block.',
        category: 'marketing',
        view: 'vendor-package::blocks.asset-media',
        accessibilityContract: new BlockAccessibilityContractData(
            mediaRules: ['requires_image_alt'],
        ),
    );

    $missingAltDiagnostics = BlockContractValidatorAction::run(
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

    $decorativeDiagnostics = BlockContractValidatorAction::run(
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

    $altTextDiagnostics = BlockContractValidatorAction::run(
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
        ->toContain('missing_block_image_alt')
        ->and(collect($decorativeDiagnostics)->pluck('code')->all())
        ->not->toContain('missing_block_image_alt')
        ->and(collect($altTextDiagnostics)->pluck('code')->all())
        ->not->toContain('missing_block_image_alt');
});
