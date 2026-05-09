<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Actions\BuildAiReadinessAuditAction;
use Capell\SeoSuite\Actions\DashboardReports\BuildAiDiscoveryPageQueryAction;
use Capell\SeoSuite\Actions\FillAiDiscoveryPageSummaryAction;
use Capell\SeoSuite\Actions\ResolveAiDiscoveryProfileAction;
use Capell\SeoSuite\Actions\UpdateAiDiscoveryPageInclusionAction;
use Capell\SeoSuite\Actions\UpdateAiDiscoveryPageProfileAction;
use Capell\SeoSuite\Models\AiDiscoveryPageProfile;
use Capell\SeoSuite\Models\AiDiscoverySiteProfile;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LogicException;

class AiDiscoveryTable implements TableConfigurator
{
    /**
     * @var array<string, AiDiscoveryPageProfile|null>
     */
    private static array $profiles = [];

    /**
     * @var array<string, int>
     */
    private static array $readinessIssueCounts = [];

    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildAiDiscoveryPageQueryAction::run())
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->recordActions(static::getTableActions())
            ->toolbarActions(static::getBulkActions())
            ->defaultSort('updated_at', 'desc');
    }

    /**
     * @return array<int, TextColumn|PageNameColumn|DateColumn>
     */
    protected static function getTableColumns(): array
    {
        return [
            PageNameColumn::make('name')
                ->label(__('capell-admin::table.name'))
                ->withUrl()
                ->nameUrl(fn (Page $record): ?string => GetEditPageResourceUrlAction::run($record))
                ->size('sm')
                ->searchable()
                ->sortable(),
            TextColumn::make('site.name')
                ->label(__('capell-admin::table.site'))
                ->size('sm')
                ->sortable(),
            TextColumn::make('site.language.name')
                ->label(__('capell-admin::table.language'))
                ->size('sm')
                ->sortable(),
            TextColumn::make('ai_discovery_state')
                ->label(__('capell-seo-suite::generic.ai_discovery_state'))
                ->state(fn (Page $record): string => self::profileFor($record)?->include_in_ai_index ? 'included' : 'excluded')
                ->formatStateUsing(fn (string $state): string => __('capell-seo-suite::generic.ai_discovery_' . $state))
                ->badge()
                ->color(fn (string $state): string => $state === 'included' ? 'success' : 'gray')
                ->sortable(false),
            TextColumn::make('ai_discovery_summary')
                ->label(__('capell-seo-suite::generic.ai_discovery_summary_status'))
                ->state(fn (Page $record): string => trim((string) self::profileFor($record)?->summary) !== '' ? 'ready' : 'missing')
                ->formatStateUsing(fn (string $state): string => __('capell-seo-suite::generic.ai_discovery_summary_' . $state))
                ->badge()
                ->color(fn (string $state): string => $state === 'ready' ? 'success' : 'warning')
                ->sortable(false),
            TextColumn::make('ai_discovery_readiness_issues')
                ->label(__('capell-seo-suite::generic.ai_discovery_readiness_issues'))
                ->state(fn (Page $record): int => self::readinessIssueCountFor($record))
                ->badge()
                ->color(fn (int $state): string => $state === 0 ? 'success' : 'warning')
                ->sortable(false),
            TextColumn::make('ai_discovery_markdown')
                ->label(__('capell-seo-suite::generic.ai_discovery_snapshot_kind_page_markdown'))
                ->state(fn (Page $record): string => self::markdownStateFor($record))
                ->formatStateUsing(fn (string $state): string => __('capell-seo-suite::generic.ai_discovery_markdown_' . $state))
                ->url(fn (Page $record): ?string => self::markdownUrlFor($record))
                ->openUrlInNewTab()
                ->badge()
                ->color(fn (string $state): string => $state === 'available' ? 'success' : 'gray')
                ->sortable(false),
            DateColumn::make('updated_at')
                ->label(__('capell-admin::table.updated_at'))
                ->size('sm')
                ->sortable(),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('site_id')
                ->label(__('capell-admin::table.site'))
                ->relationship('site', 'name'),
            TernaryFilter::make('include_in_ai_index')
                ->label(__('capell-seo-suite::form.ai_discovery_include_in_ai_index'))
                ->trueLabel(__('capell-seo-suite::generic.ai_discovery_included'))
                ->falseLabel(__('capell-seo-suite::generic.ai_discovery_excluded'))
                ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                    true, '1', 1 => self::whereIncluded($query, true),
                    false, '0', 0 => self::whereIncluded($query, false),
                    default => $query,
                }),
            TernaryFilter::make('missing_ai_summary')
                ->label(__('capell-seo-suite::generic.ai_discovery_summary_status'))
                ->trueLabel(__('capell-seo-suite::generic.ai_discovery_summary_missing'))
                ->falseLabel(__('capell-seo-suite::generic.ai_discovery_summary_ready'))
                ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                    true, '1', 1 => self::whereSummaryMissing($query),
                    false, '0', 0 => self::whereSummaryReady($query),
                    default => $query,
                }),
        ];
    }

    protected static function getTableActions(): array
    {
        return [
            Action::make('edit_ai_discovery')
                ->label(__('capell-seo-suite::generic.ai_discovery_edit_settings'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->fillForm(fn (Page $record): array => self::profileFormState($record))
                ->schema(self::profileFormSchema())
                ->action(function (Page $record, Action $action, array $data): void {
                    self::updateProfile($record, $data);
                    $action->success();
                })
                ->successNotificationTitle(__('capell-seo-suite::generic.ai_discovery_updated')),
            Action::make('fill_ai_summary')
                ->label(__('capell-seo-suite::generic.ai_discovery_fill_summary'))
                ->icon('heroicon-o-sparkles')
                ->action(function (Page $record, Action $action): void {
                    self::fillSummary($record);
                    $action->success();
                })
                ->successNotificationTitle(__('capell-seo-suite::generic.ai_discovery_updated')),
            Action::make('include_ai_index')
                ->label(__('capell-seo-suite::generic.ai_discovery_include'))
                ->icon('heroicon-o-check-circle')
                ->visible(fn (Page $record): bool => ! (self::profileFor($record)?->include_in_ai_index ?? false))
                ->action(function (Page $record, Action $action): void {
                    self::updateInclusion($record, true);
                    $action->success();
                })
                ->successNotificationTitle(__('capell-seo-suite::generic.ai_discovery_updated')),
            Action::make('exclude_ai_index')
                ->label(__('capell-seo-suite::generic.ai_discovery_exclude'))
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->visible(fn (Page $record): bool => self::profileFor($record)?->include_in_ai_index ?? false)
                ->requiresConfirmation()
                ->action(function (Page $record, Action $action): void {
                    self::updateInclusion($record, false);
                    $action->success();
                })
                ->successNotificationTitle(__('capell-seo-suite::generic.ai_discovery_updated')),
            Action::make('preview_markdown')
                ->label(__('capell-seo-suite::generic.ai_discovery_preview_markdown'))
                ->icon('heroicon-o-document-text')
                ->url(fn (Page $record): ?string => self::markdownUrlFor($record))
                ->openUrlInNewTab()
                ->visible(fn (Page $record): bool => self::markdownUrlFor($record) !== null),
            Action::make('edit_page')
                ->label(__('capell-seo-suite::generic.ai_discovery_edit_page'))
                ->icon('heroicon-o-pencil-square')
                ->url(fn (Page $record): ?string => GetEditPageResourceUrlAction::run($record)),
        ];
    }

    protected static function getBulkActions(): array
    {
        return [
            BulkAction::make('include_ai_index')
                ->label(__('capell-seo-suite::generic.ai_discovery_include'))
                ->icon('heroicon-o-check-circle')
                ->action(function (EloquentCollection $records): void {
                    $records->each(fn (Page $record): AiDiscoveryPageProfile => self::updateInclusion($record, true));
                    self::notifyUpdated();
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('exclude_ai_index')
                ->label(__('capell-seo-suite::generic.ai_discovery_exclude'))
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (EloquentCollection $records): void {
                    $records->each(fn (Page $record): AiDiscoveryPageProfile => self::updateInclusion($record, false));
                    self::notifyUpdated();
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }

    private static function fillSummary(Page $record): AiDiscoveryPageProfile
    {
        $site = self::siteFor($record);
        $language = self::languageFor($record);

        throw_unless($site instanceof Site && $language instanceof Language, LogicException::class, 'AI Discovery requires a page site and site language.');

        $profile = FillAiDiscoveryPageSummaryAction::run($record, $site, $language);
        self::forgetRecordCache($record);

        return $profile;
    }

    private static function updateInclusion(Page $record, bool $includeInAiIndex): AiDiscoveryPageProfile
    {
        $site = self::siteFor($record);
        $language = self::languageFor($record);

        throw_unless($site instanceof Site && $language instanceof Language, LogicException::class, 'AI Discovery requires a page site and site language.');

        $profile = UpdateAiDiscoveryPageInclusionAction::run($record, $site, $language, $includeInAiIndex);
        self::forgetRecordCache($record);

        return $profile;
    }

    /**
     * @return array<int, Checkbox|TextInput|Textarea>
     */
    private static function profileFormSchema(): array
    {
        return [
            Checkbox::make('include_in_ai_index')
                ->label(__('capell-seo-suite::form.ai_discovery_include_in_ai_index')),
            TextInput::make('section')
                ->label(__('capell-seo-suite::form.ai_discovery_section'))
                ->placeholder('Pages'),
            TextInput::make('priority')
                ->label(__('capell-seo-suite::form.ai_discovery_priority'))
                ->numeric()
                ->minValue(0)
                ->maxValue(1000),
            Textarea::make('summary')
                ->label(__('capell-seo-suite::form.ai_discovery_summary'))
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('markdown_override')
                ->label(__('capell-seo-suite::form.ai_discovery_markdown_override'))
                ->rows(6)
                ->columnSpanFull(),
            TextInput::make('exclude_reason')
                ->label(__('capell-seo-suite::form.ai_discovery_exclude_reason'))
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array{include_in_ai_index: bool, section: string, priority: int, summary: ?string, markdown_override: ?string, exclude_reason: ?string}
     */
    private static function profileFormState(Page $record): array
    {
        $profile = self::profileFor($record);

        return [
            'include_in_ai_index' => $profile?->include_in_ai_index ?? true,
            'section' => $profile?->section ?? 'Pages',
            'priority' => $profile?->priority ?? 500,
            'summary' => $profile?->summary,
            'markdown_override' => $profile?->markdown_override,
            'exclude_reason' => $profile?->exclude_reason,
        ];
    }

    /**
     * @param  array{include_in_ai_index?: mixed, section?: mixed, priority?: mixed, summary?: mixed, markdown_override?: mixed, exclude_reason?: mixed}  $data
     */
    private static function updateProfile(Page $record, array $data): AiDiscoveryPageProfile
    {
        $site = self::siteFor($record);
        $language = self::languageFor($record);

        throw_unless($site instanceof Site && $language instanceof Language, LogicException::class, 'AI Discovery requires a page site and site language.');

        $profile = UpdateAiDiscoveryPageProfileAction::run($record, $site, $language, $data);
        self::forgetRecordCache($record);

        return $profile;
    }

    private static function profileFor(Page $record): ?AiDiscoveryPageProfile
    {
        $site = self::siteFor($record);
        $language = self::languageFor($record);
        $cacheKey = self::recordCacheKey($record);

        if (array_key_exists($cacheKey, self::$profiles)) {
            return self::$profiles[$cacheKey];
        }

        if (! $site instanceof Site || ! $language instanceof Language) {
            return self::$profiles[$cacheKey] = null;
        }

        $siteProfile = ResolveAiDiscoveryProfileAction::run($site, $language);

        if (! $siteProfile instanceof AiDiscoverySiteProfile) {
            return self::$profiles[$cacheKey] = null;
        }

        $profile = ResolveAiDiscoveryProfileAction::run($site, $language, $record);

        return self::$profiles[$cacheKey] = $profile instanceof AiDiscoveryPageProfile ? $profile : null;
    }

    private static function readinessIssueCountFor(Page $record): int
    {
        $site = self::siteFor($record);
        $language = self::languageFor($record);
        $cacheKey = self::recordCacheKey($record);

        if (array_key_exists($cacheKey, self::$readinessIssueCounts)) {
            return self::$readinessIssueCounts[$cacheKey];
        }

        if (! $site instanceof Site || ! $language instanceof Language) {
            return self::$readinessIssueCounts[$cacheKey] = 0;
        }

        return self::$readinessIssueCounts[$cacheKey] = BuildAiReadinessAuditAction::run($record, $site, $language)->count();
    }

    private static function markdownStateFor(Page $record): string
    {
        $profile = self::siteProfileFor($record);

        if (! $profile instanceof AiDiscoverySiteProfile || ! $profile->markdown_pages_enabled) {
            return 'disabled';
        }

        return self::markdownUrlFor($record) !== null ? 'available' : 'missing_url';
    }

    private static function markdownUrlFor(Page $record): ?string
    {
        $profile = self::siteProfileFor($record);

        if (! $profile instanceof AiDiscoverySiteProfile || ! $profile->markdown_pages_enabled) {
            return null;
        }

        $record->loadMissing('pageUrl.siteDomain');

        $url = trim($record->pageUrl?->full_url ?? '');

        if ($url === '') {
            return null;
        }

        $trimmedUrl = rtrim($url, '/');
        $path = parse_url($trimmedUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $trimmedUrl . '/index.md';
        }

        return $trimmedUrl . '.md';
    }

    private static function siteProfileFor(Page $record): ?AiDiscoverySiteProfile
    {
        $site = self::siteFor($record);
        $language = self::languageFor($record);

        if (! $site instanceof Site || ! $language instanceof Language) {
            return null;
        }

        $profile = ResolveAiDiscoveryProfileAction::run($site, $language);

        return $profile instanceof AiDiscoverySiteProfile ? $profile : null;
    }

    private static function siteFor(Page $record): ?Site
    {
        $record->loadMissing('site.language');

        return $record->site instanceof Site ? $record->site : null;
    }

    private static function languageFor(Page $record): ?Language
    {
        $site = self::siteFor($record);

        return $site?->language instanceof Language ? $site->language : null;
    }

    private static function whereIncluded(Builder $query, bool $included): Builder
    {
        return self::whereAiProfile($query, function (QueryBuilder $profileQuery) use ($included): void {
            $profileQuery->where('include_in_ai_index', $included);
        });
    }

    private static function whereSummaryMissing(Builder $query): Builder
    {
        return $query->where(function (Builder $nestedQuery): void {
            self::whereAiProfile($nestedQuery, function (QueryBuilder $profileQuery): void {
                $profileQuery->where(function (QueryBuilder $summaryQuery): void {
                    $summaryQuery
                        ->whereNull('summary')
                        ->orWhere('summary', '');
                });
            })->orWhereNotExists(self::profileExistsQuery());
        });
    }

    private static function whereSummaryReady(Builder $query): Builder
    {
        return self::whereAiProfile($query, function (QueryBuilder $profileQuery): void {
            $profileQuery
                ->whereNotNull('summary')
                ->where('summary', '!=', '');
        });
    }

    private static function whereAiProfile(Builder $query, Closure $constraint): Builder
    {
        return $query->whereExists(function (QueryBuilder $profileQuery) use ($constraint): void {
            self::constrainProfileQuery($profileQuery);
            $constraint($profileQuery);
        });
    }

    private static function profileExistsQuery(): Closure
    {
        return function (QueryBuilder $profileQuery): void {
            self::constrainProfileQuery($profileQuery);
        };
    }

    private static function constrainProfileQuery(QueryBuilder $profileQuery): void
    {
        $profileQuery
            ->selectRaw('1')
            ->from('ai_discovery_page_profiles')
            ->whereColumn('ai_discovery_page_profiles.page_id', 'pages.id')
            ->whereColumn('ai_discovery_page_profiles.site_id', 'pages.site_id')
            ->whereExists(function (QueryBuilder $siteQuery): void {
                $siteQuery
                    ->selectRaw('1')
                    ->from('sites')
                    ->whereColumn('sites.id', 'pages.site_id')
                    ->whereColumn('sites.language_id', 'ai_discovery_page_profiles.language_id');
            });
    }

    private static function forgetRecordCache(Page $record): void
    {
        unset(
            self::$profiles[self::recordCacheKey($record)],
            self::$readinessIssueCounts[self::recordCacheKey($record)],
        );
    }

    private static function recordCacheKey(Page $record): string
    {
        $siteId = self::siteFor($record)?->getKey() ?? 'none';
        $languageId = self::languageFor($record)?->getKey() ?? 'none';

        return sprintf('%s:%s:%s', $record->getKey(), $siteId, $languageId);
    }

    private static function notifyUpdated(): void
    {
        Notification::make('ai-discovery-updated')
            ->title(__('capell-seo-suite::generic.ai_discovery_updated'))
            ->success()
            ->send();
    }
}
