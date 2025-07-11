<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Admin\Filament\Components\Forms\CustomColorInput;
use Capell\Admin\Filament\Components\Forms\ImageMediaPicker;
use Capell\Layout\Models\WidgetAsset;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

class BackgroundSettingsFieldset
{
    public static function make(): Forms\Components\Fieldset
    {
        return Forms\Components\Fieldset::make(__('capell-admin::form.background_settings'))
            ->schema([
                Forms\Components\Grid::make()
                    ->columnSpan(1)
                    ->schema([
                        CustomColorInput::make(
                            name: 'background_color',
                            label: __('capell-admin::form.background_color'),
                        ),

                        Forms\Components\Grid::make()
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('background_image_id'))
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\Select::make('background_size')
                                    ->label(__('capell-admin::form.background_size'))
                                    ->default('cover')
                                    ->options([
                                        'cover' => __('capell-admin::form.background_cover'),
                                        'contain' => __('capell-admin::form.background_contain'),
                                    ])
                                    ->helperText(self::getHelperText(...)),

                                Forms\Components\Select::make('background_position')
                                    ->label(__('capell-admin::form.background_position'))
                                    ->default('center')
                                    ->helperText(self::getHelperText(...))
                                    ->options([
                                        'center' => __('capell-admin::form.background_center'),
                                        'top' => __('capell-admin::form.background_top'),
                                        'right' => __('capell-admin::form.background_right'),
                                        'bottom' => __('capell-admin::form.background_bottom'),
                                        'left' => __('capell-admin::form.background_left'),
                                        'top right' => __('capell-admin::form.background_top_right'),
                                        'top left' => __('capell-admin::form.background_top_left'),
                                        'bottom right' => __('capell-admin::form.background_bottom_right'),
                                        'bottom left' => __('capell-admin::form.background_bottom_left'),
                                    ]),

                                Forms\Components\Select::make('background_repeat')
                                    ->label(__('capell-admin::form.background_repeat'))
                                    ->default('no-repeat')
                                    ->helperText(self::getHelperText(...))
                                    ->options([
                                        'no-repeat' => __('capell-admin::form.repeat_once'),
                                        'repeat' => __('capell-admin::form.repeat_both'),
                                        'repeat-x' => __('capell-admin::form.repeat_vertical'),
                                        'repeat-y' => __('capell-admin::form.repeat_horizontal'),
                                    ]),

                                Forms\Components\Select::make('background_attachment')
                                    ->label(__('capell-admin::form.background_attachment'))
                                    ->helperText(self::getHelperText(...))
                                    ->options([
                                        'fixed' => __('capell-admin::form.background_fixed'),
                                        'scroll' => __('capell-admin::form.background_scroll'),
                                    ]),

                                Forms\Components\Checkbox::make('background_overlay')
                                    ->label(__('capell-admin::form.background_overlay'))
                                    ->helperText(__('capell-admin::generic.background_overlay_helper_text')),
                            ]),
                    ]),
                ImageMediaPicker::make('background_image_id')
                    ->reactive()
                    ->dehydrateStateUsing(function (ImageMediaPicker $component, $state): ?string {
                        if (! $state) {
                            return null;
                        }

                        $imageId = is_array($state) ? collect($state)->first()['id'] : $state;

                        $component->state($imageId);

                        return $imageId;
                    })
                    ->label(__('capell-admin::form.background_image')),
            ]);
    }

    private static function getHelperText(Forms\Components\Field $component, ?Model $record): ?string
    {
        if (! $record instanceof Model) {
            return null;
        }

        if (! $record instanceof WidgetAsset) {
            return null;
        }

        if (! $backgroundColor = $record->widget->getMeta($component->getName())) {
            return null;
        }

        return __('capell-admin::generic.default_value', ['value' => $backgroundColor]);
    }
}
