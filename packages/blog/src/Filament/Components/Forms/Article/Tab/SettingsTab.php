<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Components\Forms\Article\Tab;

use Capell\Admin\Filament\Components\Forms\CacheTimeSelect;
use Capell\Blog\Filament\Components\Forms\Article\ArticleSelect;
use Capell\Core\Contracts\Pageable;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class SettingsTab
{
    public static function make(Schema $configurator, array $components = []): Tab
    {
        return Tab::make(__('capell-admin::tab.settings'))
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->columns()
            ->schema([
                ...$components,
                self::getMetaSection(),
            ]);
    }

    private static function getMetaSection(): Section
    {
        return Section::make(__('capell-admin::tab.seo_settings'))
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->statePath('meta')
            ->icon(Heroicon::OutlinedArrowTrendingUp)
            ->columns(3)
            ->schema([
                ArticleSelect::make('canonicalPage')
                    ->label(__('capell-admin::form.canonical_page'))
                    ->modifyKeySelectOptionsQueryUsing(function (Builder $query, ?Pageable $record, Get $get): Builder {
                        $siteId = filled($get('site_id')) ? (int) $get('site_id') : $record?->site_id;

                        if ($siteId === null) {
                            return $query;
                        }

                        return $query->where('site_id', $siteId);
                    }),
                CacheTimeSelect::make('cache_time'),
                Select::make('priority')
                    ->label(__('capell-admin::form.priority'))
                    ->options(
                        collect(range(0, 9))
                            ->map(fn (int $i): float => round(1.0 - $i * 0.1, 1))
                            ->filter(fn (float $value): bool => $value >= 0.1)
                            ->mapWithKeys(function (float $value): array {
                                $formatted = number_format($value, 1);
                                if ($formatted === '1.0') {
                                    $label = $formatted . ' ' . __('capell-admin::generic.highest');
                                } elseif ($formatted === '0.1') {
                                    $label = $formatted . ' ' . __('capell-admin::generic.lowest');
                                } else {
                                    $label = $formatted;
                                }

                                return [$formatted => $label];
                            }),
                    ),
                CheckboxList::make('robots')
                    ->options([
                        'noindex' => __('capell-admin::form.noindex'),
                        'nofollow' => __('capell-admin::form.nofollow'),
                    ])
                    ->descriptions([
                        'noindex' => __('capell-admin::generic.noindex_info'),
                        'nofollow' => __('capell-admin::generic.nofollow_info'),
                    ]),
                Textarea::make('meta_tags')
                    ->columnSpan(2)
                    ->rows(4)
                    ->label(__('capell-admin::form.meta_tags'))
                    ->hint(__('capell-admin::generic.meta_tags_extra')),
            ]);
    }
}
