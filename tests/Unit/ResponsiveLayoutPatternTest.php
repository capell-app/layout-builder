<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Enums\ResponsiveLayoutPattern;
use Capell\LayoutBuilder\Filament\Components\Forms\ResponsiveLayoutPatternSelect;

it('resolves missing responsive layout pattern values to grid', function (): void {
    expect(ResponsiveLayoutPattern::fromNullable(null))->toBe(ResponsiveLayoutPattern::Grid)
        ->and(ResponsiveLayoutPattern::fromNullable(''))->toBe(ResponsiveLayoutPattern::Grid)
        ->and(ResponsiveLayoutPattern::fromNullable('unknown'))->toBe(ResponsiveLayoutPattern::Grid)
        ->and(ResponsiveLayoutPattern::fromNullable(['grid']))->toBe(ResponsiveLayoutPattern::Grid);
});

it('identifies the desktop grid mobile carousel pattern capabilities', function (): void {
    $pattern = ResponsiveLayoutPattern::DesktopGridMobileCarousel;

    expect($pattern->usesDesktopGrid())->toBeTrue()
        ->and($pattern->usesMobileCarousel())->toBeTrue()
        ->and(ResponsiveLayoutPattern::Grid->usesMobileCarousel())->toBeFalse();
});

it('offers theme inheritance before per-widget responsive overrides', function (): void {
    $field = ResponsiveLayoutPatternSelect::make('responsive_layout_pattern');
    expect($field->getDefaultState())->toBeNull()
        ->and($field->getPlaceholder())->toBe(__('capell-layout-builder::form.responsive_layout_pattern_inherit'))
        ->and($field->canSelectPlaceholder())->toBeTrue();
});
