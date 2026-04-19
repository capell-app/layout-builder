<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\SEO;

use Capell\Themes\Core\Data\ThemeSettings;
use Capell\Themes\Core\SEO\AbstractThemeSchemaGenerator;
use Capell\Themes\Saas\Data\SaasThemeSettings;

class StructuredDataGenerator extends AbstractThemeSchemaGenerator
{
    public function __construct(private readonly ThemeSettings $settings) {}

    /**
     * Build a SoftwareApplication node for SaaS homepages.
     *
     * @param  array{name?: string, description?: string, url?: string, screenshot?: string, applicationCategory?: string, operatingSystem?: string}  $overrides
     * @return array<string, mixed>
     */
    public function softwareApplication(array $overrides = []): array
    {
        $saas = $this->settings instanceof SaasThemeSettings ? $this->settings : null;

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $overrides['name'] ?? ($saas?->product_name ?? 'Capell'),
            'description' => $overrides['description'] ?? $saas?->product_description,
            'url' => $overrides['url'] ?? null,
            'screenshot' => $overrides['screenshot'] ?? $saas?->product_screenshot_url,
            'applicationCategory' => $overrides['applicationCategory'] ?? 'BusinessApplication',
            'operatingSystem' => $overrides['operatingSystem'] ?? 'Web',
        ], static fn (?string $value): bool => $value !== null);
    }

    /**
     * Build a Product node with embedded Offer nodes from pricing tiers.
     *
     * @param  array<int, array{name: string, price_monthly?: float|int|null, price_annual?: float|int|null, currency?: string, url?: string}>  $tiers
     * @return array<string, mixed>
     */
    public function product(string $name, array $tiers, ?string $description = null): array
    {
        $offers = [];
        foreach ($tiers as $tier) {
            $monthly = $tier['price_monthly'] ?? null;
            if ($monthly === null) {
                continue;
            }

            $offers[] = array_filter([
                '@type' => 'Offer',
                'name' => $tier['name'] ?? '',
                'price' => (string) $monthly,
                'priceCurrency' => $tier['currency'] ?? 'USD',
                'url' => $tier['url'] ?? null,
                'availability' => 'https://schema.org/InStock',
            ], static fn (?string $value): bool => $value !== null && $value !== '');
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'description' => $description,
            'offers' => $offers ?? null,
        ], static fn ($value): bool => $value !== null);
    }

    protected function resolveOrgName(): string
    {
        return $this->settings instanceof SaasThemeSettings
            ? $this->settings->product_name
            : 'Capell';
    }

    protected function resolveOrgDescription(): ?string
    {
        return $this->settings instanceof SaasThemeSettings
            ? $this->settings->product_description
            : null;
    }

    /** @return array<int, string> */
    protected function resolveSameAs(): array
    {
        if (! $this->settings instanceof SaasThemeSettings) {
            return [];
        }

        return array_filter([
            $this->settings->social_twitter,
            $this->settings->social_linkedin,
            $this->settings->social_github,
            $this->settings->social_youtube,
        ], static fn ($value) => $value !== null);
    }
}
