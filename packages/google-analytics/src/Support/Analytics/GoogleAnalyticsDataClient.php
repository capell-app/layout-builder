<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Support\Analytics;

use Capell\GoogleAnalytics\Contracts\GoogleAnalyticsDataClientInterface;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsDailyMetricData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsPageMetricData;
use Capell\GoogleAnalytics\Data\GoogleAnalyticsWindowData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

final class GoogleAnalyticsDataClient implements GoogleAnalyticsDataClientInterface
{
    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function isConfigured(): bool
    {
        return ($this->config['enabled'] ?? false) === true
            && $this->configuredPropertyId() !== ''
            && $this->configuredCredentialsPath() !== '';
    }

    public function dailyMetrics(GoogleAnalyticsWindowData $window): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $rows = $this->runReport($window, ['date'], [
                'totalUsers',
                'sessions',
                'screenPageViews',
                'engagedSessions',
                'engagementRate',
                'averageSessionDuration',
                'eventCount',
                'keyEvents',
            ]);
        } catch (Throwable) {
            return [];
        }

        $metrics = [];

        foreach ($rows as $row) {
            $dimensionValues = $this->dimensionValues($row);
            $metricValues = $this->metricValues($row);
            $date = $dimensionValues[0] ?? null;

            if (! is_string($date) || $date === '') {
                continue;
            }

            $metricDate = CarbonImmutable::createFromFormat('Ymd', $date);

            $metrics[] = new GoogleAnalyticsDailyMetricData(
                propertyId: $window->propertyId,
                metricDate: $metricDate instanceof CarbonImmutable ? $metricDate : $window->startsAt,
                totalUsers: $this->integerMetric($metricValues, 0),
                sessions: $this->integerMetric($metricValues, 1),
                screenPageViews: $this->integerMetric($metricValues, 2),
                engagedSessions: $this->integerMetric($metricValues, 3),
                engagementRate: $this->floatMetric($metricValues, 4),
                averageSessionDuration: $this->floatMetric($metricValues, 5),
                eventCount: $this->integerMetric($metricValues, 6),
                conversions: $this->integerMetric($metricValues, 7),
            );
        }

        return $metrics;
    }

    public function pageMetrics(GoogleAnalyticsWindowData $window): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $rows = $this->runReport($window, ['date', 'pagePathPlusQueryString', 'pageTitle'], [
                'totalUsers',
                'sessions',
                'screenPageViews',
                'eventCount',
                'keyEvents',
            ], 250);
        } catch (Throwable) {
            return [];
        }

        $metrics = [];

        foreach ($rows as $row) {
            $dimensionValues = $this->dimensionValues($row);
            $metricValues = $this->metricValues($row);
            $date = $dimensionValues[0] ?? null;
            $pagePath = $dimensionValues[1] ?? null;

            if (! is_string($date) || $date === '' || ! is_string($pagePath) || trim($pagePath) === '') {
                continue;
            }

            $pageTitle = $dimensionValues[2] ?? null;

            $metricDate = CarbonImmutable::createFromFormat('Ymd', $date);

            $metrics[] = new GoogleAnalyticsPageMetricData(
                propertyId: $window->propertyId,
                metricDate: $metricDate instanceof CarbonImmutable ? $metricDate : $window->startsAt,
                pagePath: $pagePath,
                pageTitle: is_string($pageTitle) && trim($pageTitle) !== '' ? $pageTitle : null,
                totalUsers: $this->integerMetric($metricValues, 0),
                sessions: $this->integerMetric($metricValues, 1),
                screenPageViews: $this->integerMetric($metricValues, 2),
                eventCount: $this->integerMetric($metricValues, 3),
                conversions: $this->integerMetric($metricValues, 4),
            );
        }

        return $metrics;
    }

    /**
     * @param  list<string>  $dimensions
     * @param  list<string>  $metrics
     * @return list<array<string, mixed>>
     */
    private function runReport(GoogleAnalyticsWindowData $window, array $dimensions, array $metrics, int $limit = 1000): array
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post('https://analyticsdata.googleapis.com/v1beta/properties/' . $window->propertyId . ':runReport', [
                'dateRanges' => [[
                    'startDate' => $window->startsAt->toDateString(),
                    'endDate' => $window->endsAt->toDateString(),
                ]],
                'dimensions' => array_map(
                    fn (string $dimension): array => ['name' => $dimension],
                    $dimensions,
                ),
                'metrics' => array_map(
                    fn (string $metric): array => ['name' => $metric],
                    $metrics,
                ),
                'limit' => $limit,
            ]);

        if (! $response->successful()) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $response->json('rows', []);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function dimensionValues(array $row): array
    {
        return $this->values($row, 'dimensionValues');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function metricValues(array $row): array
    {
        return $this->values($row, 'metricValues');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function values(array $row, string $key): array
    {
        $values = $row[$key] ?? [];

        if (! is_array($values)) {
            return [];
        }

        $mappedValues = [];

        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }

            $mappedValue = $value['value'] ?? null;

            $mappedValues[] = is_string($mappedValue) ? $mappedValue : '';
        }

        return $mappedValues;
    }

    /**
     * @param  list<string>  $values
     */
    private function integerMetric(array $values, int $index): int
    {
        $value = $values[$index] ?? '0';

        return is_numeric($value) ? (int) round((float) $value) : 0;
    }

    /**
     * @param  list<string>  $values
     */
    private function floatMetric(array $values, int $index): float
    {
        $value = $values[$index] ?? '0';

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * @return array{client_email:string,private_key:string,token_uri?:string}
     */
    private function credentials(): array
    {
        $credentialsPath = $this->configuredCredentialsPath();

        if ($credentialsPath === '') {
            return ['client_email' => '', 'private_key' => ''];
        }

        try {
            $contents = file_get_contents($credentialsPath);

            if (! is_string($contents)) {
                return ['client_email' => '', 'private_key' => ''];
            }

            /** @var array{client_email?:string,private_key?:string,token_uri?:string} $credentials */
            $credentials = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['client_email' => '', 'private_key' => ''];
        }

        return [
            'client_email' => is_string($credentials['client_email'] ?? null) ? $credentials['client_email'] : '',
            'private_key' => is_string($credentials['private_key'] ?? null) ? $credentials['private_key'] : '',
            'token_uri' => is_string($credentials['token_uri'] ?? null) ? $credentials['token_uri'] : 'https://oauth2.googleapis.com/token',
        ];
    }

    private function accessToken(): string
    {
        $credentials = $this->credentials();
        $issuedAt = Date::now()->getTimestamp();
        $expiresAt = $issuedAt + 3600;
        $assertion = $this->jwt([
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ], $credentials['private_key']);

        $response = Http::asForm()->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        if (! $response->successful()) {
            return '';
        }

        return is_string($response->json('access_token')) ? $response->json('access_token') : '';
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function jwt(array $claims, string $privateKey): string
    {
        $segments = [
            $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];
        $signature = '';
        openssl_sign(implode('.', $segments), $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function configuredPropertyId(): string
    {
        $propertyId = $this->config['property_id'] ?? null;

        return is_string($propertyId) ? trim($propertyId) : '';
    }

    private function configuredCredentialsPath(): string
    {
        $credentialsPath = $this->config['credentials_path'] ?? null;

        return is_string($credentialsPath) ? trim($credentialsPath) : '';
    }
}
