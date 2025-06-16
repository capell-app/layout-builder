<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Admin\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetAssetsRepeater;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Forms;

class AssetsWidgetSchema extends AbstractWidgetSchema
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

    private static function getCreateOptionSchema(Forms\Form $form): array
    {
        return [
            WidgetAssetsRepeater::make($form),
        ];
    }

    private static function getEditFormSchema(Forms\Form $form): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    Forms\Components\Section::make(__('capell-admin::generic.widget_resources'))
                        ->description(__('capell-admin::generic.widget_resources_info'))
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
            Forms\Components\Tabs::make('tabs')
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make(__('capell-admin::tab.content'))
                        ->schema([
                            WidgetTranslationsRepeater::make($form->getOperation()),
                        ]),
                    WidgetSettingsTab::make([
                        Forms\Components\Grid::make()
                            ->statePath('meta')
                            ->mutateDehydratedStateUsing(function (array $state): array {
                                if (isset($state['background_image_id'])) {
                                    $state['background_image_id'] = FixCuratorMetaDataAction::run($state['background_image_id']);
                                }

                                return $state;
                            })
                            ->schema([
                                Forms\Components\Grid::make(['default' => 2, 'xl' => 3])
                                    ->schema([
                                        ColorSchemeComponent::make('color_scheme'),
                                        BackgroundSettingsFieldset::make(),
                                    ]),

                                Forms\Components\Fieldset::make(__('capell-admin::generic.carousel_options'))
                                    ->columns(['default' => 2, 'xl' => 3])
                                    ->schema(CarouselSettingsSchema::make()),
                                WidgetDisplaySection::make(),
                                WidgetComponentFilesSection::make(),
                            ]),
                    ]),

                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
