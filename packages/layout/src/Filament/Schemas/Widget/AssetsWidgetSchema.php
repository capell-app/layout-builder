<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\ImageMediaPicker;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
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

    protected static function getCreateOptionSchema(Forms\Form $form): array
    {
        return [
            WidgetAssetsRepeater::make($form),
        ];
    }

    protected static function getEditFormSchema(Forms\Form $form): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema(self::getMainSchema($form))
                ->sidebarSchema(self::getSidebarSchema($form)),
            self::getTabs($form),
        ];
    }

    protected static function getMainSchema(Forms\Form $form): array
    {
        return [
            Forms\Components\Section::make(__('capell-admin::generic.widget_resources'))
                ->description(__('capell-admin::generic.widget_resources_info'))
                ->compact()
                ->schema([
                    WidgetAssetsRepeater::make($form)
                        ->hiddenLabel(),
                ]),
        ];
    }

    protected static function getSidebarSchema(Forms\Form $form): array
    {
        return [
            Forms\Components\Section::make()
                ->columns(1)
                ->schema([
                    ...WidgetSettingsSchema::make($form),
                    Forms\Components\Group::make()
                        ->statePath('meta')
                        ->mutateDehydratedStateUsing(function (array $state): array {
                            if (! empty($state['image_id'])) {
                                $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                            }

                            return $state;
                        })
                        ->schema([
                            ImageMediaPicker::make('image_id'),
                        ]),
                ]),
        ];
    }

    protected static function getTabs(Forms\Form $form): Forms\Components\Tabs
    {
        return Forms\Components\Tabs::make('tabs')
            ->columnSpanFull()
            ->tabs([
                static::getContentTab($form),
                static::getSettingsTab($form),
                static::getAdminTab($form),
            ]);
    }

    protected static function getContentTab(Forms\Form $form): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('capell-admin::tab.content'))
            ->schema([
                WidgetTranslationsRepeater::make($form),
            ]);
    }

    protected static function getAdminTab(Forms\Form $form): Forms\Components\Tabs\Tab
    {
        return WidgetAdminTab::make();
    }

    protected static function getSettingsTab(Forms\Form $form): Forms\Components\Tabs\Tab
    {
        return WidgetDisplayTab::make([
            Forms\Components\Grid::make()
                ->statePath('meta')
                ->mutateDehydratedStateUsing(function (array $state): array {
                    if (! empty($state['background_image_id'])) {
                        $state['background_image_id'] = FixCuratorMetaDataAction::run($state['background_image_id']);
                    }

                    return $state;
                })
                ->schema([
                    WidgetDisplaySection::make([
                        ColorSchemeComponent::make('color_scheme'),
                    ]),
                    WidgetComponentFilesSection::make(),
                ]),
        ]);
    }
}
