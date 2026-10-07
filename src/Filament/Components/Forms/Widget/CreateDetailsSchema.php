<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class CreateDetailsSchema
{
    public static function make(Schema $configurator): Grid
    {
        return Grid::make()
            ->visibleOn(['create', 'createOption', 'replicate'])
            ->schema(self::getConfigurator($configurator))
            ->columnSpanFull();
    }

    /**
     * @return array<int, Component | Action | ActionGroup | string | Htmlable>
     */
    private static function getConfigurator(Schema $configurator): array
    {
        return [
            Grid::make()
                ->columnSpanFull()
                ->schema([
                    NameInput::make('name')
                        ->withTitleUpdater(),
                    TypeSelect::make('blueprint_id')
                        ->live()
                        ->withRelation()
                        ->when(
                            $configurator->isCreating(),
                            fn (TypeSelect $component): TypeSelect => $component->withCreateForm(),
                            fn (TypeSelect $component): TypeSelect => $component->withEditForm(),
                        ),
                ]),
        ];
    }
}
