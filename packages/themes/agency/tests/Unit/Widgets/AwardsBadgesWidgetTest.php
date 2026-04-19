<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\AwardsBadgesWidget;

test('awards badges widget has awards field', function () {
    $widget = AwardsBadgesWidget::make();

    expect($widget->fieldNames())->toContain('awards')
        ->and($widget->view)->toBe('agency::components.awards-badges');
});

test('awards defaults include year and organizer', function () {
    $widget = AwardsBadgesWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['awards'])->toBeArray()->not->toBeEmpty();
    expect($defaults['awards'][0])->toHaveKeys(['name', 'organizer', 'year']);
});
