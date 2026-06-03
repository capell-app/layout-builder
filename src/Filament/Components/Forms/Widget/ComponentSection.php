<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\ComponentSelect;
use Capell\LayoutBuilder\Enums\ComponentTypeEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;

class ComponentSection
{
    public static function make(bool $componentRequired = false): Group
    {
        return Group::make()
            ->columnSpanFull()
            ->schema([
                Callout::make(__('capell-layout-builder::generic.widget_rendering_components'))
                    ->description(__('capell-layout-builder::generic.widget_rendering_callout_description'))
                    ->info(),
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        Checkbox::make('is_livewire')
                            ->label(__('capell-layout-builder::form.livewire_component'))
                            ->columnSpanFull(),
                        ComponentSelect::make('component')
                            ->when($componentRequired, fn (Select $component): Select => $component->required())
                            ->setupType(ComponentTypeEnum::Widget, hintLanguage: 'capell-layout-builder::generic.widget_component_info')
                            ->withCreateComponentAction(),
                        ComponentSelect::make('component_item')
                            ->label(__('capell-admin::form.component_item'))
                            ->when($componentRequired, fn (Select $component): Select => $component->required())
                            ->setupType(ComponentTypeEnum::Asset, hintLanguage: 'capell-layout-builder::generic.widget_component_item_info')
                            ->withCreateComponentAction(),
                        TextInput::make('view_file')
                            ->label(__('capell-layout-builder::form.component_view_file'))
                            ->helperText(__('capell-layout-builder::generic.component_view_file_info'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
