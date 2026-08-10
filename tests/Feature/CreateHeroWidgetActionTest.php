<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Actions\CreateHeroWidgetAction;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Models\Widget;

it('creates its widget-type blueprint when none exists yet, without an unknown-subject exception', function (): void {
    Blueprint::query()->where('key', WidgetTypeEnum::Hero)->delete();

    $widget = CreateHeroWidgetAction::run();

    $blueprint = Blueprint::query()->findOrFail($widget->blueprint_id);

    expect($blueprint->getRawOriginal('type'))->toBe('widget');
});

it('persists the hero component as a string value without an encoded enum meta payload', function (): void {
    $widget = CreateHeroWidgetAction::run();

    $widget->refresh();

    $meta = json_decode((string) $widget->getRawOriginal('meta'), true, flags: JSON_THROW_ON_ERROR);

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->component)->toBe('capell.widget.hero')
        ->and($widget->component)->toBeString()
        ->and($meta)->not->toHaveKey('component');
});
