<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\SEO;

use Capell\Themes\Core\Data\ThemeSettings;
use Capell\Themes\Core\SEO\AbstractThemeSchemaGenerator;
use Capell\Themes\Corporate\Data\CorporateThemeSettings;

class StructuredDataGenerator extends AbstractThemeSchemaGenerator
{
    public function __construct(private readonly ThemeSettings $settings) {}

    protected function resolveOrgName(): string
    {
        return $this->settings instanceof CorporateThemeSettings
            ? $this->settings->organization_name
            : 'Capell';
    }

    protected function resolveOrgLogo(): ?string
    {
        return $this->settings instanceof CorporateThemeSettings
            ? $this->settings->organization_logo_url
            : null;
    }

    protected function resolveOrgDescription(): ?string
    {
        return $this->settings instanceof CorporateThemeSettings
            ? $this->settings->organization_description
            : null;
    }

    /** @return array<int, string> */
    protected function resolveSameAs(): array
    {
        if (! $this->settings instanceof CorporateThemeSettings) {
            return [];
        }

        return array_filter([
            $this->settings->social_twitter,
            $this->settings->social_linkedin,
            $this->settings->social_github,
        ], static fn (?string $value): bool => $value !== null);
    }
}
