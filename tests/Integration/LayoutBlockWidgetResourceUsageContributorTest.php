<?php

declare(strict_types=1);

use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBlockWidgetResourceUsageContributor;

it('contributes layout block resource usages from the public layout graph', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $type = Blueprint::factory()
        ->type(LayoutTypeEnum::Widget->value)
        ->create([
            'meta' => [
                'resource_groups' => ['package.block.carousel'],
                'presentation' => [
                    'loading_strategy' => PresentationLoadingStrategy::Idle->value,
                ],
            ],
        ]);
    $block = Widget::factory()->create([
        'blueprint_id' => $type->getKey(),
        'key' => 'feature-carousel',
    ]);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    [
                        'widget_key' => $block->key,
                        'occurrence' => 2,
                        'meta' => [
                            'resource_groups' => ['app.block.lightbox'],
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

    $usages = (new LayoutBlockWidgetResourceUsageContributor)->usages(new FrontendRenderContextData(
        page: $page,
        site: $site,
        language: $language,
        layout: $layout,
        theme: null,
    ));

    expect($usages)->toHaveCount(2)
        ->and(collect($usages)->pluck('resourceGroup')->all())->toBe([
            'package.block.carousel',
            'app.block.lightbox',
        ])
        ->and($usages[0]->widgetKey)->toBe('feature-carousel')
        ->and($usages[0]->publicId)->toBe(LayoutBlockWidgetResourceUsageContributor::publicId(
            'feature-carousel',
            'package.block.carousel',
            'main',
            2,
        ))
        ->and($usages[0]->presentation->loadingStrategy)->toBe(PresentationLoadingStrategy::Visible)
        ->and($usages[1]->presentation->loadingStrategy)->toBe(PresentationLoadingStrategy::Visible);
});
