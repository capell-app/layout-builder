<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\SeoSuite\Actions\BuildPageSearchConsoleInsightsAction;
use Capell\SeoSuite\Contracts\SearchConsoleClientInterface;
use Capell\SeoSuite\Data\SearchConsoleInsightData;
use Capell\SeoSuite\Enums\SearchConsoleMetricEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Illuminate\Contracts\Database\Eloquent\Builder;

it('returns a setup required insight when search console is not configured', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();

    $insights = BuildPageSearchConsoleInsightsAction::run($page);

    expect($insights)->toHaveCount(1)
        ->and($insights[0])->toBeInstanceOf(SearchConsoleInsightData::class)
        ->and($insights[0]->metric)->toBe(SearchConsoleMetricEnum::SetupRequired)
        ->and($insights[0]->severity)->toBe(SeoIssueSeverityEnum::Notice);
});

it('returns page insights from the configured client for the resolved page url', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()
        ->recycle($language)
        ->language($language)
        ->withTranslations($language, siteDomainData: ['scheme' => 'https', 'domain' => 'example.com', 'path' => null])
        ->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();
    $page->pageUrls()->where('language_id', $language->id)->update(['url' => '/about']);
    $page->load([
        'translation' => fn (Builder $query): Builder => $query->where('language_id', $language->id),
        'pageUrl' => fn (Builder $query): Builder => $query->where('language_id', $language->id),
        'pageUrl.siteDomain',
    ]);
    $client = new class implements SearchConsoleClientInterface
    {
        public ?string $url = null;

        public function isConfigured(): bool
        {
            return true;
        }

        public function pageInsights(string $url): array
        {
            $this->url = $url;

            return [
                new SearchConsoleInsightData(
                    metric: SearchConsoleMetricEnum::Clicks,
                    message: 'Clicks fell by 12.',
                    value: 38,
                    previousValue: 50,
                    delta: -12.0,
                    severity: SeoIssueSeverityEnum::Warning,
                ),
            ];
        }

        public function decliningPages(int $siteId, int $limit = 10): array
        {
            return [];
        }

        public function urlMetricRows(int $siteId, int $limit = 100): array
        {
            return [];
        }
    };
    app()->instance(SearchConsoleClientInterface::class, $client);

    $insights = BuildPageSearchConsoleInsightsAction::run($page);

    expect($client->url)->toBe('https://example.com/about')
        ->and($insights)->toHaveCount(1)
        ->and($insights[0]->metric)->toBe(SearchConsoleMetricEnum::Clicks)
        ->and($insights[0]->value)->toBe(38)
        ->and($insights[0]->previousValue)->toBe(50)
        ->and($insights[0]->delta)->toBe(-12.0);
});
