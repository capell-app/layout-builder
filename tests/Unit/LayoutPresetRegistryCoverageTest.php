<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\ListLayoutPresetsAction;
use Capell\LayoutBuilder\Actions\PreviewLayoutPlanAction;
use Capell\LayoutBuilder\Data\LayoutPresetData;
use Capell\LayoutBuilder\Support\LayoutPresets\LayoutPresetRegistry;

it('lists the built in layout presets in registration order', function (): void {
    $presets = ListLayoutPresetsAction::run();

    expect($presets)
        ->toHaveCount(10)
        ->each->toBeInstanceOf(LayoutPresetData::class)
        ->and($presets[0]->key)->toBe('sidebar-main-footer')
        ->and($presets[1]->key)->toBe('landing')
        ->and(collect($presets)->pluck('key')->all())->toContain(
            'capell-editorial-hero-slideshow',
            'capell-immersive-gallery-band',
            'capell-builder-workflow-row',
            'capell-testimonial-video-panel',
            'capell-mixed-content-showcase',
            'capell-project-grid',
            'capell-blog-resource-grid',
            'capell-contact-cta',
        );
});

it('matches layout plan prompts to the most relevant built in preset', function (
    string $prompt,
    string $expectedPreset,
    array $expectedSections,
): void {
    $plan = PreviewLayoutPlanAction::run($prompt)->plan;

    expect($plan->prompt)->toBe($prompt)
        ->and($plan->presetKey)->toBe($expectedPreset)
        ->and($plan->sections)->toBe($expectedSections);
})->with([
    'sidebar' => ['Put a sidebar beside the main content', 'sidebar-main-footer', ['hero', 'content', 'signup-footer']],
    'gallery' => ['Build an immersive media gallery', 'capell-immersive-gallery-band', ['image-gallery']],
    'project' => ['Show a project index', 'capell-project-grid', ['card-grid']],
    'blog' => ['Create a blog resource hub', 'capell-blog-resource-grid', ['card-grid']],
    'testimonial' => ['Add testimonial video proof', 'capell-testimonial-video-panel', ['testimonials']],
    'workflow' => ['Explain the workflow process', 'capell-builder-workflow-row', ['process-steps']],
    'contact' => ['Add a contact CTA', 'capell-contact-cta', ['cta-section']],
    'hero' => ['Start with an editorial hero', 'capell-editorial-hero-slideshow', ['hero-banner']],
    'fallback' => ['Make a conversion page', 'landing', ['hero', 'proof', 'features', 'cta']],
]);

it('allows package code to override a registered preset key', function (): void {
    $registry = new LayoutPresetRegistry;

    $registry->register(new LayoutPresetData(
        key: 'landing',
        label: 'Custom landing',
        description: 'Package supplied landing preset.',
        containers: ['hero', 'main'],
        sections: ['custom-hero'],
    ));

    $preset = $registry->bestMatch('plain landing page');

    expect($preset->label)->toBe('Custom landing')
        ->and($preset->containers)->toBe(['hero', 'main'])
        ->and($preset->sections)->toBe(['custom-hero']);
});
