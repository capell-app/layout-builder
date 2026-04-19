<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Widgets\HeroSectionWidget;

test('hero widget instantiates with required metadata', function () {
    $widget = HeroSectionWidget::make();

    expect($widget->name)->toBe('Hero Section')
        ->and($widget->description)->not->toBeEmpty()
        ->and($widget->view)->toBe('corporate::components.hero-section')
        ->and($widget->icon)->not->toBeEmpty();
});

test('hero widget declares required fields', function () {
    $widget = HeroSectionWidget::make();

    expect($widget->fieldNames())->toContain('title', 'cta_label', 'cta_url');
});

test('hero widget render produces non-empty html', function () {
    $widget = HeroSectionWidget::make();

    $html = $widget->render(['title' => 'Hello world']);

    expect($html)->toBeString()->not->toBeEmpty();
});
