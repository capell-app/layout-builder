<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class CarouselSettingsSchema
{
    public static function make(): array
    {
        return [
            Checkbox::make('carousel_fade')
                ->label(__('capell-admin::form.carousel_fade')),
            Checkbox::make('carousel_arrows')
                ->label(__('capell-admin::form.carousel_arrows')),
            Checkbox::make('carousel_pagination')
                ->label(__('capell-admin::form.carousel_pagination')),
            Checkbox::make('carousel_loop')
                ->label(__('capell-admin::form.carousel_loop')),
            Checkbox::make('lightbox')
                ->label(__('capell-admin::form.lightbox')),
            Checkbox::make('carousel_auto')
                ->label(__('capell-admin::form.carousel_auto'))
                ->reactive(),
            TextInput::make('carousel_auto_delay')
                ->label(__('capell-admin::form.carousel_auto_delay'))
                ->inlineLabel()
                ->suffix(__('capell-admin::generic.milliseconds'))
                ->default(5000)
                ->placeholder('5000')
                ->visible((fn (Get $get): bool => $get('carousel_auto'))),
        ];
    }
}
