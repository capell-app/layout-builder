<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\SearchConsole;

use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\SeoSuite\Actions\BuildDecliningSearchConsolePagesAction;
use Capell\SeoSuite\Contracts\SearchConsoleClientInterface;
use Capell\SeoSuite\Data\SearchConsoleInsightData;
use Capell\SeoSuite\Enums\SearchConsoleMetricEnum;
use Capell\SeoSuite\Enums\SeoIssueSeverityEnum;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

final class GoogleSearchConsoleClient implements SearchConsoleClientInterface
{
    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    /**
     * @param  array{enabled?: bool, credentials_path?: string|null, property_url?: string|null}  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function isConfigured(): bool
    {
        return ($this->config['enabled'] ?? false) === true
            && is_string($this->config['credentials_path'] ?? null)
            && trim($this->config['credentials_path']) !== '';
    }

    public function pageInsights(string $url): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $rows = $this->querySearchInsights($this->propertyUrl($url), [
                'startDate' => now()->subDays(30)->toDateString(),
                'endDate' => now()->subDay()->toDateString(),
                'dimensions' => ['page'],
                'dimensionFilterGroups' => [[
                    'filters' => [[
                        'dimension' => 'page',
                        'operator' => 'equals',
                        'expression' => $url,
                    ]],
                ]],
                'rowLimit' => 1,
            ]);
        } catch (Throwable) {
            return [];
        }

        /** @var array<string, mixed> $row */
        $row = $rows[0] ?? [];

        if ($row === []) {
            return [];
        }

