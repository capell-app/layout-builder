<?php

declare(strict_types=1);

use Capell\ContentBlocks\Data\BlockCompatibilityData;
use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\BlockVariantData;
use Capell\ContentBlocks\Data\BlockVariantKey;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\LayoutBuilder\Actions\ResolveBlockPresentationDataAction;
use Capell\LayoutBuilder\Models\Element;

it('projects only allowlisted block presentation keys from element meta', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'hero',
        label: 'Hero',
        description: 'Hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.hero',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::blocks.variants.split_media'),
        ],
    ));

    $element = Element::factory()->create([
        'key' => 'hero',
        'meta' => [
            'block_variant' => 'split-media',
            'block_settings' => [
                'spacing' => 'spacious',
                'background' => 'dark',
                'media_position' => 'left',
                'cards_per_row' => 4,
                'show_cta' => false,
                'heading_width' => 'wide',
                'anchor_id' => 'Hero Section',
                'signed_url' => 'https://example.test/admin/signed',
            ],
            'admin_schema' => ['field' => 'private'],
        ],
    ]);

    $payload = ResolveBlockPresentationDataAction::run($element)->toArray();

    expect($payload)
        ->toBe([
            'variant' => 'split-media',
            'spacing' => 'spacious',
            'background' => 'dark',
            'mediaPosition' => 'left',
            'cardsPerRow' => 4,
            'showCta' => false,
            'headingWidth' => 'wide',
            'anchorId' => 'hero-section',
        ])
        ->and($payload)->not->toHaveKeys(['signed_url', 'admin_schema', 'block_settings']);
});

it('falls back to a safe variant when the variant is unknown or theme unsupported', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'proof',
        label: 'Proof',
        description: 'Proof block.',
        category: 'marketing',
        view: 'vendor-package::blocks.proof',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
            new BlockVariantData(BlockVariantKey::from('bento'), 'vendor-package::blocks.variants.bento'),
        ],
        defaultVariant: BlockVariantKey::from('default'),
        compatibility: new BlockCompatibilityData(themeKeys: ['foundation']),
    ));

    $element = Element::factory()->create([
        'key' => 'proof',
        'meta' => [
            'block_variant' => 'bento',
            'block_settings' => ['spacing' => 'spacious'],
        ],
    ]);

    $payload = ResolveBlockPresentationDataAction::run($element, 'unsupported-theme')->toArray();

    expect($payload['variant'])->toBe('default')
        ->and($payload['spacing'])->toBe('normal');
});
