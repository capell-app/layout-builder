<?php

declare(strict_types=1);

use Capell\ContentBlocks\Data\BlockCompatibilityData;
use Capell\ContentBlocks\Data\BlockContentContractData;
use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\BlockVariantData;
use Capell\ContentBlocks\Data\BlockVariantKey;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\AnalyzeLayoutHealthAction;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Listeners\LayoutSavingListener;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewSignature;

it('reports duplicate anchors and over-limit cards without exposing diagnostics publicly', function (): void {
    Block::factory()->create(['key' => 'known']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'blocks' => [
                    [
                        'block_key' => 'known',
                        'meta' => [
                            'block_settings' => ['anchor_id' => 'Feature Grid'],
                        ],
                    ],
                    [
                        'block_key' => 'known',
                        'meta' => [
                            'block_settings' => ['anchor_id' => 'Feature Grid'],
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
        ->toContain('duplicate_block_anchor')
        ->toContain('too_many_block_cards');
});

it('includes block contract and theme compatibility warnings in layout health', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'known',
        label: 'Known',
        description: 'Known block.',
        category: 'marketing',
        view: 'vendor-package::blocks.known',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
        ],
        contentContract: new BlockContentContractData(
            requiredFields: ['heading'],
        ),
        compatibility: new BlockCompatibilityData(
            themeKeys: ['foundation'],
        ),
    ));

    Block::factory()->create(['key' => 'known']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'blocks' => [
                    [
                        'block_key' => 'known',
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
        ->toContain('unsupported_block_variant')
        ->toContain('missing_required_block_field');

    expect(collect($diagnostics)->firstWhere('code', 'unsupported_block_variant')->message)
        ->toContain('Default');
});

it('supports legacy shorthand block keys in layout health analysis', function (): void {
    Block::factory()->create(['key' => 'breadcrumbs']);

    $state = new LayoutBuilderStateData(
        containers: [
            'main' => [
                'blocks' => ['breadcrumbs'],
            ],
        ],
        assets: [],
        originalAssets: [],
        selectedRecords: [],
    );

    $diagnostics = AnalyzeLayoutHealthAction::run($state);

    expect(collect($diagnostics)->pluck('code')->all())->not->toContain('unknown_block');
});

it('persists legacy shorthand block keys when syncing layout blocks', function (): void {
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'blocks' => [
                    'breadcrumbs',
                    ['block_key' => 'page-content'],
                    ['block_key' => 'breadcrumbs'],
                ],
            ],
        ],
    ]);

    (new LayoutSavingListener)($layout);

    expect($layout->getAttribute('blocks'))->toBe(['breadcrumbs', 'page-content']);
});

it('includes legacy shorthand block keys in preview signatures', function (): void {
    Block::factory()->create(['key' => 'breadcrumbs']);

    $layout = Layout::factory()->make([
        'key' => 'default',
        'containers' => [
            'main' => [
                'blocks' => ['breadcrumbs'],
            ],
        ],
    ]);

    $payload = resolve(LayoutPreviewSignature::class)->payload($layout);

    expect($payload['containers'][0]['blocks'][0]['key'])->toBe('breadcrumbs');
});
