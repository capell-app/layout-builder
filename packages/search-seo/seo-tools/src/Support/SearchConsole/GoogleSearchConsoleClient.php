<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\SearchConsole;

use Capell\SeoTools\Actions\BuildDecliningSearchConsolePagesAction;
use Capell\SeoTools\Contracts\SearchConsoleClientInterface;
use Capell\SeoTools\Data\SearchConsoleInsightData;
use Capell\SeoTools\Enums\SearchConsoleMetricEnum;
use Capell\SeoTools\Enums\SeoIssueSeverityEnum;
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
            $rows = $this->querySearchAnalytics($this->propertyUrl($url), [
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
                message: __('capell-seo-tools::generic.search_console_clicks_summary'),
                value: $row['clicks'] ?? 0,
                severity: SeoIssueSeverityEnum::Notice,
            ),
            new SearchConsoleInsightData(
                metric: SearchConsoleMetricEnum::Impressions,
                message: __('capell-seo-tools::generic.search_console_impressions_summary'),
                value: $row['impressions'] ?? 0,
                severity: SeoIssueSeverityEnum::Notice,
            ),
            new SearchConsoleInsightData(
                metric: SearchConsoleMetricEnum::Ctr,
                message: __('capell-seo-tools::generic.search_console_ctr_summary'),
                value: $row['ctr'] ?? null,
                severity: SeoIssueSeverityEnum::Notice,
            ),
            new SearchConsoleInsightData(
                metric: SearchConsoleMetricEnum::Position,
                message: __('capell-seo-tools::generic.search_console_position_summary'),
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

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function querySearchAnalytics(string $propertyUrl, array $payload): array
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post(
                'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode($propertyUrl) . '/searchAnalytics/query',
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
}
