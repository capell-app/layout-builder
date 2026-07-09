<?php

declare(strict_types=1);

use Capell\Admin\Actions\Widgets\NormalizeContentWidgetStateAction;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleFilamentWidget;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleInputData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleRenderData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RenamingStateUpcaster;
use Illuminate\Support\Str;

function stateIntegrityDefinition(int $stateVersion = 2): WidgetExtensionDefinitionData
{
    return new WidgetExtensionDefinitionData(
        key: 'capell-app.slideshow',
        packageName: 'capell-app/widget-slideshow',
        stateVersion: $stateVersion,
        filamentWidget: ExampleFilamentWidget::class,
        inputData: ExampleInputData::class,
        renderData: ExampleRenderData::class,
        fallbackView: 'capell-widget-slideshow::widget',
        components: ['blade' => 'capell::widgets.capell-app.slideshow'],
        stateUpcaster: $stateVersion > 1 ? RenamingStateUpcaster::class : null,
    );
}

it('upcasts registered extension state and reserves the current state version', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(stateIntegrityDefinition());

    $normalized = NormalizeContentWidgetStateAction::run([
        [
            'type' => 'capell-app.slideshow',
            'data' => ['old_title' => 'Migrated title'],
        ],
    ]);

    expect($normalized[0]['data']['title'])->toBe('Migrated title')
        ->and($normalized[0]['data'])->not->toHaveKey('old_title')
        ->and($normalized[0]['data']['__capell']['state_version'])->toBe(2)
        ->and(Str::isUuid($normalized[0]['data']['__capell']['instance_id']))->toBeTrue();
});

it('applies upcasting recursively to registered nested targets', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(stateIntegrityDefinition());

    $normalized = NormalizeContentWidgetStateAction::run([
        [
            'type' => 'capell-app.slideshow',
            'data' => [
                'old_title' => 'Outer',
                'interaction' => [
                    'target_widget' => [
                        'type' => 'capell-app.slideshow',
                        'data' => ['old_title' => 'Nested'],
                    ],
                ],
            ],
        ],
    ]);

    expect($normalized[0]['data']['title'])->toBe('Outer')
        ->and($normalized[0]['data']['interaction']['target_widget']['data']['title'])->toBe('Nested')
        ->and($normalized[0]['data']['interaction']['target_widget']['data']['__capell']['state_version'])->toBe(2);
});

it('preserves future extension state unchanged apart from required identity normalization', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(stateIntegrityDefinition());
    $identity = (string) Str::uuid();
    $futureWidget = [
        'type' => 'capell-app.slideshow',
        'data' => [
            'old_title' => 'Future shape',
            '__capell' => [
                'instance_id' => $identity,
                'state_version' => 99,
                'presentation' => ['width' => 'wide'],
            ],
        ],
        'future-key' => true,
    ];

    $normalized = NormalizeContentWidgetStateAction::run([$futureWidget]);

    expect($normalized)->toBe([$futureWidget]);
});

it('versions initial state at one without requiring an upcaster', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(stateIntegrityDefinition(stateVersion: 1));

    $normalized = NormalizeContentWidgetStateAction::run([
        ['type' => 'capell-app.slideshow', 'data' => ['title' => 'Current']],
    ]);

    expect($normalized[0]['data']['__capell']['state_version'])->toBe(1);
});
