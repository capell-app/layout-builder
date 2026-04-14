<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

class CarouselSettingsSchema
{
    public static function make(): array
    {
        return [
            Checkbox::make('carousel_fade')
                ->label(__('capell-layout::form.carousel_fade')),
            Checkbox::make('carousel_arrows')
                ->label(__('capell-layout::form.carousel_arrows')),
            Checkbox::make('carousel_pagination')
                ->label(__('capell-layout::form.carousel_pagination')),
            Checkbox::make('carousel_loop')
                ->label(__('capell-layout::form.carousel_loop')),
            Checkbox::make('carousel_rewind')
                ->label(__('capell-layout::form.carousel_rewind'))
                ->visible(fn (Get $get): bool => ! (bool) $get('carousel_loop')),
            Checkbox::make('carousel_drag')
                ->label(__('capell-layout::form.carousel_drag')),
            Checkbox::make('carousel_touch')
                ->label(__('capell-layout::form.carousel_touch')),
            Checkbox::make('carousel_wheel')
                ->label(__('capell-layout::form.carousel_wheel')),
            Checkbox::make('lightbox')
                ->label(__('capell-layout::form.lightbox')),
            Checkbox::make('carousel_auto_play')
                ->label(__('capell-layout::form.carousel_auto_play'))
                ->reactive(),
            Checkbox::make('carousel_pause_on_hover')
                ->label(__('capell-layout::form.carousel_pause_on_hover'))
                ->visible(fn (Get $get): bool => $get('carousel_auto_play')),
            Checkbox::make('carousel_disable_on_interaction')
                ->label(__('capell-layout::form.carousel_disable_on_interaction'))
                ->visible(fn (Get $get): bool => $get('carousel_auto_play')),
            Grid::make(3)
                ->schema([
                    TextInput::make('carousel_auto_delay')
                        ->label(__('capell-layout::form.carousel_auto_delay'))
                        ->inlineLabel()
                        ->suffix(__('capell-admin::generic.milliseconds'))
                        ->default(5000)
                        ->placeholder('5000')
                        ->visible(fn (Get $get): bool => $get('carousel_auto_play')),
                    TextInput::make('carousel_speed')
                        ->label(__('capell-layout::form.carousel_speed'))
                        ->inlineLabel()
                        ->suffix(__('capell-admin::generic.milliseconds'))
                        ->default(300)
                        ->placeholder('300'),
                    AlignSelect::make('carousel_align')
                        ->label(__('capell-layout::form.carousel_align')),
                ]),
        ];
    }
}
