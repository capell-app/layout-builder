<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\SEO;

use Capell\Themes\Core\Data\ThemeSettings;
use Capell\Themes\Saas\Data\SaasThemeSettings;

/**
 * Builds schema.org JSON-LD for common SaaS pages (Organization,
 * SoftwareApplication, Product, Offer, FAQPage, BreadcrumbList).
 *
 * Output is a safe string you can embed inside a
 * <script type="application/ld+json"> tag.
 */
class StructuredDataGenerator
{
    public function __construct(private readonly ThemeSettings $settings) {}

    /**
     * Build an Organization node.
     *
     * @return array<string, mixed>
     */
    public function organization(?string $url = null): array
    {
        $name = $this->settings instanceof SaasThemeSettings
            ? $this->settings->product_name
            : 'Capell';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $url ?? '/',
        ];

        if ($this->settings instanceof SaasThemeSettings) {
            if ($this->settings->product_description !== null) {
                $data['description'] = $this->settings->product_description;
            }
            $sameAs = array_filter([
                $this->settings->social_twitter,
                $this->settings->social_linkedin,
                $this->settings->social_github,
                $this->settings->social_youtube,
            ]);
            if (! empty($sameAs)) {
                $data['sameAs'] = array_values($sameAs);
            }
        }

        return $data;
    }

    /**
     * Build a SoftwareApplication node, ideal for SaaS homepages.
     *
     * @param  array{name?: string, description?: string, url?: string, screenshot?: string, applicationCategory?: string, operatingSystem?: string}  $overrides
     * @return array<string, mixed>
     */
    public function softwareApplication(array $overrides = []): array
    {
        $saas = $this->settings instanceof SaasThemeSettings ? $this->settings : null;

        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $overrides['name'] ?? ($saas->product_name ?? 'Capell'),
            'description' => $overrides['description'] ?? ($saas->product_description ?? null),
            'url' => $overrides['url'] ?? null,
            'screenshot' => $overrides['screenshot'] ?? ($saas->product_screenshot_url ?? null),
            'applicationCategory' => $overrides['applicationCategory'] ?? 'BusinessApplication',
            'operatingSystem' => $overrides['operatingSystem'] ?? 'Web',
        ], static fn ($v) => $v !== null);

        return $data;
    }

    /**
     * Build a Product node with embedded offers from pricing tiers.
     *
     * @param  string  $name
     * @param  array<int, array{name: string, price_monthly?: ?float|int, price_annual?: ?float|int, currency?: string, url?: string}>  $tiers
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
            $offers[] = [
                '@type' => 'Offer',
                'name' => $tier['name'] ?? '',
                'price' => (string) $monthly,
                'priceCurrency' => $tier['currency'] ?? 'USD',
                'url' => $tier['url'] ?? null,
                'availability' => 'https://schema.org/InStock',
            ];
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'description' => $description,
            'offers' => $offers,
        ], static fn ($v) => $v !== null && $v !== []);
    }

    /**
     * Build a BreadcrumbList node.
     *
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumb(array $items): array
    {
        $elements = [];
        foreach (array_values($items) as $i => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * Build an FAQPage node.
     *
     * @param  array<int, array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>
     */
    public function faq(array $faqs): array
    {
        $questions = [];
        foreach ($faqs as $faq) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];
    }

    /**
     * Build a WebSite node with a search action.
     *
     * @return array<string, mixed>
     */
    public function website(string $url, ?string $name = null): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => $url,
            'name' => $name ?? ($this->settings instanceof SaasThemeSettings ? $this->settings->product_name : 'Capell'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => rtrim($url, '/').'/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Render any structured data array as a JSON-LD string.
     *
     * @param  array<string, mixed>  $data
     */
    public function toJsonLd(array $data, bool $pretty = false): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return (string) json_encode($data, $flags);
    }
}
