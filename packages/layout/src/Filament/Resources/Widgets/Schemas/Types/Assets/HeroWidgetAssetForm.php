<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Layout\Filament\Components\Forms\ActionsRepeater;
use Capell\Layout\Filament\Components\Forms\AssetsRepeater;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Capell\Layout\Filament\Components\Forms\Content\RelatedRepeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Override;

class HeroWidgetAssetForm extends AbstractWidgetAssetSchema
{
    #[Override]
    protected function getAssetSchema(Schema $schema): array
    {
        return [
            ContentTranslationsRepeater::make($schema, titleRequired: false)
                ->columnSpanFull(),

            Group::make()
                ->statePath('meta')
                ->schema([
                    Tabs::make()
                        ->columnSpanFull()
                        ->tabs([
                            self::getMediaTab($schema),
                            self::getRelatedTab(),
                            self::getActionsTab(),
                            self::getSettingsTab(),
                        ]),
                ]),
        ];
    }

    protected function getActionsTab(): Tab
    {
        return Tab::make('actions')
            ->label(__('capell-admin::generic.links'))
            ->badge(fn (Get $get): ?int => count($get('actions') ?: []) !== 0 ? count($get('actions') ?: []) : null)
            ->icon('heroicon-o-link')
            ->schema([
                ActionsRepeater::make('actions')
                    ->hiddenLabel(),
            ]);
    }

    protected function getMediaTab(Schema $schema): Tab
    {

        return Tab::make('media')
            ->label(__('capell-admin::generic.media'))
            ->badge(fn (Get $get): ?int => count($get('media') ?: []) !== 0 ? count($get('media') ?: []) : null)
            ->icon('heroicon-o-photo')
            ->schema([
                self::getAssetsComponent($schema),
            ]);
    }

    protected function getRelatedTab(): Tab
    {
        return Tab::make('related')
            ->label(__('capell-admin::generic.related'))
            ->badge(fn (Get $get): ?int => count($get('related') ?: []) !== 0 ? count($get('related') ?: []) : null)
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->schema([
                RelatedRepeater::make(),
            ]);
    }

    protected function getSettingsTab(): Tab
    {
        return Tab::make('settings')
            ->label(__('capell-admin::generic.settings'))
            ->schema([
                ColorSchemeComponent::make('color_scheme'),
                BackgroundSettingsFieldset::make()
                    ->columnSpanFull(),
            ]);
    }

    protected function getAssetsComponent(Schema $schema): Component
    {
        return AssetsRepeater::make('assets')
            ->hiddenLabel();
    }
}
