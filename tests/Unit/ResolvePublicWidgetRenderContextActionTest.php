<?php

declare(strict_types=1);

use Capell\Core\Enums\PresentationDeliveryMode;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetRenderContextAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;

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
                'resource_groups' => ['instance-gallery', 'shared', ''],
            ],
        ],
        type: 'blade',
    );

    $expectedResourceIds = [
        LayoutBuilderLayoutWidgetResourceUsageContributor::publicId('promo-card', 'type-gallery', 'main', 3),
        LayoutBuilderLayoutWidgetResourceUsageContributor::publicId('promo-card', 'shared', 'main', 3),
        LayoutBuilderLayoutWidgetResourceUsageContributor::publicId('promo-card', 'instance-gallery', 'main', 3),
    ];

    expect($context->occurrence)->toBe(3)
        ->and($context->widgetDomId)->toBe('layout-widget-' . hash('xxh128', 'global:main:2'))
        ->and($context->presentation->deliveryMode)->toBe(PresentationDeliveryMode::ServerRendered)
        ->and($context->isLazyFragment)->toBeFalse()
        ->and($context->widgetReference)->toBeNull()
        ->and($context->resourcePublicIds)->toBe($expectedResourceIds)
        ->and($context->interactions)->toBeEmpty();
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
