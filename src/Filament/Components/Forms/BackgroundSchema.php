<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Core\Models\WidgetAsset;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Model;

class BackgroundSchema
{
    public static function make(string $backgroundName = 'background_image', ?Closure $backgroundCollectionUsing = null): array
    {
        return [
            Group::make()
                ->statePath('meta')
                ->schema([
                    CustomColorInput::make(
                        name: 'background_color',
                        label: __('capell-admin::form.background_color'),
                    ),
                ]),

            MediaLibraryFileUpload::make($backgroundName)
                ->label(__('capell-layout-builder::form.background_image'))
                ->reactive()
                ->columnSpan(['md' => 2])
                ->when(
                    $backgroundCollectionUsing instanceof Closure,
                    fn (SpatieMediaLibraryFileUpload $component): SpatieMediaLibraryFileUpload => $component->collection(
                        fn (SpatieMediaLibraryFileUpload $component): string => $component->evaluate($backgroundCollectionUsing),
                    ),
                ),

            Grid::make(['sm' => 2, 'md' => 3])
                ->visibleJs(<<<JS
                \$get('{$backgroundName}')
            JS)
                ->columnSpanFull()
                ->statePath('meta')
                ->schema([
                    Select::make('background_size')
                        ->label(__('capell-layout-builder::form.background_size'))
                        ->default('cover')
                        ->options([
                            'cover' => __('capell-layout-builder::form.background_cover'),
                            'contain' => __('capell-layout-builder::form.background_contain'),
                        ])
                        ->helperText(self::getHelperText(...)),

                    Select::make('background_position')
                        ->label(__('capell-layout-builder::form.background_position'))
                        ->default('center')
                        ->helperText(self::getHelperText(...))
                        ->options([
                            'center' => __('capell-layout-builder::form.background_center'),
                            'top' => __('capell-layout-builder::form.background_top'),
                            'right' => __('capell-layout-builder::form.background_right'),
                            'bottom' => __('capell-layout-builder::form.background_bottom'),
                            'left' => __('capell-layout-builder::form.background_left'),
                            'top right' => __('capell-layout-builder::form.background_top_right'),
                            'top left' => __('capell-layout-builder::form.background_top_left'),
                            'bottom right' => __('capell-layout-builder::form.background_bottom_right'),
                            'bottom left' => __('capell-layout-builder::form.background_bottom_left'),
                        ]),

                    Select::make('background_repeat')
                        ->label(__('capell-layout-builder::form.background_repeat'))
                        ->default('no-repeat')
                        ->helperText(self::getHelperText(...))
                        ->options([
                            'no-repeat' => __('capell-layout-builder::form.repeat_once'),
                            'repeat' => __('capell-layout-builder::form.repeat_both'),
                            'repeat-x' => __('capell-layout-builder::form.repeat_vertical'),
                            'repeat-y' => __('capell-layout-builder::form.repeat_horizontal'),
                        ]),

                    Select::make('background_attachment')
                        ->label(__('capell-layout-builder::form.background_attachment'))
                        ->helperText(self::getHelperText(...))
                        ->options([
                            'fixed' => __('capell-layout-builder::form.background_fixed'),
                            'scroll' => __('capell-layout-builder::form.background_scroll'),
                        ]),

                    Checkbox::make('background_overlay')
                        ->label(__('capell-layout-builder::form.background_overlay'))
                        ->helperText(__('capell-admin::generic.background_overlay_helper_text')),
                ]),
        ];
    }

    private static function getHelperText(Field $component, ?Model $record): ?string
    {
        if (! $record instanceof Model) {
            return null;
        }

        if (! $record instanceof WidgetAsset) {
            return null;
        }

        $backgroundColor = $record->widget->getMeta($component->getName());

        if (blank($backgroundColor)) {
            return null;
        }

        return __('capell-layout-builder::generic.default_value', ['value' => $backgroundColor]);
    }
}
