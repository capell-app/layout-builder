<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\CreateHeroWidgetAction;
use Capell\LayoutBuilder\Models\Widget;

it('creates a hero widget with the correct key', function (): void {
    $widget = CreateHeroWidgetAction::run();

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->key)->toBe('hero')
        ->and(Widget::query()->where('key', 'hero')->count())->toBe(1);
});

it('accepts a custom key and label', function (): void {
    $widget = CreateHeroWidgetAction::run(key: 'page-hero', label: 'Page Hero Banner');

    expect($widget->key)->toBe('page-hero')
        ->and($widget->name)->toBe('Page Hero Banner');
});

it('is idempotent and returns the same record on repeated calls', function (): void {
    $first = CreateHeroWidgetAction::run();
    $second = CreateHeroWidgetAction::run();

    expect($second->getKey())->toBe($first->getKey())
        ->and(Widget::query()->where('key', 'hero')->count())->toBe(1);
});

it('stores carousel and heading defaults in meta', function (): void {
    $widget = CreateHeroWidgetAction::run();

    expect($widget->meta)
        ->toHaveKey('heading_size')
        ->toHaveKey('carousel_fade')
        ->toHaveKey('carousel_auto_play')
        ->and($widget->meta['heading_size'])->toBe('h1')
        ->and($widget->meta['carousel_fade'])->toBeTrue();
});

it('merges extra meta when provided', function (): void {
    $widget = CreateHeroWidgetAction::run(meta: ['color' => 'light', 'carousel_loop' => false]);

    expect($widget->meta['color'])->toBe('light')
        ->and($widget->meta['carousel_loop'])->toBeFalse();
});
