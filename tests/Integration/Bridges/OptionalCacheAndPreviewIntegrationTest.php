<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\FilamentPeek\Actions\StoreLayoutBuilderPreviewStateAction;
use Capell\HtmlCache\Actions\ClearCachedUrlsForModelAction;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Livewire\Filament\Support\LayoutBuilderActionFactory;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Tests\Support\LayoutBuilderCompanionActionSpy;

function markLayoutBuilderCompanionAvailable(string $package, bool $available): void
{
    CapellCore::partialMock()
        ->shouldReceive('isPackageAvailable')
        ->with($package)
        ->andReturn($available);
    CapellCore::shouldReceive('isPackageAvailable')->passthru();
}

function bindLayoutBuilderCompanionSpy(string $actionClass): LayoutBuilderCompanionActionSpy
{
    $spy = new LayoutBuilderCompanionActionSpy;

    app()->instance($actionClass, $spy);

    return $spy;
}

function clearCachedPagesForLayoutBuilderWidget(): void
{
    $factory = new LayoutBuilderActionFactory(new LayoutBuilder);

    (fn () => $this->clearCachedPagesForWidget(new Widget))->call($factory);
}

function clearLayoutBuilderPreviewState(): void
{
    $component = new LayoutBuilder;
    $component->page = new Page;
    $component->layoutUpdated(false);
}

it('clears cached pages through HTML Cache when a widget changes and HTML Cache is available', function (): void {
    markLayoutBuilderCompanionAvailable('capell-app/html-cache', true);
    $spy = bindLayoutBuilderCompanionSpy(ClearCachedUrlsForModelAction::class);

    clearCachedPagesForLayoutBuilderWidget();

    expect($spy->calls)->toBe(['handle'])
        ->and(resolve(ClearCachedUrlsForModelAction::class))->toBe($spy);
});

it('leaves cached pages alone when HTML Cache is not available', function (): void {
    markLayoutBuilderCompanionAvailable('capell-app/html-cache', false);
    $spy = bindLayoutBuilderCompanionSpy(ClearCachedUrlsForModelAction::class);

    clearCachedPagesForLayoutBuilderWidget();

    expect($spy->calls)->toBe([]);
});

it('clears the Filament Peek preview state when a layout is saved and Filament Peek is available', function (): void {
    markLayoutBuilderCompanionAvailable('capell-app/filament-peek', true);
    $spy = bindLayoutBuilderCompanionSpy(StoreLayoutBuilderPreviewStateAction::class);

    clearLayoutBuilderPreviewState();

    expect($spy->calls)->toBe(['clear'])
        ->and(resolve(StoreLayoutBuilderPreviewStateAction::class))->toBe($spy);
});

it('keeps no preview state when Filament Peek is not available', function (): void {
    markLayoutBuilderCompanionAvailable('capell-app/filament-peek', false);
    $spy = bindLayoutBuilderCompanionSpy(StoreLayoutBuilderPreviewStateAction::class);

    clearLayoutBuilderPreviewState();

    expect($spy->calls)->toBe([]);
});
