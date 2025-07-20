<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetAssetsRepeater;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class HeroWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return [
            ...match ($operation) {
                'create', 'createOption', 'replicate' => self::getCreateOptionSchema($schema),
                default => self::getEditFormSchema($schema),
            },
        ];
    }

    protected static function getCreateOptionSchema(Schema $schema): array
    {
        return [
            WidgetAssetsRepeater::make($schema),
            ...static::getMetaSchema(),
        ];
    }

    protected static function getEditFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    Section::make(__('capell-admin::generic.widget_assets'))
                        ->description(__('capell-admin::generic.widget_assets_info'))
                        ->compact()
                        ->schema([
                            WidgetAssetsRepeater::make($schema)
                                ->hiddenLabel(),
                        ]),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->columns(1)
                        ->schema(WidgetSettingsSchema::make($schema)),
                ]),
            self::getTabs($schema),
        ];
    }

    protected static function getTabs(Schema $schema): Tabs
    {
        return Tabs::make('tabs')
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('capell-admin::tab.content'))
                    ->schema([
                        WidgetTranslationsRepeater::make($schema),
                    ]),
                WidgetDisplayTab::make([
                    Grid::make()
                        ->statePath('meta')
                        ->mutateDehydratedStateUsing(function (array $state): array {
                            if (isset($state['background_image_id'])) {
                                $state['background_image_id'] = FixCuratorMetaDataAction::run($state['background_image_id']);
                            }

                            return $state;
                        })
                        ->schema([
                            ...self::getMetaSchema(),
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
