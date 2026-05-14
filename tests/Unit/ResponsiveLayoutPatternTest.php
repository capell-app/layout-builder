<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Enums\ResponsiveLayoutPattern;

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
