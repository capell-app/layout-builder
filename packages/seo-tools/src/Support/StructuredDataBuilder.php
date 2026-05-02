<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support;

use LogicException;

class StructuredDataBuilder
{
    /** @var array<int, array<string, mixed>> */
    private array $schemas = [];

    public function organization(string $name, string $url, ?string $logo = null): static
    {
        $configurator = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $url,
        ];

        if ($logo !== null) {
            $configurator['logo'] = $logo;
        }

        $this->schemas[] = $configurator;

        return $this;
    }

    public function address(string $streetAddress, string $city, string $country, ?string $postalCode = null): static
    {
        $addressSchema = [
            '@type' => 'PostalAddress',
            'streetAddress' => $streetAddress,
            'addressLocality' => $city,
            'addressCountry' => $country,
        ];

        if ($postalCode !== null) {
            $addressSchema['postalCode'] = $postalCode;
        }

        throw_if($this->schemas === [], LogicException::class, 'address() requires an existing schema — call organization() first.');

        $lastIndex = count($this->schemas) - 1;

        $this->schemas[$lastIndex]['address'] = $addressSchema;

        return $this;
    }

    public function contactPoint(string $email, ?string $phone = null, string $contactType = 'customer service'): static
    {
        $contactSchema = [
            '@type' => 'ContactPoint',
            'email' => $email,
            'contactType' => $contactType,
        ];

        if ($phone !== null) {
            $contactSchema['telephone'] = $phone;
        }

        throw_if($this->schemas === [], LogicException::class, 'contactPoint() requires an existing schema — call organization() first.');

        $lastIndex = count($this->schemas) - 1;

        $this->schemas[$lastIndex]['contactPoint'] = $contactSchema;

        return $this;
    }

    public function webPage(string $name, string $description, string $url): static
    {
        $this->schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $name,
            'description' => $description,
            'url' => $url,
        ];

        return $this;
    }

    public function article(string $headline, string $description, string $url, string $datePublished, ?string $author = null): static
    {
        $configurator = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $headline,
            'description' => $description,
            'url' => $url,
            'datePublished' => $datePublished,
        ];

        if ($author !== null) {
            $configurator['author'] = [
                '@type' => 'Person',
                'name' => $author,
            ];
        }

        $this->schemas[] = $configurator;

        return $this;
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public function breadcrumbList(array $items): static
    {
        $listElements = [];

        foreach ($items as $position => $item) {
            $listElements[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        $this->schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listElements,
        ];

        return $this;
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $pairs
     */
    public function faqPage(array $pairs): static
    {
        $mainEntity = [];

        foreach ($pairs as $pair) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $pair['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $pair['answer'],
                ],
            ];
        }

        $this->schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->schemas;
    }

    public function render(): string
    {
        $output = '';

        foreach ($this->schemas as $configurator) {
            $json = json_encode(
                $configurator,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT,
            );
            $output .= '<script type="application/ld+json">' . $json . '</script>' . "\n";
        }

        return rtrim($output);
    }
}
