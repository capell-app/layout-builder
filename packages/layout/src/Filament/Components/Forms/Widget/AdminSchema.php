<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Filament\Components\Forms\AssetTypeSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\SchemaSelect;
use Capell\Layout\Enums\TypeSchemaEnum;
use Capell\Layout\Enums\WidgetSchemaEnum;
use Capell\Layout\Models\Widget;
use Filament\Schemas\Components\Fieldset;

class AdminSchema
{
    public static function make(): array
    {
        return [
            SchemaSelect::make('schema')
                ->default(fn (): string => WidgetSchemaEnum::Default->name)
                ->setupOptions(TypeSchemaEnum::Widget),

            SchemaSelect::make('layout_widget_schema')
                ->label(__('capell-layout::form.layout_widget_schema'))
                ->helperText(__('capell-layout::generic.layout_widget_schema_info'))
                ->setupOptions(TypeSchemaEnum::LayoutWidget),

            IconPicker::make('icon')
                ->label(__('capell-admin::form.admin_icon')),

            MediaLibraryFileUpload::make('image'),

            Fieldset::make(__('capell-admin::generic.assets'))
                ->gridContainer()
                ->columns(['lg' => null, '@lg' => 2])
                ->columnSpanFull()
                ->visible(fn (?Widget $record): bool => isset($record->type?->admin['asset_types']) && $record->type->admin['asset_types'] !== [])
                ->schema([
                    SchemaSelect::make('widget_asset_schema')
                        ->label(__('capell-layout::form.widget_asset_schema'))
                        ->helperText(__('capell-layout::generic.widget_asset_schema_info'))
                        ->setupOptions(TypeSchemaEnum::WidgetAsset),

                    AssetTypeSelect::make('asset_types')
                        ->label(__('capell-admin::form.asset_type'))
                        ->multiple(),
                ]),
        ];
    }
}
