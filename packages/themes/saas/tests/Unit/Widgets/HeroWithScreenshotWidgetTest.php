<?php

declare(strict_types=1);

use Capell\Themes\Saas\Widgets\HeroWithScreenshotWidget;

test('hero-with-screenshot widget instantiates with required metadata', function () {
    $widget = HeroWithScreenshotWidget::make();

    expect($widget->name)->toBe('Hero with Screenshot')
        ->and($widget->description)->not->toBeEmpty()
        ->and($widget->view)->toBe('saas::components.hero-with-screenshot')
        ->and($widget->icon)->not->toBeEmpty();
});

test('hero-with-screenshot declares required fields', function () {
    $widget = HeroWithScreenshotWidget::make();

    expect($widget->fieldNames())->toContain('title', 'primary_cta_label', 'primary_cta_url', 'screenshot_url');
});

test('hero-with-screenshot render produces non-empty html', function () {
    $widget = HeroWithScreenshotWidget::make();

    $html = $widget->render(['title' => 'Hello world']);

    expect($html)->toBeString()->not->toBeEmpty();
});
