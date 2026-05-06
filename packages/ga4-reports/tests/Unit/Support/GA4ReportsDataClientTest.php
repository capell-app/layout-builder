<?php

declare(strict_types=1);

use Capell\GA4Reports\Data\GA4ReportsWindowData;
use Capell\GA4Reports\Exceptions\GA4ReportsApiException;
use Capell\GA4Reports\Support\Insights\GA4ReportsDataClient;
use Capell\GA4Reports\Tests\GA4ReportsTestCase;
use Carbon\CarbonImmutable;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

uses(GA4ReportsTestCase::class);

function createGA4ReportsCredentialsFile(): string
{
    $credentialsPath = tempnam(sys_get_temp_dir(), 'ga4-credentials');
    $privateKey = openssl_pkey_new([
        'private_key_bits' => 1024,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    $privateKeyContents = '';

    expect($credentialsPath)->toBeString();
    expect($privateKey)->not()->toBeFalse();

    openssl_pkey_export($privateKey, $privateKeyContents);

    file_put_contents($credentialsPath, json_encode([
        'client_email' => 'ga4-reports@example.iam.gserviceaccount.com',
        'private_key' => $privateKeyContents,
        'token_uri' => 'https://oauth2.googleapis.com/token',
    ], JSON_THROW_ON_ERROR));

    return $credentialsPath;
}

/**
 * @return array{dimensionValues: list<array{value: string}>, metricValues: list<array{value: string}>}
 */
function createGA4ReportsPageReportRow(int $index): array
{
    return [
        'dimensionValues' => [
            ['value' => '20260504'],
            ['value' => '/page-' . $index],
            ['value' => 'Page ' . $index],
        ],
        'metricValues' => [
            ['value' => (string) (100 + $index)],
            ['value' => (string) (90 + $index)],
            ['value' => (string) (80 + $index)],
            ['value' => (string) (70 + $index)],
            ['value' => (string) (60 + $index)],
        ],
    ];
}

it('is not configured without all required config values', function (): void {
    expect(new GA4ReportsDataClient(['enabled' => false, 'property_id' => '123', 'credentials_path' => '/tmp/service.json']))
        ->isConfigured()->toBeFalse()
        ->and(new GA4ReportsDataClient(['enabled' => true, 'property_id' => '', 'credentials_path' => '/tmp/service.json']))
        ->isConfigured()->toBeFalse()
        ->and(new GA4ReportsDataClient(['enabled' => true, 'property_id' => '123', 'credentials_path' => '']))
        ->isConfigured()->toBeFalse();
});

it('maps GA4 daily and page report rows into data objects', function (): void {
    Date::setTestNow(Date::create(2026, 5, 5, 12, 0, 0));
    $credentialsPath = createGA4ReportsCredentialsFile();

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token'], 200),
        'https://insightsdata.googleapis.com/*' => Http::sequence()
            ->push([
                'rows' => [[
                    'dimensionValues' => [
                        ['value' => '20260504'],
                    ],
                    'metricValues' => [
                        ['value' => '12'],
                        ['value' => '20'],
                        ['value' => '45'],
                        ['value' => '14'],
                        ['value' => '0.7'],
                        ['value' => '63.4'],
                        ['value' => '90'],
                        ['value' => '3'],
                    ],
                ]],
            ], 200)
            ->push([
                'rows' => [[
                    'dimensionValues' => [
                        ['value' => '20260504'],
                        ['value' => '/about'],
                        ['value' => 'About'],
                    ],
                    'metricValues' => [
                        ['value' => '8'],
                        ['value' => '10'],
                        ['value' => '30'],
                        ['value' => '45'],
                        ['value' => '2'],
                    ],
                ]],
            ], 200),
    ]);

    $client = new GA4ReportsDataClient([
        'enabled' => true,
        'property_id' => '123456789',
        'credentials_path' => $credentialsPath,
    ]);
    $window = new GA4ReportsWindowData(
        startsAt: CarbonImmutable::create(2026, 5, 4),
        endsAt: CarbonImmutable::create(2026, 5, 4),
        propertyId: '123456789',
    );

    $dailyMetrics = $client->dailyMetrics($window);
    $pageMetrics = $client->pageMetrics($window);

    unlink($credentialsPath);
    Date::setTestNow();

    expect($dailyMetrics)->toHaveCount(1)
        ->and($dailyMetrics[0]->metricDate->toDateString())->toBe('2026-05-04')
        ->and($dailyMetrics[0]->totalUsers)->toBe(12)
        ->and($dailyMetrics[0]->sessions)->toBe(20)
        ->and($dailyMetrics[0]->screenPageViews)->toBe(45)
        ->and($dailyMetrics[0]->engagedSessions)->toBe(14)
        ->and($dailyMetrics[0]->engagementRate)->toBe(0.7)
        ->and($dailyMetrics[0]->averageSessionDuration)->toBe(63.4)
        ->and($dailyMetrics[0]->eventCount)->toBe(90)
        ->and($dailyMetrics[0]->conversions)->toBe(3)
        ->and($pageMetrics)->toHaveCount(1)
        ->and($pageMetrics[0]->pagePath)->toBe('/about')
        ->and($pageMetrics[0]->pageTitle)->toBe('About')
        ->and($pageMetrics[0]->screenPageViews)->toBe(30)
        ->and($pageMetrics[0]->conversions)->toBe(2);
});

