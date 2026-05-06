<?php

declare(strict_types=1);

namespace Capell\Insights\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Capell\Insights\Enums\InsightsConsentRegion;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

final class InsightsSettingsSchema implements HasSchema
{
    public static function make(Schema $configurator): array
    {
        return [
            Fieldset::make(__('capell-insights::settings.fieldset'))
                ->columnSpanFull()
                ->schema([
                    HelperText::apply(
                        Toggle::make('enabled')
                            ->label(__('capell-insights::settings.enabled')),
                        'capell-insights::settings.enabled_helper',
                    ),
                    Toggle::make('track_page_views')
                        ->label(__('capell-insights::settings.track_page_views')),
                    Toggle::make('track_clicks')
                        ->label(__('capell-insights::settings.track_clicks')),
                    Toggle::make('track_form-builder')
                        ->label(__('capell-insights::settings.track_form-builder')),
                    Toggle::make('automatic_click_tracking')
                        ->label(__('capell-insights::settings.automatic_click_tracking')),
                    Toggle::make('require_consent_for_all_regions')
                        ->label(__('capell-insights::settings.require_consent_for_all_regions')),
                    Select::make('default_consent_region')
                        ->label(__('capell-insights::settings.default_consent_region'))
                        ->options(InsightsConsentRegion::class)
                        ->nullable(),
                    TextInput::make('policy_version')
                        ->label(__('capell-insights::settings.policy_version'))
                        ->required(),
                    TextInput::make('retention_days')
                        ->label(__('capell-insights::settings.retention_days'))
                        ->integer()
                        ->minValue(1)
                        ->suffix(__('capell-admin::form.days')),
                    Toggle::make('hash_visitor_data')
                        ->label(__('capell-insights::settings.hash_visitor_data')),
                    TextInput::make('hash_salt')
                        ->label(__('capell-insights::settings.hash_salt'))
                        ->required(),
                    Textarea::make('ignored_paths')
                        ->label(__('capell-insights::settings.ignored_paths'))
                        ->formatStateUsing(self::listToTextarea(...))
                        ->dehydrateStateUsing(self::textareaToList(...))
                        ->rows(3),
                    Textarea::make('ignored_selectors')
                        ->label(__('capell-insights::settings.ignored_selectors'))
                        ->formatStateUsing(self::listToTextarea(...))
                        ->dehydrateStateUsing(self::textareaToList(...))
                        ->rows(3),
                    TextInput::make('route_prefix')
                        ->label(__('capell-insights::settings.route_prefix'))
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
