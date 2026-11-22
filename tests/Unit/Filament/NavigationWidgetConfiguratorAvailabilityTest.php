<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\NavigationWidgetConfigurator;
use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;

function navigationWidgetFieldClass(bool $available): string
{
    CapellCore::partialMock()
        ->shouldReceive('isPackageAvailable')
        ->with('capell-app/navigation')
        ->andReturn($available);
    CapellCore::shouldReceive('isPackageAvailable')->passthru();
    CapellCore::shouldReceive('isPackageInstalled')
        ->with('capell-app/navigation')
        ->andReturnTrue();
    CapellCore::shouldReceive('isPackageInstalled')->passthru();

    $configurator = resolve(NavigationWidgetConfigurator::class);
    $group = (fn (): Group => $this->navigationSelect())->call($configurator);

    $children = (fn (): array => $this->childComponents)->call($group);
    $first = array_values(array_merge(...array_values(array_map(static fn (mixed $slot): array => is_array($slot) ? $slot : [$slot], $children))))[0];

    return $first::class;
}

it('offers a plain navigation key field when Navigation is installed but not available', function (): void {
    expect(navigationWidgetFieldClass(false))->toBe(TextInput::class);
});

it('offers the Navigation picker when Navigation is available and installed', function (): void {
    expect(navigationWidgetFieldClass(true))->toBe(NavigationSelect::class);
});
