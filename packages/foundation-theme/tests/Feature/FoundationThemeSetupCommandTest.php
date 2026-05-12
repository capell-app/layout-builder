<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Widget;
use Capell\Core\Support\Creator\LayoutCreator;

it('installs Foundation theme layout defaults when the theme setup command runs', function (): void {
    resolve(LayoutCreator::class)->setup();

    $homeLayout = Layout::query()->where('key', LayoutEnum::Home->value)->firstOrFail();
    $homeLayout->update(['containers' => [], 'widgets' => []]);
    Widget::query()->where('key', 'hero')->delete();
    $homeLayout->refresh();

    expect($homeLayout->containers)->not->toHaveKey('hero')
        ->and($homeLayout->widgets)->toBe([])
        ->and(Widget::query()->where('key', 'hero')->exists())->toBeFalse();

    test()->artisan('capell:foundation-theme-setup')->assertSuccessful();

    $homeLayout->refresh();

    expect(array_keys($homeLayout->containers))->toBe(['hero', 'main'])
        ->and($homeLayout->containers['hero']['widgets'])->toBe([
            ['widget_key' => 'hero'],
        ])
        ->and($homeLayout->containers['main']['widgets'])->toBe([
            ['widget_key' => 'page-content'],
        ])
        ->and($homeLayout->widgets)->toBe(['hero', 'page-content'])
        ->and(Widget::query()->where('key', 'hero')->exists())->toBeTrue();
});

it('does not duplicate Foundation theme layout defaults on repeated setup', function (): void {
    resolve(LayoutCreator::class)->setup();

    Layout::query()
        ->where('key', LayoutEnum::Home->value)
        ->firstOrFail()
        ->update(['containers' => [], 'widgets' => []]);
    Widget::query()->where('key', 'hero')->delete();

    test()->artisan('capell:foundation-theme-setup')->assertSuccessful();
    test()->artisan('capell:foundation-theme-setup')->assertSuccessful();

    $homeLayout = Layout::query()->where('key', LayoutEnum::Home->value)->firstOrFail();

    expect(array_keys($homeLayout->containers))->toBe(['hero', 'main'])
        ->and($homeLayout->widgets)->toBe(['hero', 'page-content'])
        ->and(Widget::query()->where('key', 'hero')->count())->toBe(1);
});
