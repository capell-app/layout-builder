<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Contracts;

interface SearchConsoleClientInterface
{
    public function isConfigured(): bool;

    /**
     * @return array<int, mixed>
     */
    public function pageInsights(string $url): array;

    /**
     * @return array<int, mixed>
     */
    public function decliningPages(int $siteId, int $limit = 10): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function urlMetricRows(int $siteId, int $limit = 100): array;
}
