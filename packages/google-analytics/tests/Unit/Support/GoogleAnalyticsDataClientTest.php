<?php

declare(strict_types=1);

use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;
use Capell\GoogleAnalytics\Exceptions\GoogleAnalyticsApiException;
use Capell\GoogleAnalytics\Support\Analytics\GoogleAnalyticsDataClient;
use Capell\GoogleAnalytics\Tests\GoogleAnalyticsTestCase;
use Carbon\CarbonImmutable;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

uses(GoogleAnalyticsTestCase::class);

function createGoogleAnalyticsCredentialsFile(): string
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
        'client_email' => 'google-analytics@example.iam.gserviceaccount.com',
        'private_key' => $privateKeyContents,
        'token_uri' => 'https://oauth2.googleapis.com/token',
    ], JSON_THROW_ON_ERROR));

    return $credentialsPath;
}

/**
 * @return array{dimensionValues: list<array{value: string}>, metricValues: list<array{value: string}>}
 */
function createGoogleAnalyticsPageReportRow(int $index): array
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
    expect(new GoogleAnalyticsDataClient(['enabled' => false, 'property_id' => '123', 'credentials_path' => '/tmp/service.json']))
        ->isConfigured()->toBeFalse()
        ->and(new GoogleAnalyticsDataClient(['enabled' => true, 'property_id' => '', 'credentials_path' => '/tmp/service.json']))
        ->isConfigured()->toBeFalse()
        ->and(new GoogleAnalyticsDataClient(['enabled' => true, 'property_id' => '123', 'credentials_path' => '']))
        ->isConfigured()->toBeFalse();
});

it('maps GA4 daily and page report rows into data objects', function (): void {
    Date::setTestNow(Date::create(2026, 5, 5, 12, 0, 0));
    $credentialsPath = createGoogleAnalyticsCredentialsFile();

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token'], 200),
        'https://analyticsdata.googleapis.com/*' => Http::sequence()
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

    $client = new GoogleAnalyticsDataClient([
        'enabled' => true,
        'property_id' => '123456789',
        'credentials_path' => $credentialsPath,
    ]);
    $window = new GoogleAnalyticsWindowData(
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
    $credentialsPath = createGoogleAnalyticsCredentialsFile();

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token'], 200),
        'https://analyticsdata.googleapis.com/*' => Http::response([], 500),
    ]);

    $client = new GoogleAnalyticsDataClient([
        'enabled' => true,
        'property_id' => '123456789',
        'credentials_path' => $credentialsPath,
    ]);

    expect(fn (): array => $client->dailyMetrics(new GoogleAnalyticsWindowData(
        startsAt: CarbonImmutable::create(2026, 5, 4),
        endsAt: CarbonImmutable::create(2026, 5, 4),
        propertyId: '123456789',
    )))->toThrow(GoogleAnalyticsApiException::class, 'Google Analytics Data API request failed with HTTP status 500.');

    unlink($credentialsPath);
});

it('orders and paginates GA4 page report rows', function (): void {
    $credentialsPath = createGoogleAnalyticsCredentialsFile();
    $analyticsPayloads = [];
    $firstPageRows = array_map(
        fn (int $index): array => createGoogleAnalyticsPageReportRow($index),
        range(1, 250),
    );

    Http::fake(function (Request $request) use (&$analyticsPayloads, $firstPageRows): PromiseInterface {
        if ($request->url() === 'https://oauth2.googleapis.com/token') {
            return Http::response(['access_token' => 'test-token'], 200);
        }

        $analyticsPayloads[] = $request->data();

        return count($analyticsPayloads) === 1
            ? Http::response(['rows' => $firstPageRows], 200)
            : Http::response(['rows' => [createGoogleAnalyticsPageReportRow(251)]], 200);
    });

    $client = new GoogleAnalyticsDataClient([
        'enabled' => true,
        'property_id' => '123456789',
        'credentials_path' => $credentialsPath,
    ]);

    $metrics = $client->pageMetrics(new GoogleAnalyticsWindowData(
        startsAt: CarbonImmutable::create(2026, 5, 4),
        endsAt: CarbonImmutable::create(2026, 5, 4),
        propertyId: '123456789',
    ));

    unlink($credentialsPath);

    expect($metrics)->toHaveCount(251)
        ->and($analyticsPayloads)->toHaveCount(2)
        ->and($analyticsPayloads[0]['limit'])->toBe(250)
        ->and($analyticsPayloads[0]['offset'])->toBe(0)
        ->and($analyticsPayloads[0]['orderBys'][0]['metric']['metricName'])->toBe('screenPageViews')
        ->and($analyticsPayloads[0]['orderBys'][0]['desc'])->toBeTrue()
        ->and($analyticsPayloads[1]['offset'])->toBe(250);
});
