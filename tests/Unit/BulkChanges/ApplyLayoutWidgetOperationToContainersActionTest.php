<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\BulkChanges\ApplyLayoutWidgetOperationToContainersAction;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;

/**
 * @param  array<string, mixed>  $payload
 */
function layoutBulkOperation(array $payload): LayoutBulkWidgetOperationData
{
    return LayoutBulkWidgetOperationData::fromPayload($payload);
}

/**
 * @param  array<string, mixed>  $containers
 * @return array<string, mixed>
 */
function layoutBulkContainer(array $containers, string $container): array
{
    $containerState = $containers[$container] ?? [];

    return is_array($containerState) ? $containerState : [];
}

/**
 * @param  array<string, mixed>  $containers
 * @return list<array<string, mixed>>
 */
function layoutBulkWidgets(array $containers, string $container): array
{
    $widgets = layoutBulkContainer($containers, $container)['widgets'] ?? [];

    if (! is_array($widgets)) {
        return [];
    }

    $normalizedWidgets = [];

    foreach ($widgets as $widget) {
        if (is_array($widget)) {
            $normalizedWidgets[] = $widget;
        }
    }

    return $normalizedWidgets;
}

/**
 * @param  array<string, mixed>  $containers
 * @return list<string>
 */
function layoutBulkWidgetKeys(array $containers, string $container): array
{
    $keys = [];

    foreach (layoutBulkWidgets($containers, $container) as $widget) {
        $key = $widget['widget_key'] ?? null;

        if (is_string($key)) {
            $keys[] = $key;
        }
    }

    return $keys;
}

it('moves a widget relative to another widget', function (): void {
    $result = ApplyLayoutWidgetOperationToContainersAction::run([
        'main' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1, 'meta' => ['locked' => true]],
            ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1],
            ['widget_key' => 'content', 'container' => 'main', 'occurrence' => 1],
        ], 'meta' => ['width' => 'full']],
    ], layoutBulkOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
        'placement' => 'after',
    ]));

    expect($result->changed)->toBeTrue()
        ->and(layoutBulkWidgetKeys($result->containers, 'main'))->toBe(['hero', 'breadcrumbs', 'content'])
        ->and(layoutBulkContainer($result->containers, 'main')['meta'] ?? [])->toBe(['width' => 'full'])
        ->and(layoutBulkWidgets($result->containers, 'main')[1]['meta'] ?? [])->toBe(['locked' => true])
        ->and($result->containerDiffs[0])->toMatchArray([
            'container' => 'main',
            'before' => ['breadcrumbs#1', 'hero#1', 'content#1'],
            'after' => ['hero#1', 'breadcrumbs#1', 'content#1'],
        ]);
});

it('removes widgets without touching other containers', function (): void {
    $result = ApplyLayoutWidgetOperationToContainersAction::run([
        'main' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1],
            ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1],
        ]],
        'sidebar' => ['widgets' => [
            ['widget_key' => 'cta', 'container' => 'sidebar', 'occurrence' => 1],
        ]],
    ], layoutBulkOperation([
        'type' => LayoutBulkWidgetOperationType::RemoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
    ]));

    expect($result->changed)->toBeTrue()
        ->and(layoutBulkWidgetKeys($result->containers, 'main'))->toBe(['hero'])
        ->and(layoutBulkWidgetKeys($result->containers, 'sidebar'))->toBe(['cta'])
        ->and($result->assetRemovals[0])->toMatchArray([
            'widget_key' => 'breadcrumbs',
            'container' => 'main',
            'occurrence' => 1,
        ]);
});

it('swaps two widgets across containers', function (): void {
    $result = ApplyLayoutWidgetOperationToContainersAction::run([
        'main' => ['widgets' => [['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]],
        'sidebar' => ['widgets' => [['widget_key' => 'breadcrumbs', 'container' => 'sidebar', 'occurrence' => 1]]],
    ], layoutBulkOperation([
        'type' => LayoutBulkWidgetOperationType::SwapWidgets->value,
        'source_widget_key' => 'hero',
        'target_widget_key' => 'breadcrumbs',
    ]));

    expect($result->changed)->toBeTrue()
        ->and(layoutBulkWidgetKeys($result->containers, 'main'))->toBe(['breadcrumbs'])
        ->and(layoutBulkWidgetKeys($result->containers, 'sidebar'))->toBe(['hero'])
        ->and($result->assetMoves)->toHaveCount(2);
});

it('moves a widget to another container and renumbers collisions', function (): void {
    $result = ApplyLayoutWidgetOperationToContainersAction::run([
        'main' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1],
            ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1],
        ]],
        'sidebar' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'sidebar', 'occurrence' => 1],
        ]],
    ], layoutBulkOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidgetToContainer->value,
        'source_widget_key' => 'breadcrumbs',
        'source_container_key' => 'main',
        'target_container_key' => 'sidebar',
        'placement' => 'bottom',
        'occurrence_mode' => 'first',
    ]));

    expect($result->changed)->toBeTrue()
        ->and(layoutBulkWidgetKeys($result->containers, 'main'))->toBe(['hero'])
        ->and(layoutBulkWidgets($result->containers, 'sidebar')[1]['occurrence'] ?? null)->toBe(2)
        ->and($result->assetMoves[0])->toMatchArray(['from_container' => 'main', 'from_occurrence' => 1, 'to_container' => 'sidebar', 'to_occurrence' => 2]);
});

it('skips layouts with missing targets instead of failing the operation', function (): void {
    $result = ApplyLayoutWidgetOperationToContainersAction::run([
        'main' => ['widgets' => [['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1]]],
    ], layoutBulkOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'hero',
    ]));

    expect($result->changed)->toBeFalse()
        ->and($result->skippedReason)->toContain('Source widget [breadcrumbs] was not found');
});

it('targets a specific occurrence', function (): void {
    $result = ApplyLayoutWidgetOperationToContainersAction::run([
        'main' => ['widgets' => [
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 1],
            ['widget_key' => 'hero', 'container' => 'main', 'occurrence' => 1],
            ['widget_key' => 'breadcrumbs', 'container' => 'main', 'occurrence' => 2],
            ['widget_key' => 'content', 'container' => 'main', 'occurrence' => 1],
        ]],
    ], layoutBulkOperation([
        'type' => LayoutBulkWidgetOperationType::MoveWidget->value,
        'source_widget_key' => 'breadcrumbs',
        'target_widget_key' => 'content',
        'placement' => 'after',
        'occurrence_mode' => 'specific',
        'source_occurrence_number' => 2,
    ]));

    expect(layoutBulkWidgetKeys($result->containers, 'main'))->toBe(['breadcrumbs', 'hero', 'content', 'breadcrumbs'])
        ->and($result->assetMoves[0])->toMatchArray([
            'from_occurrence' => 2,
            'to_occurrence' => 2,
        ]);
});
