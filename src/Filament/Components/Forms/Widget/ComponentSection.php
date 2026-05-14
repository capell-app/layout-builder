<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\ComponentSelect;
use Capell\Core\LayoutBuilder\Enums\ComponentTypeEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class ComponentSection
{
    public static function make(bool $componentRequired = false): Section
    {
        return Section::make(__('capell-layout-builder::generic.widget_files_description'))
            ->icon(Heroicon::PuzzlePiece)
            ->collapsed()
            ->compact()
            ->columns()
            ->columnSpanFull()
            ->schema([
                Group::make([
                    Checkbox::make('livewire')
                        ->label(__('capell-layout-builder::form.livewire_component')),
                    ComponentSelect::make('component')
                        ->when($componentRequired, fn (Select $component): Select => $component->required())
                        ->setupType(ComponentTypeEnum::Widget),
                    TextInput::make('view_file')
                        ->label(__('capell-layout-builder::form.component_view_file'))
                        ->helperText(__('capell-admin::generic.component_view_file_info')),
                ]),
                ComponentSelect::make('component_item')
                    ->label(__('capell-admin::form.component_item'))
                    ->when($componentRequired, fn (Select $component): Select => $component->required())
                    ->setupType(ComponentTypeEnum::Asset, hintLanguage: 'capell-admin::generic.component_item_info'),
            ]);
    }
}
