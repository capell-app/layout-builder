<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Admin\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetAssetsRepeater;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Forms;

class HeroWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        $operation = $form->getOperation();

        return [
            ...match ($operation) {
                'create', 'createOption', 'replicate' => self::getCreateOptionSchema($form),
                default => self::getEditFormSchema($form),
            },
        ];
    }

    protected static function getCreateOptionSchema(Forms\Form $form): array
    {
        return [
            WidgetAssetsRepeater::make($form),
            ...static::getMetaSchema(),
        ];
    }

    protected static function getEditFormSchema(Forms\Form $form): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    Forms\Components\Section::make(__('capell-admin::generic.widget_assets'))
                        ->description(__('capell-admin::generic.widget_assets_info'))
                        ->compact()
                        ->schema([
                            WidgetAssetsRepeater::make($form)
                                ->hiddenLabel(),
                        ]),
                ])
                ->sidebarSchema([
                    Forms\Components\Section::make()
                        ->columns(1)
                        ->schema(WidgetSettingsSchema::make($form)),
                ]),
            self::getTabs($form),
        ];
    }

    protected static function getTabs(Forms\Form $form): Forms\Components\Tabs
    {
        return Forms\Components\Tabs::make('tabs')
            ->columnSpanFull()
            ->tabs([
                Forms\Components\Tabs\Tab::make(__('capell-admin::tab.content'))
                    ->schema([
                        WidgetTranslationsRepeater::make($form),
                    ]),
                WidgetDisplayTab::make([
                    Forms\Components\Grid::make()
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
            Forms\Components\Grid::make(['default' => 2, 'xl' => 3])
                ->schema([
                    ColorSchemeComponent::make('color_scheme'),
                    Forms\Components\Select::make('height')
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

            Forms\Components\Fieldset::make(__('capell-admin::generic.carousel_options'))
                ->columns(['default' => 2, 'xl' => 3])
                ->schema(CarouselSettingsSchema::make()),
        ];
    }
}
