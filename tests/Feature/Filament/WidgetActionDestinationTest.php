<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\ActionsRepeater;
use Capell\Tests\Support\SchemaStateHarness;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

it('clears incompatible destinations when an editor changes the widget action type', function (): void {
    $page = Page::factory()->create(['name' => 'Delivery terms']);
    $repeater = ActionsRepeater::make('actions');
    $schema = Schema::make(new SchemaStateHarness)->statePath('data')->components([$repeater]);
    $schema->fill(['actions' => [[
        'type' => ActionLinkEnum::Page->value,
        'pageable_type' => $page->getMorphClass(),
        'pageable_id' => $page->getKey(),
    ]]]);

    $key = array_key_first($repeater->getChildSchemas());
    assert(is_string($key));

    $item = $repeater->getChildSchema($key);
    $type = $item?->getComponent('type');
    expect($type)->toBeInstanceOf(ToggleButtons::class);
    assert($type instanceof ToggleButtons);
    $type->state(ActionLinkEnum::Link)->callAfterStateUpdated();

    expect(data_get($repeater->getRawState(), $key . '.pageable_id'))->toBeNull();

    $url = $item->getComponent('url', withHidden: true);
    assert($url instanceof TextInput);
    $url->state('https://example.com/delivery');

    $type->state(ActionLinkEnum::VideoPopup)->callAfterStateUpdated();
    expect(data_get($repeater->getRawState(), $key . '.url'))->toBeNull();

    $video = $item->getComponent('video_url', withHidden: true);
    assert($video instanceof TextInput);
    $video->state('https://example.com/video');

    $type->state(ActionLinkEnum::Page)->callAfterStateUpdated();
    expect(data_get($repeater->getRawState(), $key . '.video_url'))->toBeNull();
});
