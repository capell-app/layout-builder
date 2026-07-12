<?php

declare(strict_types=1);

use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Frontend\Actions\ResolveFrontendRuntimeAction;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Widget;

it('adds layout builder runtime manifest flags for blade layouts with blade widgets', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $widget = Widget::factory()->create([
        'key' => 'blade-widget',
        'meta' => [
            'component' => 'capell.widget.default',
        ],
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key],
                ],
            ],
        ],
    ]);

    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('page')->andReturn($page);
    $context->shouldReceive('layout')->andReturn($layout);
    $context->shouldReceive('theme')->andReturnNull();

    $resolution = ResolveFrontendRuntimeAction::run($context);

    expect($resolution->runtime)->toBe(FrontendRuntime::Blade)
        ->and($resolution->runtimeManifest->renderingStrategy)->toBe(RenderingStrategyEnum::BladeOnly)
        ->and($resolution->runtimeManifest->usesAlpine)->toBeTrue()
        ->and($resolution->runtimeManifest->usesLivewire)->toBeFalse()
        ->and($resolution->runtimeManifest->usesIslands)->toBeFalse()
        ->and($resolution->runtimeManifest->modules['layout-builder'])->toBeTrue();
});

it('adds livewire island flags for blade layouts with livewire widgets', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $widget = Widget::factory()->create([
        'key' => 'livewire-widget',
        'meta' => [
            'component' => 'capell.widget.default',
            'livewire' => true,
        ],
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key],
                ],
            ],
        ],
    ]);

    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('page')->andReturn($page);
    $context->shouldReceive('layout')->andReturn($layout);
    $context->shouldReceive('theme')->andReturnNull();

    $resolution = ResolveFrontendRuntimeAction::run($context);

    expect($resolution->runtime)->toBe(FrontendRuntime::Blade)
        ->and($resolution->runtimeManifest->renderingStrategy)->toBe(RenderingStrategyEnum::BladeOnly)
        ->and($resolution->runtimeManifest->usesAlpine)->toBeTrue()
        ->and($resolution->runtimeManifest->usesLivewire)->toBeTrue()
        ->and($resolution->runtimeManifest->usesIslands)->toBeTrue()
        ->and($resolution->runtimeManifest->modules['layout-builder'])->toBeTrue();
});

it('extracts livewire widget keys from container key fallbacks', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $widget = Widget::factory()->create([
        'key' => 'container-key-livewire-widget',
        'is_livewire' => true,
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['key' => $widget->key],
                ],
            ],
        ],
    ]);

    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('page')->andReturn($page);
    $context->shouldReceive('layout')->andReturn($layout);
    $context->shouldReceive('theme')->andReturnNull();

    $resolution = ResolveFrontendRuntimeAction::run($context);

    expect($resolution->runtimeManifest->usesAlpine)->toBeTrue()
        ->and($resolution->runtimeManifest->usesLivewire)->toBeTrue()
        ->and($resolution->runtimeManifest->usesIslands)->toBeTrue()
        ->and($resolution->runtimeManifest->modules['layout-builder'])->toBeTrue();
});

it('ignores disabled livewire widgets when contributing blade runtime flags', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $widget = Widget::factory()->create([
        'key' => 'disabled-livewire-widget',
        'is_livewire' => true,
        'status' => false,
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key],
                ],
            ],
        ],
    ]);

    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('page')->andReturn($page);
    $context->shouldReceive('layout')->andReturn($layout);
    $context->shouldReceive('theme')->andReturnNull();

    $resolution = ResolveFrontendRuntimeAction::run($context);

    expect($resolution->runtimeManifest->usesAlpine)->toBeTrue()
        ->and($resolution->runtimeManifest->usesLivewire)->toBeFalse()
        ->and($resolution->runtimeManifest->usesIslands)->toBeFalse()
        ->and($resolution->runtimeManifest->modules['layout-builder'])->toBeTrue();
});

it('ignores future livewire widgets when contributing blade runtime flags', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $widget = Widget::factory()->create([
        'key' => 'future-livewire-widget',
        'is_livewire' => true,
        'visible_from' => now()->addDay(),
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key],
                ],
            ],
        ],
    ]);

    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('page')->andReturn($page);
    $context->shouldReceive('layout')->andReturn($layout);
    $context->shouldReceive('theme')->andReturnNull();

    $resolution = ResolveFrontendRuntimeAction::run($context);

    expect($resolution->runtimeManifest->usesAlpine)->toBeTrue()
        ->and($resolution->runtimeManifest->usesLivewire)->toBeFalse()
        ->and($resolution->runtimeManifest->usesIslands)->toBeFalse()
        ->and($resolution->runtimeManifest->modules['layout-builder'])->toBeTrue();
});

it('ignores livewire widgets with inaccessible widget blueprints when contributing blade runtime flags', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $blueprint = Blueprint::factory()
        ->type(LayoutTypeEnum::Widget->value)
        ->create(['meta' => ['accessible' => false]]);
    $widget = Widget::factory()->create([
        'blueprint_id' => $blueprint->getKey(),
        'key' => 'inaccessible-livewire-widget',
        'is_livewire' => true,
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key],
                ],
            ],
        ],
    ]);

    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('page')->andReturn($page);
    $context->shouldReceive('layout')->andReturn($layout);
    $context->shouldReceive('theme')->andReturnNull();

    $resolution = ResolveFrontendRuntimeAction::run($context);

    expect($resolution->runtimeManifest->usesAlpine)->toBeTrue()
        ->and($resolution->runtimeManifest->usesLivewire)->toBeFalse()
        ->and($resolution->runtimeManifest->usesIslands)->toBeFalse()
        ->and($resolution->runtimeManifest->modules['layout-builder'])->toBeTrue();
});

it('does not change non blade-only runtime manifests', function (): void {
    $page = Page::factory()->make([
        'meta' => ['rendering_strategy' => RenderingStrategyEnum::FullLivewire->value],
    ]);
    $widget = Widget::factory()->create([
        'key' => 'livewire-widget',
        'meta' => [
            'component' => 'capell.widget.default',
            'livewire' => true,
        ],
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key],
                ],
            ],
        ],
    ]);

    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('page')->andReturn($page);
    $context->shouldReceive('layout')->andReturn($layout);

    $resolution = ResolveFrontendRuntimeAction::run($context);

    expect($resolution->runtime)->toBe(FrontendRuntime::Livewire)
        ->and($resolution->runtimeManifest->renderingStrategy)->toBe(RenderingStrategyEnum::FullLivewire)
        ->and($resolution->runtimeManifest->usesLivewire)->toBeTrue()
        ->and($resolution->runtimeManifest->usesAlpine)->toBeTrue()
        ->and($resolution->runtimeManifest->usesIslands)->toBeFalse()
        ->and($resolution->runtimeManifest->modules)->toBe([]);
});
