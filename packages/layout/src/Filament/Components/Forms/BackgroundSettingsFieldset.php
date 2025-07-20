<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Admin\Filament\Components\Forms\Media\ImageMediaPicker;
use Capell\Layout\Models\WidgetAsset;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

class BackgroundSettingsFieldset
{
    public static function make(): Fieldset
    {
        return Fieldset::make(__('capell-admin::form.background_settings'))
            ->schema([
                Grid::make()
                    ->columnSpan(1)
                    ->schema([
                        CustomColorInput::make(
                            name: 'background_color',
                            label: __('capell-admin::form.background_color'),
                        ),

                        Grid::make()
                            ->visible(fn (Get $get): bool => (bool) $get('background_image_id'))
                            ->columnSpanFull()
                            ->schema([
                                Select::make('background_size')
                                    ->label(__('capell-admin::form.background_size'))
                                    ->default('cover')
                                    ->options([
                                        'cover' => __('capell-admin::form.background_cover'),
                                        'contain' => __('capell-admin::form.background_contain'),
                                    ])
                                    ->helperText(self::getHelperText(...)),

                                Select::make('background_position')
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

                                Select::make('background_repeat')
                                    ->label(__('capell-admin::form.background_repeat'))
                                    ->default('no-repeat')
                                    ->helperText(self::getHelperText(...))
                                    ->options([
                                        'no-repeat' => __('capell-admin::form.repeat_once'),
                                        'repeat' => __('capell-admin::form.repeat_both'),
                                        'repeat-x' => __('capell-admin::form.repeat_vertical'),
                                        'repeat-y' => __('capell-admin::form.repeat_horizontal'),
                                    ]),

                                Select::make('background_attachment')
                                    ->label(__('capell-admin::form.background_attachment'))
                                    ->helperText(self::getHelperText(...))
                                    ->options([
                                        'fixed' => __('capell-admin::form.background_fixed'),
                                        'scroll' => __('capell-admin::form.background_scroll'),
                                    ]),

                                Checkbox::make('background_overlay')
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

    private static function getHelperText(Field $component, ?Model $record): ?string
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
