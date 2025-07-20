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
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DefaultWidgetSchema extends AbstractWidgetSchema
{
    public static function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'create', 'createOption', 'replicate' => [
                WidgetTranslationsRepeater::make($schema)
                    ->section(fn (string $operation): bool => $operation === 'create'),
                ...self::getExtraSchema($schema),
            ],
            'editOption' => [
                WidgetTranslationsRepeater::make($schema),
                ...self::getExtraSchema($schema, withSettingsTab: true),
            ],
            default => [
                FixedWidthSidebar::make()
                    ->mainSchema([
                        WidgetTranslationsRepeater::make($schema)
                            ->section(),
                        ...self::getExtraSchema($schema),
                    ])
                    ->sidebarSchema([
                        Section::make()
                            ->columns(1)
                            ->schema(WidgetSettingsSchema::make($schema)),
                    ]),
            ],
        };
    }

    protected static function getExtraSchema(Schema $schema, bool $withSettingsTab = false): array
    {
        return [
            self::getTabs($schema, $withSettingsTab),
        ];
    }

    protected static function getTabs(Schema $schema, bool $withSettingsTab = false): Tabs
    {
        return Tabs::make('tabs')
            ->columnSpanFull()
            ->tabs([
                static::getDetailsTab(),
                static::getDisplayTab($schema),
                ...$withSettingsTab ? static::getSettingsTab($schema) : [],
            ]);
    }

    protected static function getDisplayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            Grid::make()
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

    private static function getDetailsTab(): Tab
    {
        return Tab::make('details')
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
                Grid::make()
                    ->schema([
                        ImageMediaPicker::make('image_id')
                            ->label(__('capell-admin::form.image'))
                            ->relationship(relationshipName: 'image', titleColumnName: 'name')
                            ->reactive(),
                        Checkbox::make('reverse_order')
                            ->label(__('capell-admin::form.reverse_order'))
                            ->visible(fn (Get $get): bool => (bool) $get('image_id')),
                    ]),
                ActionsRepeater::make('actions'),
            ]);
    }

    private static function getSettingsTab(Schema $schema): Tab
    {
        return Tab::make('settings')
            ->label(__('capell-admin::tab.settings'))
            ->icon('heroicon-o-cog')
            ->statePath('settings')
            ->schema(WidgetSettingsSchema::make($schema));
    }
}
