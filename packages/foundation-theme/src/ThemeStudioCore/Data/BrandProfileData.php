<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Core\Data;

use Spatie\LaravelData\Data;

class BrandProfileData extends Data
{
    public function __construct(
        public string $primaryColor = '#1a2d6d',
        public string $accentColor = '#f59e0b',
        public string $neutralColor = '#111827',
        public string $headingFont = 'inter',
        public string $bodyFont = 'inter',
        public string $spacing = 'balanced',
        public string $alignment = 'left',
        public string $cardStyle = 'subtle',
        public string $navigationStyle = 'standard',
        public string $layoutPresentation = 'structured',
        public string $motionIntensity = 'subtle',
        public string $mediaTreatment = 'natural',
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public function merge(array $values): self
    {
        return new self(
            primaryColor: (string) ($values['primaryColor'] ?? $this->primaryColor),
            accentColor: (string) ($values['accentColor'] ?? $this->accentColor),
            neutralColor: (string) ($values['neutralColor'] ?? $this->neutralColor),
            headingFont: (string) ($values['headingFont'] ?? $this->headingFont),
            bodyFont: (string) ($values['bodyFont'] ?? $this->bodyFont),
            spacing: (string) ($values['spacing'] ?? $this->spacing),
            alignment: (string) ($values['alignment'] ?? $this->alignment),
            cardStyle: (string) ($values['cardStyle'] ?? $this->cardStyle),
            navigationStyle: (string) ($values['navigationStyle'] ?? $this->navigationStyle),
            layoutPresentation: (string) ($values['layoutPresentation'] ?? $this->layoutPresentation),
            motionIntensity: (string) ($values['motionIntensity'] ?? $this->motionIntensity),
            mediaTreatment: (string) ($values['mediaTreatment'] ?? $this->mediaTreatment),
        );
    }

    /**
     * @return array<string, string>
     */
    public function tokens(): array
    {
        return [
            '--theme-primary' => $this->primaryColor,
            '--theme-accent' => $this->accentColor,
            '--theme-neutral' => $this->neutralColor,
            '--theme-heading-font' => $this->fontStack($this->headingFont),
            '--theme-body-font' => $this->fontStack($this->bodyFont),
            '--theme-spacing' => $this->spacing,
            '--theme-alignment' => $this->alignment,
            '--theme-card-style' => $this->cardStyle,
            '--theme-navigation-style' => $this->navigationStyle,
            '--theme-layout-presentation' => $this->layoutPresentation,
            '--theme-motion-intensity' => $this->motionIntensity,
            '--theme-media-treatment' => $this->mediaTreatment,
        ];
    }

    private function fontStack(string $font): string
    {
        return match ($font) {
            'playfair' => "'Playfair Display', Georgia, serif",
            'sora' => "'Sora', 'Inter', system-ui, sans-serif",
            'manrope' => "'Manrope', 'Inter', system-ui, sans-serif",
            default => "'Inter', system-ui, sans-serif",
        };
    }
}
