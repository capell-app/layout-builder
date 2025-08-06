<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\LayoutContainer;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\ColumnInput;
use Capell\Layout\Filament\Components\Forms\ContainerWidthSelect;
use Capell\Layout\Filament\Components\Forms\HtmlClassInput;
use Capell\Layout\Filament\Components\Forms\MarginSelect;
use Capell\Layout\Filament\Components\Forms\PaddingSelect;
use Capell\Layout\Filament\Components\Forms\SpacingSelect;
use Capell\Layout\Filament\Components\Forms\TagSelect;
use Capell\Layout\Filament\Schemas\AbstractLayoutContainerSchema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class DefaultLayoutContainerSchema extends AbstractLayoutContainerSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Group::make()
                ->statePath('meta')
                ->mutateDehydratedStateUsing(function (array $state): array {
                    if (isset($state['background_image_id'])) {
                        $state['background_image_id'] = FixCuratorMetaDataAction::run($state['background_image_id']);
                    }

                    return $state;
                })
                ->columns()
                ->columnSpanFull()
                ->schema([
                    ColumnInput::make('colspan')
                        ->label(__('capell-admin::form.colspan'))
                        ->helperText(__('capell-admin::generic.colspan_info'))
                        ->default(12),
                    ColumnInput::make('column_start')
                        ->label(__('capell-admin::form.column_start')),
                    Grid::make(['md' => 2])
                        ->columnSpanFull()
                        ->schema([
                            ContainerWidthSelect::make('container'),
                            HtmlClassInput::make('html_class'),
                            PaddingSelect::make('padding'),
                            MarginSelect::make('margin'),
                            SpacingSelect::make('spacing'),
                            TagSelect::make('tag'),
                            TextInput::make('override_columns')
                                ->label(__('capell-admin::form.override_columns'))
                                ->helperText(__('capell-admin::generic.override_columns_info')),
                            BackgroundSettingsFieldset::make()
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}
