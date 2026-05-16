<?php

declare(strict_types=1);

use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Frontend\Actions\ResolveFrontendRuntimeAction;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\LayoutBuilder\Models\Element;

it('adds layout builder runtime manifest flags for blade layouts with blade elements', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $element = Element::factory()->create([
        'key' => 'blade-element',
        'meta' => [
            'component' => 'capell.widget.default',
        ],
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'elements' => [
                    ['element_key' => $element->key],
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

it('adds livewire island flags for blade layouts with livewire elements', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $element = Element::factory()->create([
        'key' => 'livewire-element',
        'meta' => [
            'component' => 'capell.widget.default',
            'livewire' => true,
        ],
    ]);
    $layout = Layout::factory()->make([
        'elements' => [$element->key],
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

it('does not change non blade-only runtime manifests', function (): void {
    $page = Page::factory()->make([
        'meta' => ['rendering_strategy' => RenderingStrategyEnum::FullLivewire->value],
    ]);
    $element = Element::factory()->create([
        'key' => 'livewire-element',
        'meta' => [
            'component' => 'capell.widget.default',
            'livewire' => true,
        ],
    ]);
    $layout = Layout::factory()->make([
        'elements' => [$element->key],
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
