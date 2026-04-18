<?php

declare(strict_types=1);

test('all modern widget blade components exist', function (): void {
    $widgets = [
        'hero-banner',
        'card-grid',
        'feature-list',
        'stats-section',
        'testimonials',
        'team-members',
        'pricing-table',
        'faq-section',
        'image-gallery',
        'alternating-content',
        'process-steps',
        'cta-section',
    ];

    $basePath = base_path('packages/mosaic/resources/views/components/modern/');

    foreach ($widgets as $widget) {
        $filePath = "{$basePath}{$widget}.blade.php";
        expect(file_exists($filePath))
            ->toBeTrue("Widget component {$widget}.blade.php not found at {$filePath}");
    }
});

test('design tokens css file exists', function (): void {
    $path = base_path('packages/mosaic/resources/css/design-tokens.css');
    expect(file_exists($path))
        ->toBeTrue('Design tokens CSS file not found');
});

test('widget blade components have valid syntax', function (): void {
    $widgets = [
        'hero-banner',
        'card-grid',
        'feature-list',
        'stats-section',
        'testimonials',
        'team-members',
        'pricing-table',
        'faq-section',
        'image-gallery',
        'alternating-content',
        'process-steps',
        'cta-section',
    ];

    $basePath = base_path('packages/mosaic/resources/views/components/modern/');

    foreach ($widgets as $widget) {
        $filePath = "{$basePath}{$widget}.blade.php";
        $content = file_get_contents($filePath);

        expect($content)->toContain('@props', "{$widget}: Missing @props declaration");
        expect($content)->not()->toBeEmpty("{$widget}: File is empty");
        expect($content)->toContain('<', "{$widget}: Missing HTML tags");
    }
});

test('all widget filament schemas exist', function (): void {
    $schemas = [
        'ModernHeroBannerSchema',
        'ModernCardGridSchema',
        'ModernFeatureListSchema',
        'ModernStatsSectionSchema',
        'ModernTestimonialsSchema',
        'ModernTeamMembersSchema',
        'ModernPricingTableSchema',
        'ModernImageGallerySchema',
        'ModernAlternatingContentSchema',
        'ModernProcessStepsSchema',
        'ModernFaqSchema',
    ];

    $basePath = base_path('packages/mosaic/src/Filament/Schemas/Widgets/');

    foreach ($schemas as $schema) {
        $filePath = "{$basePath}{$schema}.php";
        expect(file_exists($filePath))
            ->toBeTrue("Filament schema {$schema}.php not found at {$filePath}");
    }
});

test('filament schema files have valid php syntax', function (): void {
    $schemas = [
        'ModernHeroBannerSchema',
        'ModernCardGridSchema',
        'ModernFeatureListSchema',
        'ModernStatsSectionSchema',
        'ModernTestimonialsSchema',
        'ModernTeamMembersSchema',
        'ModernPricingTableSchema',
        'ModernImageGallerySchema',
        'ModernAlternatingContentSchema',
        'ModernProcessStepsSchema',
        'ModernFaqSchema',
    ];

    $basePath = base_path('packages/mosaic/src/Filament/Schemas/Widgets/');

    foreach ($schemas as $schema) {
        $filePath = "{$basePath}{$schema}.php";
        $content = file_get_contents($filePath);

        expect($content)->toContain('class ' . $schema, "{$schema}: Class declaration not found");
        expect($content)->toContain('declare(strict_types=1)', "{$schema}: Missing declare(strict_types=1)");
    }
});

test('hero banner component has video prop', function (): void {
    $content = file_get_contents(base_path('packages/mosaic/resources/views/components/modern/hero-banner.blade.php'));

    expect($content)->toContain('videoUrl', 'Hero banner missing videoUrl prop');
    expect($content)->toContain('parallax', 'Hero banner missing parallax prop');
    expect($content)->toContain('<video', 'Hero banner missing video element');
});

test('card grid component has badge support', function (): void {
    $content = file_get_contents(base_path('packages/mosaic/resources/views/components/modern/card-grid.blade.php'));

    expect($content)->toContain('badge', 'Card grid missing badge support');
    expect($content)->toContain('hoverEffect', 'Card grid missing hoverEffect prop');
});

test('feature list component has animation support', function (): void {
    $content = file_get_contents(base_path('packages/mosaic/resources/views/components/modern/feature-list.blade.php'));

    expect($content)->toContain('animation', 'Feature list missing animation prop');
    expect($content)->toContain('fade-in', 'Feature list missing fade-in animation');
    expect($content)->toContain('animation-delay', 'Feature list missing staggered animation delays');
});

test('testimonials component has carousel support', function (): void {
    $content = file_get_contents(base_path('packages/mosaic/resources/views/components/modern/testimonials.blade.php'));

    expect($content)->toContain('displayMode', 'Testimonials missing displayMode prop');
    expect($content)->toContain('carousel', 'Testimonials missing carousel support');
});

test('pricing table component has billing toggle', function (): void {
    $content = file_get_contents(base_path('packages/mosaic/resources/views/components/modern/pricing-table.blade.php'));

    expect($content)->toContain('billingOptions', 'Pricing table missing billingOptions prop');
    expect($content)->toContain('toggleBilling', 'Pricing table missing billing toggle function');
});

test('team members component has social media support', function (): void {
    $content = file_get_contents(base_path('packages/mosaic/resources/views/components/modern/team-members.blade.php'));

    expect($content)->toContain('social', 'Team members missing social prop');
    expect($content)->toContain('tags', 'Team members missing tags prop');
});

test('faq component has category filtering', function (): void {
    $content = file_get_contents(base_path('packages/mosaic/resources/views/components/modern/faq-section.blade.php'));

    expect($content)->toContain('categories', 'FAQ missing categories prop');
    expect($content)->toContain('filterFaqCategory', 'FAQ missing category filter function');
});
