<?php

declare(strict_types=1);

namespace Capell\Analytics\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Capell\Analytics\Enums\AnalyticsConsentRegion;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class AnalyticsSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-analytics::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('enabled')
                            ->label(__('capell-analytics::settings.enabled')),
                        'capell-analytics::settings.enabled_helper',
                    ),
                    Toggle::make('track_page_views')
                        ->label(__('capell-analytics::settings.track_page_views')),
                    Toggle::make('track_clicks')
                        ->label(__('capell-analytics::settings.track_clicks')),
                    Toggle::make('track_forms')
                        ->label(__('capell-analytics::settings.track_forms')),
                    Toggle::make('automatic_click_tracking')
                        ->label(__('capell-analytics::settings.automatic_click_tracking')),
                    Toggle::make('require_consent_for_all_regions')
                        ->label(__('capell-analytics::settings.require_consent_for_all_regions')),
                    Select::make('default_consent_region')
                        ->label(__('capell-analytics::settings.default_consent_region'))
                        ->options(AnalyticsConsentRegion::class)
                        ->nullable(),
                    TextInput::make('policy_version')
                        ->label(__('capell-analytics::settings.policy_version'))
                        ->required(),
                    TextInput::make('retention_days')
                        ->label(__('capell-analytics::settings.retention_days'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    Toggle::make('hash_visitor_data')
                        ->label(__('capell-analytics::settings.hash_visitor_data')),
                    TextInput::make('hash_salt')
                        ->label(__('capell-analytics::settings.hash_salt'))
                        ->required(),
                    Textarea::make('ignored_paths')
                        ->label(__('capell-analytics::settings.ignored_paths'))
                        ->formatStateUsing(self::listToTextarea(...))
                        ->dehydrateStateUsing(self::textareaToList(...))
                        ->rows(3),
                    Textarea::make('ignored_selectors')
                        ->label(__('capell-analytics::settings.ignored_selectors'))
                        ->formatStateUsing(self::listToTextarea(...))
                        ->dehydrateStateUsing(self::textareaToList(...))
                        ->rows(3),
                    TextInput::make('route_prefix')
                        ->label(__('capell-analytics::settings.route_prefix'))
                        ->required(),
                ]),
        ];
    }

    public static function listToTextarea(mixed $state): string
    {
        if (! is_array($state)) {
            return is_string($state) ? $state : '';
        }

        $lines = [];

        foreach ($state as $item) {
            if (! is_string($item)) {
                continue;
            }

            if ($item === '') {
                continue;
            }

            $lines[] = $item;
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return list<string>
     */
    public static function textareaToList(mixed $state): array
    {
        if (is_array($state)) {
            return self::filterList($state);
        }

        if (! is_string($state)) {
            return [];
        }

        $items = preg_split('/\R/', $state);

        return self::filterList(is_array($items) ? $items : []);
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<string>
     */
    private static function filterList(array $items): array
    {
        $filteredItems = [];

        foreach ($items as $item) {
            if (! is_string($item)) {
                continue;
            }

            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $filteredItems[] = $item;
        }

        return $filteredItems;
    }
}
