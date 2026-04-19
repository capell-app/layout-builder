<?php

declare(strict_types=1);

use Capell\Themes\Corporate\Widgets\TeamGridWidget;

test('team grid widget declares members field', function () {
    $widget = TeamGridWidget::make();

    expect($widget->fieldNames())->toContain('members')
        ->and($widget->view)->toBe('corporate::components.team-grid')
        ->and($widget->name)->toBe('Team Grid');
});
