<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Capell\Layout\Livewire\Filament\WidgetAssetsTable;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class HeroWidgetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    protected static string $schemaType = SchemaEnum::Widget->value;

    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return [
            ...match ($operation) {
                'editOption', 'createOption', 'replicate' => static::getOptionSchema($schema),
                default => static::getFormSchema($schema),
            },
        ];
    }

    protected static function getOptionSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            Livewire::make(WidgetAssetsTable::class, ['schema' => $schema])
                ->key('widget-assets-table'),
            ...static::getMetaSchema(),
        ];
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    Livewire::make(WidgetAssetsTable::class, ['schema' => $schema])
                        ->key('widget-assets-table'),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema(WidgetSettingsSchema::make($schema)),
                ]),
            static::getTabs($schema),
        ];
    }

    protected static function getTabs(Schema $schema): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('capell-admin::tab.content'))
                    ->schema([
                        WidgetTranslationsRepeater::make($schema),
                    ]),
                WidgetDisplayTab::make([
                    Grid::make()
                        ->statePath('meta')
                        ->schema([
                            ...static::getMetaSchema(),
                            WidgetComponentFilesSection::make(),
                        ]),
                ]),
                WidgetAdminTab::make(),
            ]);
    }

    protected static function getMetaSchema(): array
    {
        return [
            Grid::make(['default' => 2, 'xl' => 3])
                ->schema([
                    ColorSchemeComponent::make('color_scheme'),
                    Select::make('height')
                        ->label(__('capell-admin::form.height'))
                        ->options([
                            'small' => __('capell-admin::generic.small'),
                            'medium' => __('capell-admin::generic.medium'),
                            'large' => __('capell-admin::generic.large'),
                            'full' => __('capell-admin::generic.full'),
                        ])
                        ->default('medium')
                        ->required(),
                    BackgroundSettingsFieldset::make(),
                ]),

            Fieldset::make(__('capell-admin::generic.carousel_options'))
                ->columns(['default' => 2, 'xl' => 3])
                ->schema(CarouselSettingsSchema::make()),
        ];
    }
}
