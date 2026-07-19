<?php

declare(strict_types=1);

use Capell\Frontend\Contracts\FrontendComponentContributor;
use Capell\Frontend\Contracts\FrontendWidgetResourceUsageContributor;
use Capell\Frontend\Data\FrontendComponentContributionData;
use Capell\Frontend\Enums\FrontendComponentTarget;
use Capell\LayoutBuilder\Data\LayoutWidgets\LayoutWidgetDefinitionData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\Assets\PageContentLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\LayoutBuilderFrontendComponentContributor;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;

it('contributes layout widgets through frontend owned component contracts', function (): void {
    $contributors = collect(app()->tagged(FrontendComponentContributor::TAG));
    $contributor = $contributors->first(
        fn (mixed $candidate): bool => $candidate instanceof LayoutBuilderFrontendComponentContributor,
    );
    $widgets = app(LayoutWidgetRegistry::class);
    $widgets->register('interactive', LayoutWidgetTarget::FrontendLivewire, 'interactive-widget');
    $widgets->registerDefinition(LayoutWidgetDefinitionData::frontendInertia('inertia-only', 'InertiaOnly'));

    expect($contributor)
        ->toBeInstanceOf(LayoutBuilderFrontendComponentContributor::class)
        ->and($contributor->components())->each->toBeInstanceOf(FrontendComponentContributionData::class);

    $components = collect($contributor->components())->keyBy('name');

    expect($components->only(['content', 'image', 'title', 'interactive'])->values()->all())
        ->toEqual([
            new FrontendComponentContributionData('content', 'capell-layout-builder::layout-widgets.content', FrontendComponentTarget::Blade),
            new FrontendComponentContributionData('image', 'capell-layout-builder::layout-widgets.image', FrontendComponentTarget::Blade),
            new FrontendComponentContributionData('title', 'capell-layout-builder::layout-widgets.title', FrontendComponentTarget::Blade),
            new FrontendComponentContributionData('interactive', 'interactive-widget', FrontendComponentTarget::Livewire),
        ])
        ->and($components)->not->toHaveKey('inertia-only');

    $resolvedContributor = app(LayoutBuilderFrontendComponentContributor::class);
    expect($resolvedContributor)->toBe($contributor);

    app()->forgetScopedInstances();

    expect(app(LayoutBuilderFrontendComponentContributor::class))->not->toBe($resolvedContributor);
});

it('registers scoped resource usage contributors through the frontend owned contract', function (): void {
    $contributors = collect(app()->tagged(FrontendWidgetResourceUsageContributor::TAG));

    $layoutContributor = app(LayoutBuilderLayoutWidgetResourceUsageContributor::class);
    $pageContentContributor = app(PageContentLayoutWidgetResourceUsageContributor::class);

    expect($contributors->contains(
        fn (mixed $contributor): bool => $contributor instanceof LayoutBuilderLayoutWidgetResourceUsageContributor,
    ))->toBeTrue()
        ->and($contributors->contains(
            fn (mixed $contributor): bool => $contributor instanceof PageContentLayoutWidgetResourceUsageContributor,
        ))->toBeTrue()
        ->and(app(LayoutBuilderLayoutWidgetResourceUsageContributor::class))->toBe($layoutContributor)
        ->and(app(PageContentLayoutWidgetResourceUsageContributor::class))->toBe($pageContentContributor);

    app()->forgetScopedInstances();

    expect(app(LayoutBuilderLayoutWidgetResourceUsageContributor::class))->not->toBe($layoutContributor)
        ->and(app(PageContentLayoutWidgetResourceUsageContributor::class))->not->toBe($pageContentContributor);
});
