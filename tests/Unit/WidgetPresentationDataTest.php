<?php

declare(strict_types=1);

use Capell\BlockLibrary\Data\BlockCompatibilityData;
use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockVariantData;
use Capell\BlockLibrary\Data\BlockVariantKey;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\LayoutBuilder\Actions\ResolveWidgetPresentationDataAction;
use Capell\LayoutBuilder\Models\Widget;

it('projects only allowlisted widget presentation keys from widget meta', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'test-hero',
        label: 'Hero',
        description: 'Hero widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.hero',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::widgets.variants.split_media'),
        ],
    ));

    $widget = Widget::factory()->create([
        'key' => 'test-hero',
        'meta' => [
            'widget_variant' => 'split-media',
            'widget_settings' => [
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

    $payload = ResolveWidgetPresentationDataAction::run($widget)->toArray();

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
        ->and($payload)->not->toHaveKeys(['signed_url', 'admin_schema', 'widget_settings']);
});

it('falls back to a safe variant when the variant is unknown or theme unsupported', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'proof',
        label: 'Proof',
        description: 'Proof widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.proof',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
            new BlockVariantData(BlockVariantKey::from('bento'), 'vendor-package::widgets.variants.bento'),
        ],
        defaultVariant: BlockVariantKey::from('default'),
        compatibility: new BlockCompatibilityData(themeKeys: ['foundation']),
    ));

    $widget = Widget::factory()->create([
        'key' => 'proof',
        'meta' => [
            'widget_variant' => 'bento',
            'widget_settings' => ['spacing' => 'spacious'],
        ],
    ]);

    $payload = ResolveWidgetPresentationDataAction::run($widget, 'unsupported-theme')->toArray();

    expect($payload['variant'])->toBe('default')
        ->and($payload['spacing'])->toBe('normal');
});
