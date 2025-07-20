<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\WidgetAsset;

use Capell\Layout\Filament\Components\Forms\ActionsRepeater;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Capell\Layout\Filament\Components\Forms\Content\RelatedRepeater;
use Capell\Layout\Filament\Components\Forms\MediaRepeater;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Override;

class HeroWidgetAssetSchema extends DefaultWidgetAssetSchema
{
    #[Override]
    protected static function getContentFormSchema(Schema $schema): array
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
                            self::getMediaTab(),
                            self::getRelatedTab(),
                            self::getActionsTab(),
                            self::getSettingsTab(),
                        ]),
                ]),
        ];
    }

    protected static function getActionsTab(): Tab
    {
        return Tab::make('actions')
            ->label(__('capell-admin::generic.links'))
            ->badge(fn (Get $get): ?int => count($get('actions') ?: []) ?: null)
            ->icon('heroicon-o-link')
            ->schema([
                ActionsRepeater::make('actions')
                    ->hiddenLabel(),
            ]);
    }

    protected static function getMediaTab(): Tab
    {
        return Tab::make('media')
            ->label(__('capell-admin::generic.media'))
            ->badge(fn (Get $get): ?int => count($get('media') ?: []) ?: null)
            ->icon('heroicon-o-photo')
            ->schema([
                Hidden::make('image_id'),
                MediaRepeater::make(prependImage: true),
            ]);
    }

    protected static function getRelatedTab(): Tab
    {
        return Tab::make('related')
            ->label(__('capell-admin::generic.related'))
            ->badge(fn (Get $get): ?int => count($get('related') ?: []) ?: null)
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->schema([
                RelatedRepeater::make(),
            ]);
    }

    protected static function getSettingsTab(): Tab
    {
        return Tab::make('settings')
            ->label(__('capell-admin::generic.settings'))
            ->schema([
                ColorSchemeComponent::make('color_scheme'),
                BackgroundSettingsFieldset::make()
                    ->columnSpanFull(),
            ]);
    }
}
