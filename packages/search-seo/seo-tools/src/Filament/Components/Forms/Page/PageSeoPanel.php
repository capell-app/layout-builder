<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Components\Forms\Page;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoTools\Actions\BuildPageSeoReportAction;
use Capell\SeoTools\Data\PageSeoReportData;
use Capell\SeoTools\Filament\Actions\AiContentBriefAction;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Throwable;

class PageSeoPanel extends View
{
    private const VIEW_NAME = 'capell-seo-tools::filament.components.page-seo-panel';

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->registerActions([
                AiContentBriefAction::make(),
            ])
            ->viewData(fn (Get $get): array => $this->reportViewData($get('language_id')));
    }

    public static function make(?string $view = null): static
    {
        $static = resolve(static::class, ['view' => $view ?? self::VIEW_NAME]);
        $static->configure();

        return $static;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return parent::getViewData();
    }

    /**
     * @return array{page: Page, site: Site, language: Language}|null
     */
    public function resolveAiContentBriefContext(null|int|string $languageId = null): ?array
    {
        $record = $this->pageRecord();

        if (! $record instanceof Page || ! $record->exists) {
            return null;
        }

        $record->loadMissing([
            'site.language',
            'translation.language',
        ]);

        $site = $record->site;
        $language = $this->resolveLanguage($record, $site, $languageId);

        if (! $site instanceof Site || ! $language instanceof Language) {
            return null;
        }

        return [
            'page' => $record,
            'site' => $site,
            'language' => $language,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reportViewData(null|int|string $languageId = null): array
    {
        $report = $this->buildReport($languageId);

        return [
            'report' => $report,
            'hasReport' => $report instanceof PageSeoReportData,
        ];
    }

    private function buildReport(null|int|string $languageId = null): ?PageSeoReportData
    {
        $record = $this->pageRecord();

        if (! $record instanceof Page || ! $record->exists) {
            return null;
        }

        $record->loadMissing([
            'site.language',
            'translation.language',
        ]);

        $site = $record->site;
        $language = $this->resolveLanguage($record, $site, $languageId);

        if (! $site instanceof Site || ! $language instanceof Language) {
            return null;
        }

        return BuildPageSeoReportAction::run($record, $site, $language);
    }

    private function pageRecord(): ?Page
    {
        try {
            $record = $this->getRecord();
        } catch (Throwable) {
            return null;
        }

        return $record instanceof Page ? $record : null;
    }

    private function resolveLanguage(Page $record, ?Site $site, null|int|string $languageId): ?Language
    {
        if ($languageId !== null && $languageId !== '') {
            $language = $record->translations()
                ->where('language_id', (int) $languageId)
                ->first()
                ?->language;

            if ($language instanceof Language) {
                return $language;
            }

            $language = $site?->languages()
                ->where('languages.id', (int) $languageId)
                ->first();

            if ($language instanceof Language) {
                return $language;
            }
        }

        return $record->translation?->language ?? $site?->language;
    }
}
