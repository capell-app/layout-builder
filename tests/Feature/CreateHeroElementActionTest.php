<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\CreateHeroElementAction;
use Capell\LayoutBuilder\Models\Element;

it('persists the hero component as a string value without an encoded enum meta payload', function (): void {
    $element = CreateHeroElementAction::run();

    $element->refresh();
    $meta = json_decode((string) $element->getRawOriginal('meta'), true, flags: JSON_THROW_ON_ERROR);

    expect($element)->toBeInstanceOf(Element::class)
        ->and($element->component)->toBe('capell.element.hero')
        ->and($element->component)->toBeString()
        ->and($meta)->not->toHaveKey('component');
});
