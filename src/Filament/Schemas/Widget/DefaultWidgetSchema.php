<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Widget;

use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Media\ImageMediaPicker;
use Capell\Layout\Filament\Components\Forms\ActionsRepeater;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Capell\Layout\Filament\Schemas\AbstractWidgetSchema;
use Filament\Forms;

class DefaultWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Forms\Form $form): array
    {
        $operation = $form->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate' => [
                WidgetTranslationsRepeater::make($form)
                    ->section(fn (string $operation): bool => $operation === 'create'),
                ...self::getExtraSchema($form),
            ],
            'editOption' => [
                WidgetTranslationsRepeater::make($form),
                ...self::getExtraSchema($form, withSettingsTab: true),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        WidgetTranslationsRepeater::make($form)
                            ->section(),
                        ...self::getExtraSchema($form),
                    ])
                    ->sidebarSchema([
                        Forms\Components\Section::make()
                            ->columns(1)
                            ->schema(WidgetSettingsSchema::make($form)),
                    ]),
            ],
        };
    }

    protected static function getExtraSchema(Forms\Form $form, bool $withSettingsTab = false): array
    {
        return [
            self::getTabs($form, $withSettingsTab),
        ];
    }

    protected static function getTabs(Forms\Form $form, bool $withSettingsTab = false): Forms\Components\Tabs
    {
        return Forms\Components\Tabs::make('tabs')
            ->columnSpanFull()
            ->tabs([
                static::getDetailsTab(),
                static::getDisplayTab($form),
                ...$withSettingsTab ? static::getSettingsTab($form) : [],
            ]);
    }

    protected static function getDisplayTab(Forms\Form $form): Forms\Components\Tabs\Tab
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

    private static function getDetailsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->mutateDehydratedStateUsing(function (array $state): array {
                if (! empty($state['image_id'])) {
                    $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                }

                return $state;
            })
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        ImageMediaPicker::make('image_id')
                            ->label(__('capell-admin::form.image'))
                            ->relationship(relationshipName: 'image', titleColumnName: 'name')
                            ->reactive(),
                        Forms\Components\Checkbox::make('reverse_order')
                            ->label(__('capell-admin::form.reverse_order'))
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('image_id')),
                    ]),
                ActionsRepeater::make('actions'),
            ]);
    }

    private static function getSettingsTab(Forms\Form $form): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('settings')
            ->label(__('capell-admin::tab.settings'))
            ->icon('heroicon-o-cog')
            ->statePath('settings')
            ->schema(WidgetSettingsSchema::make($form));
    }
}
