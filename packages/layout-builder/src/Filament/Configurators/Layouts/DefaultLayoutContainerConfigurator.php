<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Layouts;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\ContainerAlignmentEnum;
use Capell\LayoutBuilder\Enums\ResponsiveVisibilityEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\BackgroundSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\ColumnInput;
use Capell\LayoutBuilder\Filament\Components\Forms\ContainerWidthSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\HtmlClassInput;
use Capell\LayoutBuilder\Filament\Components\Forms\MarginSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\PaddingSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\SpacingSelect;
use Capell\LayoutBuilder\Filament\Components\Forms\TagSelect;
use Filament\FormBuilder\Components\CheckboxList;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DefaultLayoutContainerConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::LayoutContainer;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutContainer->value);
    }

    public function make(Schema $configurator): array
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
                        ->label(__('capell-layout-builder::form.colspan'))
                        ->helperText(__('capell-admin::generic.colspan_info'))
                        ->default(12),
                    ColumnInput::make('column_start')
                        ->label(__('capell-layout-builder::form.column_start')),
                    ContainerWidthSelect::make(),
                    Select::make('alignment')
                        ->label(__('capell-layout-builder::form.alignment'))
                        ->options(ContainerAlignmentEnum::class)
                        ->default(ContainerAlignmentEnum::Stretch->value),
                    CheckboxList::make('hidden_on')
                        ->label(__('capell-layout-builder::form.hide_on'))
                        ->options(ResponsiveVisibilityEnum::class)
                        ->default([])
                        ->columns(3),
                    HtmlClassInput::make('html_class'),
                    PaddingSelect::make('padding'),
                    MarginSelect::make('margin'),
                    SpacingSelect::make('spacing')
                        ->helperText(__('capell-admin::generic.container_spacing_help')),
                    TagSelect::make('tag'),
                    TextInput::make('override_columns')
                        ->label(__('capell-layout-builder::form.override_columns'))
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
