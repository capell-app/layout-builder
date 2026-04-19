<?php

declare(strict_types=1);

namespace Capell\Themes\Core\SEO;

abstract class AbstractThemeSchemaGenerator
{
    abstract protected function resolveOrgName(): string;

    /** @return array<int, string> */
    abstract protected function resolveSameAs(): array;

    /** @return array<string, mixed> */
    public function organization(?string $url = null): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->resolveOrgName(),
            'url' => $url ?? '/',
        ];

        $logo = $this->resolveOrgLogo();
        if ($logo !== null) {
            $data['logo'] = $logo;
        }

        $description = $this->resolveOrgDescription();
        if ($description !== null) {
            $data['description'] = $description;
        }

        $sameAs = array_values(array_filter($this->resolveSameAs(), static fn (string $value): true => $value !== null));
        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function website(string $url, ?string $name = null): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => $url,
            'name' => $name ?? $this->resolveOrgName(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => rtrim($url, '/') . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumb(array $items): array
    {
        $elements = [];
        foreach (array_values($items) as $index => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
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
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];
    }

    /**
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
        ], static fn (string|array|null $value): bool => $value !== null);
    }

    /** @param  array<string, mixed>  $data */
    public function toJsonLd(array $data, bool $pretty = false): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return (string) json_encode($data, $flags);
    }

    protected function resolveOrgLogo(): ?string
    {
        return null;
    }

    protected function resolveOrgDescription(): ?string
    {
        return null;
    }
}
