<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Containers;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaExtenderEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Components\Forms\BackgroundSchema;
use Capell\Layout\Filament\Components\Forms\ColumnInput;
use Capell\Layout\Filament\Components\Forms\ContainerWidthSelect;
use Capell\Layout\Filament\Components\Forms\HtmlClassInput;
use Capell\Layout\Filament\Components\Forms\MarginSelect;
use Capell\Layout\Filament\Components\Forms\PaddingSelect;
use Capell\Layout\Filament\Components\Forms\SpacingSelect;
use Capell\Layout\Filament\Components\Forms\TagSelect;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DefaultLayoutContainerSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = SchemaTypeEnum::LayoutContainer;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutContainer->value);
    }

    public function make(Schema $schema): array
    {
        return [
            Section::make(__('capell-layout::generic.container_settings'))
                ->statePath('meta')
                ->collapsed()
                ->columnSpanFull()
                ->columns(['sm' => 2, 'md' => 3])
                ->schema([
                    ColumnInput::make('colspan')
                        ->label(__('capell-layout::form.colspan'))
                        ->helperText(__('capell-admin::generic.colspan_info'))
                        ->default(12),
                    ColumnInput::make('column_start')
                        ->label(__('capell-layout::form.column_start')),
                    ContainerWidthSelect::make(),
                    HtmlClassInput::make('html_class'),
                    PaddingSelect::make('padding'),
                    MarginSelect::make('margin'),
                    SpacingSelect::make('spacing'),
                    TagSelect::make('tag'),
                    TextInput::make('override_columns')
                        ->label(__('capell-layout::form.override_columns'))
                        ->helperText(__('capell-admin::generic.override_columns_info')),
                ]),
            Section::make(__('capell-admin::generic.background'))
                ->collapsed()
                ->columnSpanFull()
                ->columns(['sm' => 2, 'md' => 3])
                ->schema(
                    BackgroundSchema::make(
                        backgroundCollectionUsing: fn (Get $get): string => $get('key') . '-background',
                    ),
                ),
        ];
    }
}
