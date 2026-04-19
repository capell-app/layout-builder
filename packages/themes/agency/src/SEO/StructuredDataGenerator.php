<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\SEO;

use Capell\Themes\Agency\Data\AgencyThemeSettings;
use Capell\Themes\Core\Data\ThemeSettings;

/**
 * Builds schema.org JSON-LD for common Agency pages (Organization, WebSite,
 * CreativeWork / Article for portfolio pieces, BreadcrumbList, FAQPage).
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
        $name = $this->settings instanceof AgencyThemeSettings
            ? $this->settings->organization_name
            : 'Capell';

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $url ?? '/',
        ];

        if ($this->settings instanceof AgencyThemeSettings) {
            if ($this->settings->organization_logo_url !== null) {
                $data['logo'] = $this->settings->organization_logo_url;
            }
            if ($this->settings->organization_description !== null) {
                $data['description'] = $this->settings->organization_description;
            }
            $sameAs = array_filter([
                $this->settings->social_instagram,
                $this->settings->social_dribbble,
                $this->settings->social_behance,
                $this->settings->social_linkedin,
            ]);
            if (! empty($sameAs)) {
                $data['sameAs'] = array_values($sameAs);
            }
        }

        return $data;
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
            'name' => $name ?? ($this->settings instanceof AgencyThemeSettings ? $this->settings->organization_name : 'Capell'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => rtrim($url, '/').'/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Build a CreativeWork node, used for portfolio / case study pages.
     *
     * @param  array{name: string, description?: string, image?: string, creator?: string, dateCreated?: string, url?: string, keywords?: array<int, string>}  $work
     * @return array<string, mixed>
     */
    public function creativeWork(array $work): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $work['name'],
            'description' => $work['description'] ?? null,
            'image' => $work['image'] ?? null,
            'creator' => isset($work['creator']) ? ['@type' => 'Organization', 'name' => $work['creator']] : null,
            'dateCreated' => $work['dateCreated'] ?? null,
            'url' => $work['url'] ?? null,
            'keywords' => $work['keywords'] ?? null,
        ], static fn ($v) => $v !== null);
    }

    /**
     * Build an Article node.
     *
     * @param  array{headline: string, description?: string, image?: string, datePublished?: string, dateModified?: string, author?: string, url?: string}  $article
     * @return array<string, mixed>
     */
    public function article(array $article): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['headline'],
            'description' => $article['description'] ?? null,
            'image' => $article['image'] ?? null,
            'datePublished' => $article['datePublished'] ?? null,
            'dateModified' => $article['dateModified'] ?? ($article['datePublished'] ?? null),
            'author' => isset($article['author']) ? ['@type' => 'Person', 'name' => $article['author']] : null,
            'mainEntityOfPage' => $article['url'] ?? null,
        ], static fn ($v) => $v !== null);
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
