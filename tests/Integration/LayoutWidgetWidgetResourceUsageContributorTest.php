<?php

declare(strict_types=1);

use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Data\FrontendAssetContextData;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\Frontend\Support\Assets\ThemeResourceResolver;
use Capell\LayoutBuilder\Data\Assets\LayoutWidgetResourceUsageData;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Assets\LayoutWidgetResourceAssetContributor;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;

it('contributes layout widget resource usages from the public layout graph', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $type = Blueprint::factory()
        ->type(LayoutTypeEnum::Widget->value)
        ->create([
            'meta' => [
                'resource_groups' => ['package.widget.carousel'],
                'presentation' => [
                    'loading_strategy' => PresentationLoadingStrategy::Idle->value,
                ],
            ],
        ]);
    $widget = Widget::factory()->create([
        'blueprint_id' => $type->getKey(),
        'key' => 'feature-carousel',
    ]);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => $widget->key,
                        'occurrence' => 2,
                        'meta' => [
                            'resource_groups' => ['app.widget.lightbox'],
                            'presentation' => [
                                'loading_strategy' => PresentationLoadingStrategy::Visible->value,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    $usages = (new LayoutBuilderLayoutWidgetResourceUsageContributor)->usages(new FrontendRenderContextData(
        page: $page,
        site: $site,
        language: $language,
        layout: $layout,
        theme: null,
    ));

    expect($usages)->toHaveCount(2)
        ->and(collect($usages)->pluck('resourceGroup')->all())->toBe([
            'package.widget.carousel',
            'app.widget.lightbox',
        ])
        ->and($usages[0]->widgetKey)->toBe('feature-carousel')
        ->and($usages[0]->publicId)->toBe(LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId('package.widget.carousel'))
        ->and($usages[0]->presentation->loadingStrategy)->toBe(PresentationLoadingStrategy::Visible)
        ->and($usages[1]->presentation->loadingStrategy)->toBe(PresentationLoadingStrategy::Visible);
});

it('deduplicates lazy resource requirements when a widget resource is used multiple times', function (): void {
    $registry = new FrontendResourceRegistry;
    $registry
        ->group('theme.carousel')
        ->js('resources/js/widgets/carousel.js', buildPath: 'build', loading: PresentationLoadingStrategy::Visible);

    $publicId = LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId('theme.carousel');
    $requirements = (new LayoutWidgetResourceAssetContributor($registry, new ThemeResourceResolver))->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::FullLivewire),
        widgetResourceUsages: [
            new LayoutWidgetResourceUsageData(
                widgetKey: 'carousel',
                resourceGroup: 'theme.carousel',
                publicId: $publicId,
                presentation: resolve(ResolvePresentationSettingsAction::class)->handle(),
            ),
            new LayoutWidgetResourceUsageData(
                widgetKey: 'carousel',
                resourceGroup: 'theme.carousel',
                publicId: $publicId,
                presentation: resolve(ResolvePresentationSettingsAction::class)->handle(),
            ),
        ],
    ));

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]->source)->toBe('resources/js/widgets/carousel.js')
        ->and($requirements[0]->condition)->toBe($publicId);
});

it('resolves widget resource groups from the active theme resource layer', function (): void {
    $theme = new Theme;
    $theme->meta = [
        'editor' => [
            'resources' => [
                'theme.carousel' => [
                    'assets' => [
                        [
                            'source' => 'resources/js/widgets/carousel.js',
                            'loading' => PresentationLoadingStrategy::Visible->value,
                        ],
                    ],
                ],
            ],
        ],
    ];
    $publicId = LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId('theme.carousel');

    $requirements = (new LayoutWidgetResourceAssetContributor(new FrontendResourceRegistry, new ThemeResourceResolver))->requirements(new FrontendAssetContextData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: $theme,
        runtime: FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::FullLivewire),
        widgetResourceUsages: [
            new LayoutWidgetResourceUsageData(
                widgetKey: 'carousel',
                resourceGroup: 'theme.carousel',
                publicId: $publicId,
                presentation: resolve(ResolvePresentationSettingsAction::class)->handle(),
            ),
        ],
    ));

    expect($requirements)->toHaveCount(1)
        ->and($requirements[0]->source)->toBe('resources/js/widgets/carousel.js')
        ->and($requirements[0]->buildPath)->toBe('build')
        ->and($requirements[0]->condition)->toBe($publicId);
});
