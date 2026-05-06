<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\Publishing;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\PublishingStudio\Models\Workspace;
use Capell\SeoSuite\Actions\BuildPageSeoReportAction;
use Capell\SeoSuite\Contracts\SeoPublishReportProvider;
use Capell\SeoSuite\Data\SeoIssueData;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SeoPublishReportProviderAdapter implements SeoPublishReportProvider
{
    /**
     * @return array<int, array{
     *     page: array{id: int|string|null, uuid?: string|null, label: string},
     *     issues: array<int, array{key: string, severity: string, message: string}>
     * }>
     */
    public function forWorkspace(Workspace $workspace): array
    {
        return $workspace->runInContext(function () use ($workspace): array {
            if (! class_exists(Page::class) || ! Schema::hasTable('pages') || ! Schema::hasColumn('pages', 'workspace_id')) {
                return [];
            }

            $pages = Page::query()
                ->withoutGlobalScopes()
                ->with(['site.language', 'translations.language', 'pageUrls'])
                ->where('workspace_id', $workspace->id)
                ->get();

            return $pages
                ->map(fn (Page $page): ?array => $this->reportForPage($page))
                ->filter()
                ->values()
                ->all();
        });
    }

    /**
     * @return array{
     *     page: array{id: int|string|null, uuid?: string|null, label: string},
     *     issues: array<int, array{key: string, severity: string, message: string}>
     * }|null
     */
    private function reportForPage(Page $page): ?array
    {
        $site = $page->site;

        if (! $site instanceof Site) {
            return null;
        }

        $issues = [];

        foreach ($this->languagesForPage($page, $site) as $language) {
            try {
                $report = BuildPageSeoReportAction::run($page, $site, $language);
            } catch (Throwable) {
                continue;
            }

            foreach ($report->issues as $issue) {
                if (! $issue instanceof SeoIssueData) {
                    continue;
                }

                $issues[] = [
                    'key' => $issue->key->value,
                    'severity' => $issue->severity->value,
                    'message' => $issue->message,
                ];
            }
        }

        if ($issues === []) {
            return null;
        }

        return [
            'page' => [
                'id' => $page->getKey(),
                'uuid' => $this->stringValue($page->getAttribute('uuid')),
                'label' => $this->pageLabel($page),
            ],
            'issues' => $issues,
        ];
    }

    /**
     * @return array<int, Language>
     */
    private function languagesForPage(Page $page, Site $site): array
    {
        $languages = $page->translations instanceof EloquentCollection
            ? $page->translations
                ->map(fn (mixed $translation): mixed => $translation->language ?? null)
                ->filter(fn (mixed $language): bool => $language instanceof Language)
            : collect();

        if ($languages->isEmpty() && $site->language instanceof Language) {
            $languages->push($site->language);
        }

        return $languages
            ->unique(fn (Language $language): int => (int) $language->getKey())
            ->values()
            ->all();
    }

    private function pageLabel(Page $page): string
    {
        $url = $page->pageUrls instanceof EloquentCollection
            ? $this->stringValue($page->pageUrls->first()?->url)
            : null;

        return $url
            ?? $this->stringValue($page->getAttribute('name'))
            ?? $this->stringValue($page->getAttribute('uuid'))
            ?? (string) $page->getKey();
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue !== '' ? $stringValue : null;
    }
}
