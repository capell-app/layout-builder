<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\SeoTools\Actions\PersistSearchConsoleUrlMetricAction;
use Capell\SeoTools\Actions\SyncSearchConsoleInsightsAction;
use Capell\SeoTools\Contracts\SearchConsoleClientInterface;
use Capell\SeoTools\Models\SearchConsoleUrlMetric;
use Capell\SeoTools\Support\SearchConsole\NullSearchConsoleClient;

it('returns unconfigured sync results without writing metrics', function (): void {
    $site = Site::factory()->create();

    app()->instance(SearchConsoleClientInterface::class, new NullSearchConsoleClient);

    $result = SyncSearchConsoleInsightsAction::run((int) $site->getKey());

    expect($result)->toBe([
        'synced' => 0,
        'configured' => false,
        'pages' => [],
    ])->and(SearchConsoleUrlMetric::query()->count())->toBe(0);
});

it('stores and queries declining search console url metrics', function (): void {
    $site = Site::factory()->create();

    PersistSearchConsoleUrlMetricAction::run(
        siteId: (int) $site->getKey(),
        url: 'https://example.com/a',
        windowStart: now()->subDays(28),
        windowEnd: now(),
        clicks: 10,
        impressions: 100,
        ctr: 0.10,
        averagePosition: 4.2,
        previousClicks: 30,
        previousImpressions: 180,
        previousCtr: 0.16,
        previousAveragePosition: 3.1,
    );

    $metric = SearchConsoleUrlMetric::query()
        ->decliningPages((int) $site->getKey(), 10)
        ->first();

    expect($metric)->not()->toBeNull()
        ->and($metric->url)->toBe('https://example.com/a')
        ->and($metric->click_delta)->toBe(-20);
});

it('only returns declining pages from the latest search console window', function (): void {
    $site = Site::factory()->create();
    $olderWindowStart = now()->subDays(60);
    $olderWindowEnd = now()->subDays(32);
    $latestWindowStart = now()->subDays(28);
    $latestWindowEnd = now();

    PersistSearchConsoleUrlMetricAction::run(
        siteId: (int) $site->getKey(),
        url: 'https://example.com/old-drop',
        windowStart: $olderWindowStart,
        windowEnd: $olderWindowEnd,
        clicks: 5,
        impressions: 50,
        ctr: 0.10,
        averagePosition: 5.2,
        previousClicks: 80,
        previousImpressions: 200,
        previousCtr: 0.40,
        previousAveragePosition: 2.1,
    );
    PersistSearchConsoleUrlMetricAction::run(
        siteId: (int) $site->getKey(),
        url: 'https://example.com/current-growth',
        windowStart: $latestWindowStart,
        windowEnd: $latestWindowEnd,
        clicks: 40,
        impressions: 120,
        ctr: 0.33,
        averagePosition: 3.2,
        previousClicks: 10,
        previousImpressions: 80,
        previousCtr: 0.12,
        previousAveragePosition: 4.6,
    );
    PersistSearchConsoleUrlMetricAction::run(
        siteId: (int) $site->getKey(),
        url: 'https://example.com/current-drop',
        windowStart: $latestWindowStart,
        windowEnd: $latestWindowEnd,
        clicks: 20,
        impressions: 100,
        ctr: 0.20,
        averagePosition: 4.2,
        previousClicks: 45,
        previousImpressions: 140,
        previousCtr: 0.32,
        previousAveragePosition: 3.1,
    );

    $urls = SearchConsoleUrlMetric::query()
        ->decliningPages((int) $site->getKey(), 10)
        ->pluck('url')
        ->all();

    expect($urls)->toBe(['https://example.com/current-drop']);
});

it('returns configured sync results from the search console client', function (): void {
    $site = Site::factory()->create();
    $pages = [
        [
            'url' => 'https://example.com/a',
            'clicks' => 10,
            'previous_clicks' => 30,
            'click_delta' => -20,
        ],
        [
            'url' => 'https://example.com/b',
            'clicks' => 8,
            'previous_clicks' => 12,
            'click_delta' => -4,
        ],
    ];

    app()->instance(SearchConsoleClientInterface::class, new class($pages) implements SearchConsoleClientInterface
    {
        /**
         * @param  array<int, array{url: string, clicks: int, previous_clicks: int, click_delta: int}>  $pages
         */
        public function __construct(private readonly array $pages) {}

        public function isConfigured(): bool
        {
            return true;
        }

        public function pageInsights(string $url): array
        {
            return [];
        }

        public function decliningPages(int $siteId, int $limit = 10): array
        {
            return array_slice($this->pages, 0, $limit);
        }
    });

    $result = SyncSearchConsoleInsightsAction::run((int) $site->getKey(), 10);

    expect($result)->toBe([
        'synced' => 2,
        'configured' => true,
        'pages' => $pages,
    ]);
});
