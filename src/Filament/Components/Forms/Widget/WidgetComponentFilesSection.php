<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\ComponentSelect;
use Capell\Core\Models;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Models\Widget;
use Filament\Forms;
use Filament\Forms\Get;

class WidgetComponentFilesSection
{
    public static function make(bool $componentRequired = false): Forms\Components\Section
    {
        return Forms\Components\Section::make(function (Get $get, null|Widget|Models\Type $record): string {
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
                Forms\Components\Group::make([
                    ComponentSelect::make('component')
                        ->when($componentRequired, fn (Forms\Components\Select $component): Forms\Components\Select => $component->required())
                        ->setupType(ComponentTypeEnum::Widget),
                    Forms\Components\TextInput::make('view_file')
                        ->label(__('capell-admin::form.component_view_file'))
                        ->helperText(__('capell-admin::generic.component_view_file_info')),
                ]),

                ComponentSelect::make('component_item')
                    ->label(__('capell-admin::form.component_item'))
                    ->when($componentRequired, fn (Forms\Components\Select $component): Forms\Components\Select => $component->required())
                    ->setupType(ComponentTypeEnum::Asset, hintLanguage: 'capell-admin::generic.component_item_info'),
            ]);
    }
}
