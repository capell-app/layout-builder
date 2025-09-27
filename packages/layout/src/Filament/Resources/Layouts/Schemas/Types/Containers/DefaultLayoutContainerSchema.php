<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Containers;

use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaExtenderEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\ColumnInput;
use Capell\Layout\Filament\Components\Forms\ContainerWidthSelect;
use Capell\Layout\Filament\Components\Forms\HtmlClassInput;
use Capell\Layout\Filament\Components\Forms\MarginSelect;
use Capell\Layout\Filament\Components\Forms\PaddingSelect;
use Capell\Layout\Filament\Components\Forms\SpacingSelect;
use Capell\Layout\Filament\Components\Forms\TagSelect;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class DefaultLayoutContainerSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static string $schemaType = SchemaTypeEnum::LayoutContainer->value;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutContainer->value);
    }

    public function make(Schema $schema): array
    {
        return [
            Group::make()
                ->statePath('meta')
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
