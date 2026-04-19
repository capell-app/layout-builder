<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\SEO;

use Capell\Themes\Agency\Data\AgencyThemeSettings;
use Capell\Themes\Core\Data\ThemeSettings;
use Capell\Themes\Core\SEO\AbstractThemeSchemaGenerator;

class StructuredDataGenerator extends AbstractThemeSchemaGenerator
{
    public function __construct(private readonly ThemeSettings $settings) {}

    /**
     * Build a CreativeWork node for portfolio / case study pages.
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
        ], static fn (string|array|null $value): bool => $value !== null);
    }

    protected function resolveOrgName(): string
    {
        return $this->settings instanceof AgencyThemeSettings
            ? $this->settings->organization_name
            : 'Capell';
    }

    protected function resolveOrgLogo(): ?string
    {
        return $this->settings instanceof AgencyThemeSettings
            ? $this->settings->organization_logo_url
            : null;
    }

    protected function resolveOrgDescription(): ?string
    {
        return $this->settings instanceof AgencyThemeSettings
            ? $this->settings->organization_description
            : null;
    }

    /** @return array<int, string> */
    protected function resolveSameAs(): array
    {
        if (! $this->settings instanceof AgencyThemeSettings) {
            return [];
        }

        return array_filter([
            $this->settings->social_instagram,
            $this->settings->social_dribbble,
            $this->settings->social_behance,
            $this->settings->social_linkedin,
        ], static fn ($value) => $value !== null);
    }
}