        return [
            new SearchConsoleInsightData(
                metric: SearchConsoleMetricEnum::Clicks,
                message: __('capell-seo-suite::generic.search_console_clicks_summary'),
                value: $row['clicks'] ?? 0,
                severity: SeoIssueSeverityEnum::Notice,
            ),
            new SearchConsoleInsightData(
                metric: SearchConsoleMetricEnum::Impressions,
                message: __('capell-seo-suite::generic.search_console_impressions_summary'),
                value: $row['impressions'] ?? 0,
                severity: SeoIssueSeverityEnum::Notice,
            ),
            new SearchConsoleInsightData(
                metric: SearchConsoleMetricEnum::Ctr,
                message: __('capell-seo-suite::generic.search_console_ctr_summary'),
                value: $row['ctr'] ?? null,
                severity: SeoIssueSeverityEnum::Notice,
            ),
            new SearchConsoleInsightData(
                metric: SearchConsoleMetricEnum::Position,
                message: __('capell-seo-suite::generic.search_console_position_summary'),
                value: $row['position'] ?? null,
                severity: SeoIssueSeverityEnum::Notice,
            ),
        ];
    }

    public function decliningPages(int $siteId, int $limit = 10): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return BuildDecliningSearchConsolePagesAction::run($siteId, $limit);
    }

    public function urlMetricRows(int $siteId, int $limit = 100): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $propertyUrl = $this->sitePropertyUrl($siteId);

        if ($propertyUrl === null) {
            return [];
        }

        $currentWindowEnd = Date::now()->subDay();
        $currentWindowStart = $currentWindowEnd->copy()->subDays(27);
        $previousWindowEnd = $currentWindowStart->copy()->subDay();
        $previousWindowStart = $previousWindowEnd->copy()->subDays(27);

        try {
            $currentRows = $this->searchInsightsRowsByUrl($this->querySearchInsights($propertyUrl, [
                'startDate' => $currentWindowStart->toDateString(),
                'endDate' => $currentWindowEnd->toDateString(),
                'dimensions' => ['page'],
                'rowLimit' => $limit,
            ]));
            $previousRows = $this->searchInsightsRowsByUrl($this->querySearchInsights($propertyUrl, [
                'startDate' => $previousWindowStart->toDateString(),
                'endDate' => $previousWindowEnd->toDateString(),
                'dimensions' => ['page'],
                'rowLimit' => $limit,
            ]));
        } catch (Throwable) {
            return [];
        }

        $urls = array_values(array_unique([
            ...array_keys($currentRows),
            ...array_keys($previousRows),
        ]));

        return array_map(
            fn (string $url): array => $this->comparisonRow(
                url: $url,
                currentRow: $currentRows[$url] ?? [],
                previousRow: $previousRows[$url] ?? [],
                windowStart: $currentWindowStart->toDateString(),
                windowEnd: $currentWindowEnd->toDateString(),
            ),
            $urls,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function querySearchInsights(string $propertyUrl, array $payload): array
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post(
                'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($propertyUrl) . '/searchInsights/query',
                $payload,
            );

        if (! $response->successful()) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $response->json('rows', []);

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function searchInsightsRowsByUrl(array $rows): array
    {
        $rowsByUrl = [];

        foreach ($rows as $row) {
            $url = $this->searchInsightsRowUrl($row);

            if ($url === null) {
                continue;
            }

            $rowsByUrl[$url] = $row;
        }

        return $rowsByUrl;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function searchInsightsRowUrl(array $row): ?string
    {
        $keys = $row['keys'] ?? [];

        if (is_array($keys)) {
            $keyUrl = $keys[0] ?? null;

            if (is_string($keyUrl) && trim($keyUrl) !== '') {
                return $keyUrl;
            }
        }

        $url = $row['url'] ?? null;

        if (is_string($url) && trim($url) !== '') {
            return $url;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $currentRow
     * @param  array<string, mixed>  $previousRow
     * @return array{url: string, clicks: int, impressions: int, ctr: float, average_position: float, previous_clicks: int, previous_impressions: int, previous_ctr: float, previous_average_position: float, window_start: string, window_end: string}
     */
    private function comparisonRow(
        string $url,
        array $currentRow,
        array $previousRow,
        string $windowStart,
        string $windowEnd,
    ): array {
        return [
            'url' => $url,
            'clicks' => $this->integerMetric($currentRow, 'clicks'),
            'impressions' => $this->integerMetric($currentRow, 'impressions'),
            'ctr' => $this->floatMetric($currentRow, 'ctr'),
            'average_position' => $this->floatMetric($currentRow, 'position'),
            'previous_clicks' => $this->integerMetric($previousRow, 'clicks'),
            'previous_impressions' => $this->integerMetric($previousRow, 'impressions'),
            'previous_ctr' => $this->floatMetric($previousRow, 'ctr'),
            'previous_average_position' => $this->floatMetric($previousRow, 'position'),
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function integerMetric(array $row, string $key): int
    {
        $value = $row[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function floatMetric(array $row, string $key): float
    {
        $value = $row[$key] ?? 0.0;

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * @return array{client_email:string,private_key:string,token_uri?:string}
     */
    private function credentials(): array
    {
        $credentialsPath = $this->config['credentials_path'] ?? null;

        if (! is_string($credentialsPath) || trim($credentialsPath) === '') {
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

    private function propertyUrl(string $pageUrl): string
    {
        $configuredProperty = $this->config['property_url'] ?? null;

        if (is_string($configuredProperty) && trim($configuredProperty) !== '') {
            return $configuredProperty;
        }

        $scheme = parse_url($pageUrl, PHP_URL_SCHEME);
        $host = parse_url($pageUrl, PHP_URL_HOST);

        if (! is_string($scheme) || ! is_string($host)) {
            return $pageUrl;
        }

        return $scheme . '://' . $host . '/';
    }

    private function sitePropertyUrl(int $siteId): ?string
    {
        $configuredProperty = $this->config['property_url'] ?? null;

        if (is_string($configuredProperty) && trim($configuredProperty) !== '') {
            return $configuredProperty;
        }

        $site = Site::query()
            ->with('siteDomains')
            ->find($siteId);

        if (! $site instanceof Site) {
            return null;
        }

        $siteDomain = $site->siteDomains->first(fn (SiteDomain $domain): bool => $domain->default)
            ?? $site->siteDomains->first();

        $fullUrl = $siteDomain?->full_url;

        if (! is_string($fullUrl) || trim($fullUrl) === '') {
            return null;
        }

        return rtrim($fullUrl, '/') . '/';
    }
}
