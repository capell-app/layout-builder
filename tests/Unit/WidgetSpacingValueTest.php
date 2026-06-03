<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Enums\WidgetSpacingValue;
use Capell\LayoutBuilder\Filament\Components\Forms\MarginSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\PaddingSelect;

it('defines the saved widget spacing tokens', function (): void {
    expect(array_map(
        static fn (WidgetSpacingValue $spacing): string => $spacing->value,
        WidgetSpacingValue::cases(),
    ))->toBe([
        'none',
        'sm',
        't-sm',
        'b-sm',
        'md',
        't-md',
        'b-md',
        'lg',
        't-lg',
        'b-lg',
        'xl',
        't-xl',
        'b-xl',
    ]);
});

it('uses widget spacing values for padding and margin options', function (): void {
    $expectedOptions = collect(WidgetSpacingValue::cases())
        ->mapWithKeys(fn (WidgetSpacingValue $spacing): array => [$spacing->value => $spacing->getLabel()])
        ->all();

    expect(PaddingSelect::make('padding')->getOptions())->toBe($expectedOptions)
        ->and(MarginSelect::make('margin')->getOptions())->toBe($expectedOptions);
});

it('hydrates legacy string spacing values as arrays', function (): void {
    expect(PaddingSelect::normalizeHydratedState('lg'))->toBe(['lg'])
        ->and(MarginSelect::normalizeHydratedState('t-sm'))->toBe(['t-sm']);
});

it('preserves current array spacing values during hydration', function (): void {
    expect(PaddingSelect::normalizeHydratedState(['lg']))->toBe(['lg'])
        ->and(MarginSelect::normalizeHydratedState(['t-sm', 'b-lg']))->toBe(['t-sm', 'b-lg']);
});
