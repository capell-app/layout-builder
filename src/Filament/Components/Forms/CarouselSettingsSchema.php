<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms;
use Filament\Forms\Get;

class CarouselSettingsSchema
{
    public static function make(): array
    {
        return [
            Forms\Components\Checkbox::make('carousel_fade')
                ->label(__('capell-admin::form.carousel_fade')),
            Forms\Components\Checkbox::make('carousel_arrows')
                ->label(__('capell-admin::form.carousel_arrows')),
            Forms\Components\Checkbox::make('carousel_pagination')
                ->label(__('capell-admin::form.carousel_pagination')),
            Forms\Components\Checkbox::make('carousel_loop')
                ->label(__('capell-admin::form.carousel_loop')),
            Forms\Components\Checkbox::make('lightbox')
                ->label(__('capell-admin::form.lightbox')),
            Forms\Components\Checkbox::make('carousel_auto')
                ->label(__('capell-admin::form.carousel_auto'))
                ->reactive(),
            Forms\Components\TextInput::make('carousel_auto_delay')
                ->label(__('capell-admin::form.carousel_auto_delay'))
                ->inlineLabel()
                ->suffix(__('capell-admin::generic.milliseconds'))
                ->default(5000)
                ->placeholder('5000')
                ->visible((fn (Get $get): bool => $get('carousel_auto'))),
        ];
    }
}
