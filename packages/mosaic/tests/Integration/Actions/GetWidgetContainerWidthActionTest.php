<?php

declare(strict_types=1);

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Models\Layout;
use Capell\Frontend\Support\State\FrontendState;
use Capell\Mosaic\Actions\GetWidgetContainerWidthAction;
use Capell\Mosaic\Actions\InstallPackageAction;
use Capell\Mosaic\Models\Widget;

beforeEach(function (): void {
    InstallPackageAction::run();
    $layout = Layout::factory()->create(['meta' => null]);
    app()->make(FrontendState::class)->withLayout($layout);
});

it('returns the container width from the widget meta', function (): void {
    $widget = Widget::factory()->create(['meta' => ['container' => 'full']]);

    $result = GetWidgetContainerWidthAction::run($widget);

    expect($result)->toBe(ContainerWidthEnum::Full);
});

it('returns the correct enum for each stored container value', function (string $value, ContainerWidthEnum $expected): void {
    $widget = Widget::factory()->create(['meta' => ['container' => $value]]);

    expect(GetWidgetContainerWidthAction::run($widget))->toBe($expected);
})->with([
    ['container', ContainerWidthEnum::Default],
    ['sm', ContainerWidthEnum::Small],
    ['xl', ContainerWidthEnum::ExtraLarge],
]);

it('falls back to the provided default when the widget has no container meta', function (): void {
    $widget = Widget::factory()->create(['meta' => []]);

    $result = GetWidgetContainerWidthAction::run($widget, 'full');

    expect($result)->toBe(ContainerWidthEnum::Full);
});

it('falls back to ExtraLarge when no meta and no default is given', function (): void {
    $widget = Widget::factory()->create(['meta' => []]);

    $result = GetWidgetContainerWidthAction::run($widget);

    expect($result)->toBe(ContainerWidthEnum::ExtraLarge);
});
