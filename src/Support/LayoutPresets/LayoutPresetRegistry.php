<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\LayoutPresets;

use Capell\LayoutBuilder\Data\LayoutPresetData;

class LayoutPresetRegistry
{
    /** @var array<string, LayoutPresetData> */
    private array $presets = [];

    public function __construct()
    {
        $this->register(new LayoutPresetData(
            key: 'sidebar-main-footer',
            label: 'Sidebar, main, footer',
            description: 'Sidebar container, primary content area, and full-width footer.',
            containers: ['sidebar', 'main', 'footer'],
            sections: ['hero', 'content', 'signup-footer'],
        ));

        $this->register(new LayoutPresetData(
            key: 'landing',
            label: 'Landing page',
            description: 'Hero, proof, feature grid, and call to action.',
            containers: ['main'],
            sections: ['hero', 'proof', 'features', 'cta'],
        ));

        $this->register(new LayoutPresetData(
            key: 'capell-editorial-hero-slideshow',
            label: 'Capell editorial hero slideshow',
            description: 'Immersive hero with editorial copy, actions, and asset-backed slide rails.',
            containers: ['main'],
            sections: ['hero-banner'],
        ));

        $this->register(new LayoutPresetData(
            key: 'capell-immersive-gallery-band',
            label: 'Capell immersive gallery band',
            description: 'Full-width gallery section for media, assets, captions, and fallback panels.',
            containers: ['main'],
            sections: ['image-gallery'],
        ));

        $this->register(new LayoutPresetData(
            key: 'capell-builder-workflow-row',
            label: 'Capell builder workflow row',
            description: 'Numbered process row for consultation, build, publishing, and monitoring flows.',
            containers: ['main'],
            sections: ['process-steps'],
        ));

        $this->register(new LayoutPresetData(
            key: 'capell-testimonial-video-panel',
            label: 'Capell testimonial video panel',
            description: 'Proof section combining testimonial copy, avatar/media assets, and video-style controls.',
            containers: ['main'],
            sections: ['testimonials'],
        ));

        $this->register(new LayoutPresetData(
            key: 'capell-mixed-content-showcase',
            label: 'Capell mixed content showcase',
            description: 'Feature list and alternating content for pages, assets, media, and navigation.',
            containers: ['main'],
            sections: ['feature-list', 'alternating-content'],
        ));

        $this->register(new LayoutPresetData(
            key: 'capell-project-grid',
            label: 'Capell project grid',
            description: 'Reusable project index card grid for implementation and case-study pages.',
            containers: ['main'],
            sections: ['card-grid'],
        ));

        $this->register(new LayoutPresetData(
            key: 'capell-blog-resource-grid',
            label: 'Capell blog and resource grid',
            description: 'Editorial card grid for blog posts, guides, resources, and launch content.',
            containers: ['main'],
            sections: ['card-grid'],
        ));

        $this->register(new LayoutPresetData(
            key: 'capell-contact-cta',
            label: 'Capell contact CTA',
            description: 'Conversion band for scoping, support, implementation, and contact routes.',
            containers: ['main'],
            sections: ['cta-section'],
        ));
    }

    public function register(LayoutPresetData $preset): void
    {
        $this->presets[$preset->key] = $preset;
    }

    /**
     * @return array<int, LayoutPresetData>
     */
    public function all(): array
    {
        return array_values($this->presets);
    }

    public function bestMatch(string $prompt): LayoutPresetData
    {
        $normalizedPrompt = strtolower($prompt);

        if (str_contains($normalizedPrompt, 'sidebar')) {
            return $this->presets['sidebar-main-footer'];
        }

        if (str_contains($normalizedPrompt, 'gallery')) {
            return $this->presets['capell-immersive-gallery-band'];
        }

        if (str_contains($normalizedPrompt, 'project')) {
            return $this->presets['capell-project-grid'];
        }

        if (str_contains($normalizedPrompt, 'blog') || str_contains($normalizedPrompt, 'resource')) {
            return $this->presets['capell-blog-resource-grid'];
        }

        if (str_contains($normalizedPrompt, 'testimonial') || str_contains($normalizedPrompt, 'video')) {
            return $this->presets['capell-testimonial-video-panel'];
        }

        if (str_contains($normalizedPrompt, 'workflow') || str_contains($normalizedPrompt, 'process')) {
            return $this->presets['capell-builder-workflow-row'];
        }

        if (str_contains($normalizedPrompt, 'contact') || str_contains($normalizedPrompt, 'cta')) {
            return $this->presets['capell-contact-cta'];
        }

        if (str_contains($normalizedPrompt, 'hero')) {
            return $this->presets['capell-editorial-hero-slideshow'];
        }

        return $this->presets['landing'];
    }
}
