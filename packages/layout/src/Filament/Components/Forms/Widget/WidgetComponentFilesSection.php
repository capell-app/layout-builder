<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
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
            ->icon('heroicon-o-paper-clip')
            ->collapsed()
            ->compact()
            ->columns(['lg' => 2, '2xl' => 3])
            ->columnSpanFull()
            ->schema([
                Forms\Components\Select::make('component')
                    ->label(__('capell-admin::form.component'))
                    ->searchable()
                    ->reactive()
                    ->preload()
                    ->when($componentRequired, fn (Forms\Components\Select $component): Forms\Components\Select => $component->required())
                    ->options(function (null|Widget|Models\Type $record): array {
                        if ($record === null) {
                            return [];
                        }

                        return CapellCore::getComponents('widget');
                    }),

                Forms\Components\TextInput::make('view_file')
                    ->label(__('capell-admin::form.component_view_file'))
                    ->helperText(__('capell-admin::generic.component_view_file_info')),

                Forms\Components\Select::make('component_item')
                    ->label(__('capell-admin::form.component_item'))
                    ->helperText(__('capell-admin::generic.component_item_info'))
                    ->options(
                        fn (null|Widget|Models\Type $record): array => CapellCore::getComponents('resource')
                    ),
            ]);
    }
}
