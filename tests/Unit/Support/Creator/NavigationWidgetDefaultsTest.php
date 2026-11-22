<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Support\Creator\WidgetCreator;

it('keeps a stable navigation widget and applies a new tabs view when reseeding', function (): void {
    $type = resolve(TypeCreator::class)->navigationWidgetType();
    $creator = resolve(WidgetCreator::class);

    $original = $creator->navigationTabsWidget(
        type: $type,
        widgetMeta: ['view_file' => 'example.tabs.first', 'label' => 'Primary'],
        navigationKey: 'primary-navigation',
    );
    $updated = $creator->navigationTabsWidget(
        type: $type,
        widgetMeta: ['view_file' => 'example.tabs.updated'],
        navigationKey: 'primary-navigation',
    );

    expect($updated->id)->toBe($original->id)
        ->and($updated->refresh()->view_file)->toBe('example.tabs.updated')
        ->and($updated->meta)->toMatchArray([
            'navigation' => 'primary-navigation',
            'label' => 'Primary',
        ]);
});