it('throws when the GA4 API fails', function (): void {
    $credentialsPath = createGA4ReportsCredentialsFile();

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token'], 200),
        'https://insightsdata.googleapis.com/*' => Http::response([], 500),
    ]);

    $client = new GA4ReportsDataClient([
        'enabled' => true,
        'property_id' => '123456789',
        'credentials_path' => $credentialsPath,
    ]);

    expect(fn (): array => $client->dailyMetrics(new GA4ReportsWindowData(
        startsAt: CarbonImmutable::create(2026, 5, 4),
        endsAt: CarbonImmutable::create(2026, 5, 4),
        propertyId: '123456789',
    )))->toThrow(GA4ReportsApiException::class, 'GA4 Reports Data API request failed with HTTP status 500.');

    unlink($credentialsPath);
});

it('orders and paginates GA4 page report rows', function (): void {
    $credentialsPath = createGA4ReportsCredentialsFile();
    $insightsPayloads = [];
    $firstPageRows = array_map(
        fn (int $index): array => createGA4ReportsPageReportRow($index),
        range(1, 250),
    );

    Http::fake(function (Request $request) use (&$insightsPayloads, $firstPageRows): PromiseInterface {
        if ($request->url() === 'https://oauth2.googleapis.com/token') {
            return Http::response(['access_token' => 'test-token'], 200);
        }

        $insightsPayloads[] = $request->data();

        return count($insightsPayloads) === 1
            ? Http::response(['rows' => $firstPageRows], 200)
            : Http::response(['rows' => [createGA4ReportsPageReportRow(251)]], 200);
    });

    $client = new GA4ReportsDataClient([
        'enabled' => true,
        'property_id' => '123456789',
        'credentials_path' => $credentialsPath,
    ]);

    $metrics = $client->pageMetrics(new GA4ReportsWindowData(
        startsAt: CarbonImmutable::create(2026, 5, 4),
        endsAt: CarbonImmutable::create(2026, 5, 4),
        propertyId: '123456789',
    ));

    unlink($credentialsPath);

    expect($metrics)->toHaveCount(251)
        ->and($insightsPayloads)->toHaveCount(2)
        ->and($insightsPayloads[0]['limit'])->toBe(250)
        ->and($insightsPayloads[0]['offset'])->toBe(0)
        ->and($insightsPayloads[0]['orderBys'][0]['metric']['metricName'])->toBe('screenPageViews')
        ->and($insightsPayloads[0]['orderBys'][0]['desc'])->toBeTrue()
        ->and($insightsPayloads[1]['offset'])->toBe(250);
});
