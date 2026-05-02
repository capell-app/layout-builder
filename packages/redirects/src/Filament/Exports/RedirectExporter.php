<?php

declare(strict_types=1);

namespace Capell\Redirects\Filament\Exports;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Override;

class RedirectExporter extends Exporter
{
    protected static ?string $model = PageUrl::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('url')
                ->label(__('redirects::table.source_url')),

            ExportColumn::make('target_url')
                ->label(__('redirects::table.target_url')),

            ExportColumn::make('status_code')
                ->label(__('redirects::table.status_code'))
                ->formatStateUsing(fn (?PageUrl $record): int => $record?->status_code?->value ?? 301),

            ExportColumn::make('status')
                ->label(__('redirects::table.status'))
                ->formatStateUsing(fn (?PageUrl $record): string => $record?->status ? 'active' : 'disabled'),

            ExportColumn::make('is_manual')
                ->label(__('redirects::table.is_manual'))
                ->formatStateUsing(fn (?PageUrl $record): string => $record?->is_manual ? 'yes' : 'no'),

            ExportColumn::make('site.name')
                ->label(__('redirects::form.site')),

            ExportColumn::make('language.name')
                ->label(__('redirects::form.language')),

            ExportColumn::make('hit_count')
                ->label(__('redirects::table.hit_count')),

            ExportColumn::make('last_hit_at')
                ->label(__('redirects::table.last_hit_at')),

            ExportColumn::make('notes')
                ->label(__('redirects::form.notes')),

            ExportColumn::make('created_at'),
        ];
    }

    #[Override]
    public static function modifyQuery(Builder $query): Builder
    {
        return $query
            ->where('type', UrlTypeEnum::Redirect)
            ->tap(fn (Builder $query): Builder => SiteScope::applyForCurrentActor($query))
            ->with(['site', 'language']);
    }

    #[Override]
    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('redirects::message.redirect_export_complete', [
            'count' => number_format($export->successful_rows),
        ]);
    }

    #[Override]
    public static function getModel(): string
    {
        return PageUrl::class;
    }
}
