<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\BulkChanges\ApplyLayoutWidgetOperationToContainersAction;
use Capell\LayoutBuilder\Data\LayoutBulkWidgetOperationData;
use Capell\LayoutBuilder\Enums\LayoutBulkWidgetOperationType;

function layoutBulkOperation(array $payload): LayoutBulkWidgetOperationData
{
    return LayoutBulkWidgetOperationData::fromPayload($payload);
}

function layoutBulkWidgetKeys(array $containers, string $container): array
{
    return array_map(fn (array $widget): string => (string) $widget['widget_key'], $containers[$container]['widgets'] ?? []);
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
        ->and($result->containers['main']['meta'])->toBe(['width' => 'full'])
        ->and($result->containers['main']['widgets'][1]['meta'])->toBe(['locked' => true]);
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
        ->and(layoutBulkWidgetKeys($result->containers, 'sidebar'))->toBe(['cta']);
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
        ->and($result->containers['sidebar']['widgets'][1]['occurrence'])->toBe(2)
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
