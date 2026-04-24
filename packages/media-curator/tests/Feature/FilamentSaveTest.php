<?php

declare(strict_types=1);

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Capell\Core\Contracts\Media\MediaFieldFactory;
use Capell\MediaCurator\Filament\Components\CuratorMediaFieldFactory;

test('factory make returns a CuratorPicker field instance', function (): void {
    $factory = app(MediaFieldFactory::class);

    $field = $factory->make('image');

    expect($field)->toBeInstanceOf(CuratorPicker::class);
});

test('MediaFieldFactory contract resolves to CuratorMediaFieldFactory when plugin registered', function (): void {
    $factory = app(MediaFieldFactory::class);

    expect($factory)->toBeInstanceOf(CuratorMediaFieldFactory::class);
});

test('capell media backend config key is set to curator', function (): void {
    expect(config('capell.media.backend'))->toBe('curator');
});
