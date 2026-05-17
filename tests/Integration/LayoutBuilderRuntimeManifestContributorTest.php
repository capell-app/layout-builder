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
use Capell\LayoutBuilder\Models\Element;

it('adds layout builder runtime manifest flags for blade layouts with blade elements', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $element = Element::factory()->create([
        'key' => 'blade-element',
        'meta' => [
            'component' => 'capell.element.default',
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
            'component' => 'capell.element.default',
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

it('extracts livewire element keys from container key fallbacks', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $element = Element::factory()->create([
        'key' => 'container-key-livewire-element',
        'is_livewire' => true,
    ]);
    $layout = Layout::factory()->make([
        'containers' => [
            'main' => [
                'elements' => [
                    ['key' => $element->key],
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

it('ignores disabled livewire elements when contributing blade runtime flags', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $element = Element::factory()->create([
        'key' => 'disabled-livewire-element',
        'is_livewire' => true,
        'status' => false,
    ]);
    $layout = Layout::factory()->make([
        'elements' => [$element->key],
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

it('ignores future livewire elements when contributing blade runtime flags', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $element = Element::factory()->create([
        'key' => 'future-livewire-element',
        'is_livewire' => true,
        'visible_from' => now()->addDay(),
    ]);
    $layout = Layout::factory()->make([
        'elements' => [$element->key],
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

it('ignores livewire elements with inaccessible element blueprints when contributing blade runtime flags', function (): void {
    $page = Page::factory()->make(['meta' => null]);
    $blueprint = Blueprint::factory()
        ->type(LayoutTypeEnum::Element->value)
        ->create(['meta' => ['accessible' => false]]);
    $element = Element::factory()->create([
        'blueprint_id' => $blueprint->getKey(),
        'key' => 'inaccessible-livewire-element',
        'is_livewire' => true,
    ]);
    $layout = Layout::factory()->make([
        'elements' => [$element->key],
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
    $element = Element::factory()->create([
        'key' => 'livewire-element',
        'meta' => [
            'component' => 'capell.element.default',
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
