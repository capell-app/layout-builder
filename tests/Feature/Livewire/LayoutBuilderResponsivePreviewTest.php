<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('persists responsive container overrides separately from base colspan from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['colspan' => 12]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', LayoutBreakpoint::Mobile->value)
        ->call('resizeContainer', 'main', 6)
        ->assertSet('containers.main.meta.colspan', 12)
        ->assertSet('containers.main.meta.responsive.mobile.colspan', 6)
        ->call('saveLayout');

    expect($layout->fresh()->containers['main']['meta']['responsive']['mobile']['colspan'])->toBe(6);
});

it('can resize the active responsive preview without waiting for breakpoint rerender from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['colspan' => 12]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('resizeContainer', 'main', 6, LayoutBreakpoint::Mobile->value)
        ->assertSet('containers.main.meta.colspan', 12)
        ->assertSet('containers.main.meta.responsive.mobile.colspan', 6);
});

it('renders responsive preview switching as an alpine interaction from the package namespace', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['colspan' => 12]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSeeHtml('setActiveBreakpointPreview')
        ->assertSeeHtml('activeBreakpointMaxCanvasWidth')
        ->assertSeeHtml('activeBreakpointMinCanvasWidth')
        ->assertSeeHtml('data-match-frontend-container-layout="true"')
        ->assertSeeHtml('shouldStackContainersForActiveBreakpoint')
        ->assertSeeHtml('layout-builder-canvas-scroll')
        ->assertSeeHtml('x-bind:aria-pressed')
        ->assertDontSeeHtml('wire:click="setActiveBreakpoint');
});

it('can opt out of frontend container stacking in the admin preview from the package namespace', function (): void {
    config()->set('capell-layout-builder.preview.match_frontend_container_layout', false);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['colspan' => 12]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSeeHtml('data-match-frontend-container-layout="false"');
});

it('resets a responsive override to the base colspan fallback from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'widgets' => [],
            'meta' => [
                'colspan' => 12,
                'responsive' => ['mobile' => ['colspan' => 6]],
            ],
        ],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', 'mobile')
        ->call('resetResponsiveContainerOverride', 'main')
        ->assertSet('containers.main.meta.responsive', []);
});
