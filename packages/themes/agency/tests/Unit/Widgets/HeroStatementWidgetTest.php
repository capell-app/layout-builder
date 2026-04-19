<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\HeroStatementWidget;

test('hero statement widget instantiates with required metadata', function () {
    $widget = HeroStatementWidget::make();

    expect($widget->name)->toBe('Hero Statement')
        ->and($widget->description)->not->toBeEmpty()
        ->and($widget->view)->toBe('agency::components.hero-statement')
        ->and($widget->icon)->not->toBeEmpty();
});

test('hero statement widget declares required fields', function () {
    $widget = HeroStatementWidget::make();

    expect($widget->fieldNames())->toContain('statement', 'cta_label', 'cta_url');
});

test('hero statement widget render produces non-empty html', function () {
    $widget = HeroStatementWidget::make();

    $html = $widget->render(['statement' => 'Make it beautiful']);

    expect($html)->toBeString()->not->toBeEmpty();
});
