<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Filament\FormBuilder\Components\Checkbox;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class FoundationThemeSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-frontend::form.performance'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Checkbox::make('enable_lazy_loading')
                            ->label(__('capell-frontend::form.enable_lazy_loading')),
                        'capell-frontend::form.enable_lazy_loading_helper',
                    ),
                    HelperText::apply(
                        Checkbox::make('minify_assets')
                            ->label(__('capell-frontend::form.minify_assets')),
                        'capell-frontend::form.minify_assets_helper',
                    ),
                ]),
        ];
    }
}
