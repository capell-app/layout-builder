<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoSuite\Actions\DashboardReports\BuildSeoAuditQueryAction;
use Capell\SeoSuite\Enums\SeoCheckKeyEnum;
use Capell\SeoSuite\Enums\SeoSnapshotStatusEnum;
use Capell\SeoSuite\Models\PageSeoSnapshot;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class SeoAuditTable implements TableConfigurator
{
    /**
     * @var array<string, PageSeoSnapshot|null>
     */
    private static array $snapshots = [];

    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildSeoAuditQueryAction::run())
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->defaultSort('created_at', 'desc');
    }

    protected static function getTableColumns(): array
    {
        return [
            PageNameColumn::make('name')
                ->label(__('capell-admin::table.name'))
                ->size('sm')
                ->searchable()
                ->sortable(),
            TextColumn::make('site.name')
                ->label(__('capell-admin::table.site'))
                ->size('sm')
                ->sortable(),
            TextColumn::make('seo_score')
                ->label(__('capell-seo-suite::generic.seo_panel_score'))
                ->state(fn (Page $record): ?int => self::snapshotFor($record)?->score)
                ->placeholder(__('capell-seo-suite::generic.seo_snapshot_not_scanned'))
                ->size('sm')
                ->sortable(false),
            TextColumn::make('critical_issues_count')
                ->label(__('capell-seo-suite::generic.seo_severity_critical'))
                ->state(fn (Page $record): ?int => self::snapshotFor($record)?->critical_count)
                ->size('sm')
                ->sortable(false),
            TextColumn::make('warning_issues_count')
                ->label(__('capell-seo-suite::generic.seo_severity_warning'))
                ->state(fn (Page $record): ?int => self::snapshotFor($record)?->warning_count)
                ->size('sm')
                ->sortable(false),
            TextColumn::make('schema_status')
                ->label(__('capell-seo-suite::generic.seo_check_schema'))
                ->size('sm')
                ->state(fn (Page $record): ?string => self::snapshotFor($record)?->schema_status)
                ->placeholder(__('capell-seo-suite::generic.seo_snapshot_not_scanned'))
                ->badge()
                ->sortable(false),
            TextColumn::make('search_preview_title')
                ->label(__('capell-seo-suite::generic.seo_panel_search_preview'))
                ->state(fn (Page $record): ?string => self::searchPreviewTitleFor($record))
                ->limit(60)
                ->size('sm')
                ->sortable(false),
            TextColumn::make('creator.name')
                ->label(__('capell-admin::table.author'))
                ->size('sm')
                ->sortable(),
            DateColumn::make('created_at')
                ->label(__('capell-admin::table.created_at'))
                ->size('sm')
                ->sortable(),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('severity')
                ->label(__('capell-seo-suite::generic.seo_audit_severity'))
                ->options([
                    'critical' => __('capell-seo-suite::generic.seo_severity_critical'),
                    'warning' => __('capell-seo-suite::generic.seo_severity_warning'),
                    'notice' => __('capell-seo-suite::generic.seo_severity_notice'),
                    'clean' => __('capell-seo-suite::generic.seo_audit_severity_clean'),
                ])
                ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                    'critical', 'warning', 'notice', 'clean' => self::whereSeveritySnapshot($query, $data['value']),
                    default => $query,
                }),
            SelectFilter::make('issue_key')
                ->label(__('capell-seo-suite::generic.seo_audit_issue_category'))
                ->options(SeoCheckKeyEnum::class)
                ->query(fn (Builder $query, array $data): Builder => $data['value'] === null || $data['value'] === ''
                    ? $query
                    : self::whereIssueKeySnapshot($query, $data['value'])),
            SelectFilter::make('score_band')
                ->label(__('capell-seo-suite::generic.seo_audit_score_band'))
                ->options([
                    'excellent' => __('capell-seo-suite::generic.seo_audit_score_band_excellent'),
                    'good' => __('capell-seo-suite::generic.seo_audit_score_band_good'),
                    'needs_work' => __('capell-seo-suite::generic.seo_audit_score_band_needs_work'),
                    'poor' => __('capell-seo-suite::generic.seo_audit_score_band_poor'),
                ])
                ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                    'excellent' => self::whereSnapshot($query, function (QueryBuilder $snapshotQuery): void {
                        $snapshotQuery->where('score', '>=', 90);
                    }),
                    'good' => self::whereSnapshot($query, function (QueryBuilder $snapshotQuery): void {
                        $snapshotQuery->whereBetween('score', [70, 89]);
                    }),
                    'needs_work' => self::whereSnapshot($query, function (QueryBuilder $snapshotQuery): void {
                        $snapshotQuery->whereBetween('score', [40, 69]);
                    }),
                    'poor' => self::whereSnapshot($query, function (QueryBuilder $snapshotQuery): void {
                        $snapshotQuery->where('score', '<', 40);
                    }),
                    default => $query,
                }),
            SelectFilter::make('schema_status')
                ->label(__('capell-seo-suite::generic.seo_check_schema'))
                ->options(self::snapshotStatusOptions())
                ->query(fn (Builder $query, array $data): Builder => self::whereSnapshotStatus($query, 'schema_status', $data['value'] ?? null)),
            SelectFilter::make('robots_status')
                ->label(__('capell-seo-suite::generic.seo_check_robots'))
                ->options(self::snapshotStatusOptions())
                ->query(fn (Builder $query, array $data): Builder => self::whereSnapshotStatus($query, 'robots_status', $data['value'] ?? null)),
            SelectFilter::make('canonical_status')
                ->label(__('capell-seo-suite::generic.seo_check_canonical'))
                ->options(self::snapshotStatusOptions())
                ->query(fn (Builder $query, array $data): Builder => self::whereSnapshotStatus($query, 'canonical_status', $data['value'] ?? null)),
            TernaryFilter::make('has_redirect_opportunities')
                ->label(__('capell-seo-suite::generic.seo_audit_redirect_opportunities'))
                ->trueLabel(__('capell-seo-suite::generic.seo_audit_has_redirect_opportunities'))
                ->falseLabel(__('capell-seo-suite::generic.seo_audit_no_redirect_opportunities'))
                ->queries(
                    true: fn (Builder $query): Builder => self::whereSnapshot($query, function (QueryBuilder $snapshotQuery): void {
                        $snapshotQuery->where('redirect_opportunities_count', '>', 0);
                    }),
                    false: fn (Builder $query): Builder => self::whereSnapshot($query, function (QueryBuilder $snapshotQuery): void {
                        $snapshotQuery->where('redirect_opportunities_count', 0);
                    }),
                    blank: fn (Builder $query): Builder => $query,
                ),
            SelectFilter::make('search_console_status')
                ->label(__('capell-seo-suite::generic.seo_check_search_console'))
                ->options(self::snapshotStatusOptions())
                ->query(fn (Builder $query, array $data): Builder => self::whereSnapshotStatus($query, 'search_console_status', $data['value'] ?? null)),
            SelectFilter::make('snapshot_state')
                ->label(__('capell-seo-suite::generic.seo_audit_snapshot_state'))
                ->options([
                    'missing' => __('capell-seo-suite::generic.seo_audit_snapshot_missing'),
                    'scanned' => __('capell-seo-suite::generic.seo_audit_snapshot_scanned'),
                    'stale' => __('capell-seo-suite::generic.seo_audit_snapshot_stale'),
                ])
                ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                    'missing' => self::whereMissingSnapshot($query),
                    'scanned' => self::whereSnapshot($query),
                    'stale' => self::whereSnapshot($query, function (QueryBuilder $snapshotQuery): void {
                        $snapshotQuery->where('computed_at', '<', now()->subDay());
                    }),
                    default => $query,
                }),
        ];
    }

    protected static function whereSeveritySnapshot(Builder $query, string $severity): Builder
    {
        return self::whereSnapshot($query, function (QueryBuilder $snapshotQuery) use ($severity): void {
            match ($severity) {
                'critical' => $snapshotQuery->where('critical_count', '>', 0),
                'warning' => $snapshotQuery->where('warning_count', '>', 0),
                'notice' => $snapshotQuery->where('notice_count', '>', 0),
                'clean' => $snapshotQuery
                    ->where('critical_count', 0)
                    ->where('warning_count', 0)
                    ->where('notice_count', 0),
                default => null,
            };
        });
    }

    protected static function whereIssueKeySnapshot(Builder $query, string $issueKey): Builder
    {
        return self::whereSnapshot($query, function (QueryBuilder $snapshotQuery) use ($issueKey): void {
            $snapshotQuery->whereJsonContains('issue_keys', $issueKey);
        });
    }

    protected static function whereSnapshot(Builder $query, ?Closure $constraint = null): Builder
    {
        return $query->whereExists(function (QueryBuilder $snapshotQuery) use ($constraint): void {
            $snapshotQuery
                ->selectRaw('1')
                ->from('page_seo_snapshots')
                ->whereColumn('page_seo_snapshots.page_id', 'pages.id')
                ->whereColumn('page_seo_snapshots.site_id', 'pages.site_id')
                ->whereExists(function (QueryBuilder $siteQuery): void {
                    $siteQuery
                        ->selectRaw('1')
                        ->from('sites')
                        ->whereColumn('sites.id', 'pages.site_id')
                        ->whereColumn('sites.language_id', 'page_seo_snapshots.language_id');
                });

            if ($constraint instanceof Closure) {
                $constraint($snapshotQuery);
            }
        });
    }

    protected static function whereMissingSnapshot(Builder $query): Builder
    {
        return $query->whereNotExists(function (QueryBuilder $snapshotQuery): void {
            $snapshotQuery
                ->selectRaw('1')
                ->from('page_seo_snapshots')
                ->whereColumn('page_seo_snapshots.page_id', 'pages.id')
                ->whereColumn('page_seo_snapshots.site_id', 'pages.site_id')
                ->whereExists(function (QueryBuilder $siteQuery): void {
                    $siteQuery
                        ->selectRaw('1')
                        ->from('sites')
                        ->whereColumn('sites.id', 'pages.site_id')
                        ->whereColumn('sites.language_id', 'page_seo_snapshots.language_id');
                });
        });
    }

    private static function snapshotFor(Page $record): ?PageSeoSnapshot
    {
        $record->loadMissing([
            'site.language',
            'translation.language',
            'translations.language',
        ]);

        $site = $record->site;
        $language = $site?->language;
        $cacheKey = sprintf('%s:%s', $record->getKey(), $language?->getKey() ?? 'none');

        if (array_key_exists($cacheKey, self::$snapshots)) {
            return self::$snapshots[$cacheKey];
        }

        if (! $site instanceof Site || ! $language instanceof Language) {
            return self::$snapshots[$cacheKey] = null;
        }

        return self::$snapshots[$cacheKey] = PageSeoSnapshot::query()
            ->where('page_id', $record->getKey())
            ->where('site_id', $site->getKey())
            ->where('language_id', $language->getKey())
            ->first();
    }

    private static function searchPreviewTitleFor(Page $record): ?string
    {
        $translation = $record->translation;

        if (! $translation instanceof Translation) {
            return $record->name;
        }

        return $translation->getMeta('title')
            ?? $translation->title
            ?? $translation->label
            ?? $record->name;
    }

    /**
     * @return array<string, string>
     */
    private static function snapshotStatusOptions(): array
    {
        return [
            SeoSnapshotStatusEnum::Passed->value => __('capell-seo-suite::generic.seo_audit_status_passed'),
            SeoSnapshotStatusEnum::Warning->value => __('capell-seo-suite::generic.seo_audit_status_warning'),
            SeoSnapshotStatusEnum::Missing->value => __('capell-seo-suite::generic.seo_audit_status_missing'),
            SeoSnapshotStatusEnum::Unknown->value => __('capell-seo-suite::generic.seo_audit_status_unknown'),
            SeoSnapshotStatusEnum::Declining->value => __('capell-seo-suite::generic.seo_audit_status_declining'),
        ];
    }

    private static function whereSnapshotStatus(Builder $query, string $column, mixed $value): Builder
    {
        if (! is_string($value) || $value === '') {
            return $query;
        }

        return self::whereSnapshot($query, function (QueryBuilder $snapshotQuery) use ($column, $value): void {
            $snapshotQuery->where($column, $value);
        });
    }
}
