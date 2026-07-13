<?php

declare(strict_types=1);

use Capell\Core\Enums\InteractionTargetType;
use Capell\Core\Enums\PresentationDeliveryMode;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetRenderContextAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\Livewire\OpaqueWidgetReference;

it('resolves public widget render context outside the blade view', function (): void {
    $widget = Widget::factory()->create([
        'key' => 'promo-card',
        'meta' => ['resource_groups' => ['type-gallery', 'shared']],
    ]);
    $widget->blueprint()->first()?->forceFill([
        'meta' => [
            'presentation' => ['delivery_mode' => PresentationDeliveryMode::ServerRendered->value],
            'resource_groups' => ['type-gallery', 'shared'],
        ],
    ])->save();

    $context = ResolvePublicWidgetRenderContextAction::run(
        layout: null,
        containerKey: 'main',
        widgetIndex: 2,
        widget: $widget->refresh()->load('blueprint'),
        widgetData: [
            'widget_key' => 'promo-card',
            'occurrence' => 3,
            'meta' => [
                'presentation' => ['delivery_mode' => PresentationDeliveryMode::LazyFragment->value],
                'resource_groups' => ['instance-gallery', 'shared', ''],
                'interactions' => [[
                    'label' => 'Open details',
                    'target' => [
                        'target_type' => InteractionTargetType::Fragment->value,
                    ],
                ]],
            ],
        ],
        type: 'blade',
    );

    $referenceData = OpaqueWidgetReference::decode((string) $context->widgetReference);
    $expectedResourceIds = [
        LayoutBuilderLayoutWidgetResourceUsageContributor::publicId('promo-card', 'type-gallery', 'main', 3),
        LayoutBuilderLayoutWidgetResourceUsageContributor::publicId('promo-card', 'shared', 'main', 3),
        LayoutBuilderLayoutWidgetResourceUsageContributor::publicId('promo-card', 'instance-gallery', 'main', 3),
    ];

    expect($context->occurrence)->toBe(3)
        ->and($context->widgetDomId)->toBe('layout-widget-' . hash('xxh128', 'global:main:2'))
        ->and($context->presentation->deliveryMode)->toBe(PresentationDeliveryMode::LazyFragment)
        ->and($context->isLazyFragment)->toBeTrue()
        ->and($context->resourcePublicIds)->toBe($expectedResourceIds)
        ->and($context->interactions)->toHaveCount(1)
        ->and($context->interactions[0]->target->type)->toBe(InteractionTargetType::Fragment)
        ->and($context->interactions[0]->target->fragmentReference)->toBe($context->widgetReference)
        ->and($referenceData['container_key'])->toBe('main')
        ->and($referenceData['widget_key'])->toBe('promo-card')
        ->and($referenceData['occurrence'])->toBe(3)
        ->and($referenceData['widget_index'])->toBe(2);
});

it('creates an opaque widget reference for livewire widgets without lazy presentation', function (): void {
    $widget = Widget::factory()->create(['key' => 'live-cta']);

    $context = ResolvePublicWidgetRenderContextAction::run(
        layout: null,
        containerKey: 'aside',
        widgetIndex: 0,
        widget: $widget,
        widgetData: ['widget_key' => 'live-cta'],
        type: 'livewire',
    );

    expect($context->isLazyFragment)->toBeFalse()
        ->and($context->widgetReference)->not->toBeNull();
});
