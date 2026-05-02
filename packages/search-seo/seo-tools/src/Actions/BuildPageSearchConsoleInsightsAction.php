<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions;

use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\SeoTools\Contracts\SearchConsoleClientInterface;
use Capell\SeoTools\Data\SearchConsoleInsightData;
use Capell\SeoTools\Enums\SearchConsoleMetricEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

/**
 * @method static array<int, SearchConsoleInsightData> run(Page $page)
 */
final class BuildPageSearchConsoleInsightsAction
{
    use AsAction;

    /**
     * @return array<int, SearchConsoleInsightData>
     */
    public function handle(Page $page): array
    {
        $client = resolve(SearchConsoleClientInterface::class);

        if (! $client->isConfigured()) {
            return [
                new SearchConsoleInsightData(
                    metric: SearchConsoleMetricEnum::SetupRequired,
                    message: __('capell-seo-tools::generic.search_console_setup_required'),
                    severity: SeoIssueSeverityEnum::Notice,
                ),
            ];
        }

        $url = $this->pageUrl($page);

        if ($url === '') {
            return [];
        }

        return array_values(array_map(
            $this->toInsightData(...),
            $client->pageInsights($url),
        ));
    }

    private function pageUrl(Page $page): string
    {
        if (! $page->relationLoaded('pageUrl')) {
            $languageId = $this->languageId($page);

            $page->load([
                'pageUrl' => fn (BuilderContract $query): BuilderContract => $languageId === null
                    ? $query
                    : $query->where('language_id', $languageId),
                'pageUrl.siteDomain',
            ]);
        } elseif ($page->pageUrl !== null && ! $page->pageUrl->relationLoaded('siteDomain')) {
            $page->pageUrl->load('siteDomain');
        }

        if ($page->pageUrl === null) {
            return '';
        }

        try {
            return $this->stringValue($page->pageUrl->full_url) ?? $this->stringValue($page->pageUrl->url) ?? '';
        } catch (Throwable) {
            return $this->stringValue($page->pageUrl->url) ?? '';
        }
    }

    private function languageId(Page $page): ?int
    {
        if (! $page->relationLoaded('translation')) {
            return null;
        }

        if (! $page->translation instanceof Translation) {
            return null;
        }

        return $page->translation->language_id;
    }

    private function toInsightData(mixed $insight): SearchConsoleInsightData
    {
        if ($insight instanceof SearchConsoleInsightData) {
            return $insight;
        }

        if (is_array($insight)) {
            return SearchConsoleInsightData::from($insight);
        }

        return new SearchConsoleInsightData(
            metric: SearchConsoleMetricEnum::SetupRequired,
            message: __('capell-seo-tools::generic.search_console_insight_unavailable'),
            severity: SeoIssueSeverityEnum::Notice,
        );
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
