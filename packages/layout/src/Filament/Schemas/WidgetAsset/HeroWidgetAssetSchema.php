<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\WidgetAsset;

use Capell\Admin\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Admin\Filament\Components\Forms\MediaRepeater;
use Capell\Layout\Filament\Components\Forms\ActionsRepeater;
use Capell\Layout\Filament\Components\Forms\BackgroundSettingsFieldset;
use Capell\Layout\Filament\Components\Forms\Content\ContentTranslationsRepeater;
use Capell\Layout\Filament\Components\Forms\Content\RelatedRepeater;
use Filament\Forms;
use Override;

class HeroWidgetAssetSchema extends DefaultWidgetAssetSchema
{
    #[Override]
    protected static function getContentFormSchema(Forms\Form $form): array
    {
        return [
            ContentTranslationsRepeater::make($form, titleRequired: false)
                ->columnSpanFull(),

            Forms\Components\Group::make()
                ->statePath('meta')
                ->schema([
                    Forms\Components\Tabs::make()
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

    protected static function getActionsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('actions')
            ->label(__('capell-admin::generic.links'))
            ->badge(fn (Forms\Get $get): ?int => count($get('actions') ?: []) ?: null)
            ->icon('heroicon-o-link')
            ->schema([
                ActionsRepeater::make('actions')
                    ->hiddenLabel(),
            ]);
    }

    protected static function getMediaTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('media')
            ->label(__('capell-admin::generic.media'))
            ->badge(fn (Forms\Get $get): ?int => count($get('media') ?: []) ?: null)
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\Hidden::make('image_id'),
                MediaRepeater::make(prependImage: true),
            ]);
    }

    protected static function getRelatedTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('related')
            ->label(__('capell-admin::generic.related'))
            ->badge(fn (Forms\Get $get): ?int => count($get('related') ?: []) ?: null)
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->schema([
                RelatedRepeater::make(),
            ]);
    }

    protected static function getSettingsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('settings')
            ->label(__('capell-admin::generic.settings'))
            ->schema([
                ColorSchemeComponent::make('color_scheme'),
                BackgroundSettingsFieldset::make()
                    ->columnSpanFull(),
            ]);
    }
}
