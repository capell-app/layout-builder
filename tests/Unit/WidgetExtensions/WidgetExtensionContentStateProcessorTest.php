<?php

declare(strict_types=1);

use Capell\Admin\Actions\Widgets\NormalizeContentWidgetStateAction;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionStateUpcaster;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleFilamentWidget;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleInputData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleRenderData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\MockableStateUpcaster;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RenamingStateUpcaster;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ThrowingStateUpcaster;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\UnresolvableStateUpcaster;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** @param class-string<WidgetExtensionStateUpcaster>|null $stateUpcaster */
function stateIntegrityDefinition(
    int $stateVersion = 2,
    ?string $stateUpcaster = null,
): WidgetExtensionDefinitionData {
    return new WidgetExtensionDefinitionData(
        key: 'capell-app.slideshow',
        packageName: 'capell-app/widget-slideshow',
        stateVersion: $stateVersion,
        filamentWidget: ExampleFilamentWidget::class,
        inputData: ExampleInputData::class,
        renderData: ExampleRenderData::class,
        fallbackView: 'capell-widget-slideshow::widget',
        components: ['blade' => 'capell::widgets.capell-app.slideshow'],
        stateUpcaster: $stateVersion > 1 ? ($stateUpcaster ?? RenamingStateUpcaster::class) : null,
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

it('contains upcaster and container failures while logging only safe context', function (string $stateUpcaster): void {
    Log::spy();
    resolve(WidgetExtensionRegistry::class)->register(stateIntegrityDefinition(stateUpcaster: $stateUpcaster));
    $identity = (string) Str::uuid();
    $widget = [
        'type' => 'capell-app.slideshow',
        'data' => [
            'old_title' => 'Sensitive saved content',
            '__capell' => ['instance_id' => $identity],
        ],
    ];

    $normalized = NormalizeContentWidgetStateAction::run([$widget]);

    expect($normalized)->toBe([$widget]);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Widget extension state upcast failed.', Mockery::on(
            static fn (array $context): bool => $context === [
                'widget_key' => 'capell-app.slideshow',
                'target_version' => 2,
                'failure_type' => $stateUpcaster === ThrowingStateUpcaster::class
                    ? RuntimeException::class
                    : BindingResolutionException::class,
            ],
        ));
})->with([
    'runtime exception' => ThrowingStateUpcaster::class,
    'container resolution failure' => UnresolvableStateUpcaster::class,
]);

it('contains a non-array upcaster return without exposing saved content', function (): void {
    Log::spy();
    $upcaster = Mockery::mock(WidgetExtensionStateUpcaster::class);
    $upcaster->shouldReceive('upcast')->once()->andReturn('not-an-array');
    app()->instance(MockableStateUpcaster::class, $upcaster);
    resolve(WidgetExtensionRegistry::class)->register(stateIntegrityDefinition(
        stateUpcaster: MockableStateUpcaster::class,
    ));
    $identity = (string) Str::uuid();
    $widget = [
        'type' => 'capell-app.slideshow',
        'data' => [
            'old_title' => 'Sensitive saved content',
            '__capell' => ['instance_id' => $identity],
        ],
    ];

    expect(NormalizeContentWidgetStateAction::run([$widget]))->toBe([$widget]);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Widget extension state upcast failed.', [
            'widget_key' => 'capell-app.slideshow',
            'target_version' => 2,
            'failure_type' => TypeError::class,
        ]);
});

it('contains a container binding that resolves the wrong runtime type', function (): void {
    Log::spy();
    app()->instance(MockableStateUpcaster::class, new stdClass);
    resolve(WidgetExtensionRegistry::class)->register(stateIntegrityDefinition(
        stateUpcaster: MockableStateUpcaster::class,
    ));
    $widget = [
        'type' => 'capell-app.slideshow',
        'data' => ['__capell' => ['instance_id' => (string) Str::uuid()]],
    ];

    expect(NormalizeContentWidgetStateAction::run([$widget]))->toBe([$widget]);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Widget extension state upcast failed.', [
            'widget_key' => 'capell-app.slideshow',
            'target_version' => 2,
            'failure_type' => RuntimeException::class,
        ]);
});
