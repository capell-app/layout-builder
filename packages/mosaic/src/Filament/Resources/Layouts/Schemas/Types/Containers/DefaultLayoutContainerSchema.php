<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Layouts\Schemas\Types\Containers;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Mosaic\Enums\SchemaExtenderEnum;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Components\Forms\BackgroundSchema;
use Capell\Mosaic\Filament\Components\Forms\ColumnInput;
use Capell\Mosaic\Filament\Components\Forms\ContainerWidthSelect;
use Capell\Mosaic\Filament\Components\Forms\HtmlClassInput;
use Capell\Mosaic\Filament\Components\Forms\MarginSelect;
use Capell\Mosaic\Filament\Components\Forms\PaddingSelect;
use Capell\Mosaic\Filament\Components\Forms\SpacingSelect;
use Capell\Mosaic\Filament\Components\Forms\TagSelect;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DefaultLayoutContainerSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = TypeSchemaEnum::LayoutContainer;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutContainer->value);
    }

    public function make(Schema $schema): array
    {
        return [
            Section::make(__('capell-admin::generic.settings'))
                ->statePath('meta')
                ->collapsed()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->columnSpanFull()
                ->columns(['sm' => 2, 'md' => 3])
                ->schema([
                    ColumnInput::make('colspan')
                        ->label(__('capell-mosaic::form.colspan'))
                        ->helperText(__('capell-admin::generic.colspan_info'))
                        ->default(12),
                    ColumnInput::make('column_start')
                        ->label(__('capell-mosaic::form.column_start')),
                    ContainerWidthSelect::make(),
                    HtmlClassInput::make('html_class'),
                    PaddingSelect::make('padding'),
                    MarginSelect::make('margin'),
                    SpacingSelect::make('spacing')
                        ->helperText(__('capell-admin::generic.container_spacing_help')),
                    TagSelect::make('tag'),
                    TextInput::make('override_columns')
                        ->label(__('capell-mosaic::form.override_columns'))
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
