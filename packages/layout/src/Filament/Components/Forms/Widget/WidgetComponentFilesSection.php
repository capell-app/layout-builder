<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\ComponentSelect;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Models\Widget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class WidgetComponentFilesSection
{
    public static function make(bool $componentRequired = false): Section
    {
        return Section::make(function (Get $get, null|Widget|Type $record): string {
            if ($record === null) {
                return '';
            }

            $name = $record instanceof Widget ? $record->type->name : $record->name;

            return __('capell-admin::generic.widget_files_description', ['name' => $name]);
        })
            ->icon('heroicon-o-puzzle-piece')
            ->collapsed()
            ->compact()
            ->columns()
            ->columnSpanFull()
            ->schema([
                Group::make([
                    ComponentSelect::make('component')
                        ->when($componentRequired, fn (Select $component): Select => $component->required())
                        ->setupType(ComponentTypeEnum::Widget),
                    TextInput::make('view_file')
                        ->label(__('capell-admin::form.component_view_file'))
                        ->helperText(__('capell-admin::generic.component_view_file_info')),
                ]),

                ComponentSelect::make('component_item')
                    ->label(__('capell-admin::form.component_item'))
                    ->when($componentRequired, fn (Select $component): Select => $component->required())
                    ->setupType(ComponentTypeEnum::Asset, hintLanguage: 'capell-admin::generic.component_item_info'),
            ]);
    }
}
