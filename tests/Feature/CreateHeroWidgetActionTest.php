<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\CreateHeroWidgetAction;
use Capell\LayoutBuilder\Models\Widget;

it('persists the hero component as a string value without an encoded enum meta payload', function (): void {
    $widget = CreateHeroWidgetAction::run();

    $widget->refresh();

    $meta = json_decode((string) $widget->getRawOriginal('meta'), true, flags: JSON_THROW_ON_ERROR);

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->component)->toBe('capell.widget.hero')
        ->and($widget->component)->toBeString()
        ->and($meta)->not->toHaveKey('component');
});
