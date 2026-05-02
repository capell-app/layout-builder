<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SeoSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-admin::tab.seo_settings'))
                ->columnSpanFull()
                ->schema([
                    Checkbox::make('seo_audit_enabled')
                        ->label(__('capell-admin::form.seo_audit_enabled'))
                        ->helperText(__('capell-admin::form.seo_audit_enabled_helper'))
                        ->default(true)
                        ->reactive(),
                    Fieldset::make(__('capell-admin::form.seo_audit_checks'))
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('seo_audit_enabled') === true)
                        ->schema([
                            Checkbox::make('seo_check_meta_description')
                                ->label(__('capell-admin::form.seo_check_meta_description'))
                                ->default(true),
                            Checkbox::make('seo_check_meta_title')
                                ->label(__('capell-admin::form.seo_check_meta_title'))
                                ->default(true),
                            Checkbox::make('seo_check_duplicate_title')
                                ->label(__('capell-admin::form.seo_check_duplicate_title'))
                                ->default(true),
                            Checkbox::make('seo_check_alt_text')
                                ->label(__('capell-admin::form.seo_check_alt_text'))
                                ->default(true),
                            Checkbox::make('seo_check_internal_links')
                                ->label(__('capell-admin::form.seo_check_internal_links'))
                                ->default(true),
                        ]),
                ]),
        ];
    }
}
