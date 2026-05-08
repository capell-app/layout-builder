<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SeoSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Checkbox::make('seo_audit_enabled')
                        ->label(__('capell-seo-suite::form.seo_audit_enabled'))
                        ->helperText(__('capell-seo-suite::form.seo_audit_enabled_helper'))
                        ->default(true)
                        ->reactive(),
                    Grid::make(2)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('seo_audit_enabled') === true)
                        ->schema([
                            Checkbox::make('seo_check_meta_description')
                                ->label(__('capell-seo-suite::form.seo_check_meta_description'))
                                ->default(true),
                            Checkbox::make('seo_check_meta_title')
                                ->label(__('capell-seo-suite::form.seo_check_meta_title'))
                                ->default(true),
                            Checkbox::make('seo_check_duplicate_title')
                                ->label(__('capell-seo-suite::form.seo_check_duplicate_title'))
                                ->default(true),
                        ]),
                ]),
        ];
    }
}
