<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Mosaic\Enums\AssetEnum;
use Capell\Mosaic\Enums\WidgetComponentEnum;
use Capell\Mosaic\Filament\Configurators\Widgets\CarouselWidgetConfigurator;
use Spatie\LaravelData\Data;

class WidgetDefinitionData extends Data
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $admin
     * @param  array<string, mixed>  $translations
     * @param  array<string, mixed>  $navigationItems
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $typeCreatorMethod,
        public readonly array $meta = [],
        public readonly array $admin = [],
        public readonly array $translations = [],
        public readonly ?string $navigationKey = null,
        public readonly ?string $navigationName = null,
        public readonly array $navigationItems = [],
    ) {}

    /**
     * @return array<int, self>
     */
    public static function defaultCatalog(): array
    {
        return [
            new self(
                key: 'breadcrumbs',
                name: __('capell-admin::generic.breadcrumbs'),
                typeCreatorMethod: 'systemWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::PageBreadcrumbs,
                ],
            ),
            new self(
                key: 'children',
                name: __('capell-admin::generic.page_children'),
                typeCreatorMethod: 'resultsWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::PageChildren,
                    'content_divider' => true,
                    'with_children_count' => true,
                    'with_summary' => true,
                    'with_image' => true,
                    'heading_style' => 'secondary',
                    'margin' => ['b-lg'],
                ],
                admin: [
                    'icon' => 'heroicon-c-users',
                ],
                translations: [
                    'title' => __('capell-mosaic::heading.page_children'),
                ],
            ),
            new self(
                key: 'assets',
                name: __('capell-mosaic::generic.assets'),
                typeCreatorMethod: 'contentsWidgetType',
                meta: [
                    'limit' => 6,
                    'pagination' => false,
                    'with_summary' => true,
                    'with_link_text' => true,
                    'with_image' => true,
                    'columns' => 1,
                ],
                admin: [
                    'icon' => 'heroicon-o-rectangle-stack',
                ],
            ),
            new self(
                key: 'gallery',
                name: __('capell-admin::generic.gallery'),
                typeCreatorMethod: 'mediaWidgetType',
                meta: [
                    'widget_theme' => 'masonry',
                    'spacing' => 'md',
                    'margin' => ['lg'],
                    'container' => ContainerWidthEnum::Full,
                ],
                translations: [
                    'title' => __('capell-mosaic::heading.gallery'),
                ],
            ),
            new self(
                key: 'latest-pages',
                name: __('capell-admin::generic.latest_pages'),
                typeCreatorMethod: 'resultsWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::PageLatest,
                    'content_divider' => true,
                    'limit' => 6,
                    'pagination' => false,
                    'with_summary' => true,
                    'with_link_text' => true,
                    'with_image' => true,
                    'columns' => 1,
                ],
                admin: [
                    'icon' => 'heroicon-o-rectangle-stack',
                ],
                translations: [
                    'title' => __('capell-admin::heading.latest_pages'),
                    'content' => '<p>' . __('capell-mosaic::generic.latest_pages_description') . '</p>',
                ],
            ),
            new self(
                key: 'media-carousel',
                name: __('capell-admin::generic.media_carousel'),
                typeCreatorMethod: 'mediaWidgetType',
                meta: [
                    'carousel_align' => 'center',
                    'carousel_arrows' => true,
                    'carousel_auto_delay' => 5000,
                    'carousel_auto_play' => true,
                    'carousel_disable_on_interaction' => true,
                    'carousel_drag' => true,
                    'carousel_effect' => 'slide',
                    'carousel_fade' => false,
                    'carousel_loop' => true,
                    'carousel_pagination' => false,
                    'carousel_pause_on_hover' => true,
                    'carousel_speed' => 300,
                    'carousel_touch' => true,
                    'carousel_wheel' => true,
                    'component' => WidgetComponentEnum::AssetCarousel,
                    'limit' => 20,
                    'container' => ContainerWidthEnum::Full,
                    'background_color' => 'light-gray',
                    'spacing' => 'md',
                    'margin' => 0,
                    'padding' => ['md'],
                ],
                admin: [
                    'configurator' => CarouselWidgetConfigurator::getKey(),
                ],
            ),
            new self(
                key: 'page-content',
                name: __('capell-admin::generic.page_content'),
                typeCreatorMethod: 'pageContentWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::PageContent,
                    'margin' => ['t-lg', 'b-xl'],
                    'page_content' => ['title', 'content'],
                ],
            ),
            new self(
                key: 'page-slot',
                name: __('capell-admin::generic.page_slot'),
                typeCreatorMethod: 'systemWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::PageSlot,
                    'type' => 'slot',
                ],
            ),
            new self(
                key: 'pages-card',
                name: __('capell-admin::generic.pages_card'),
                typeCreatorMethod: 'pagesWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::Pages,
                    'limit' => 10,
                    'with_image' => true,
                    'with_summary' => true,
                    'with_link_text' => true,
                    'spacing' => 'lg',
                    'margin' => ['lg'],
                ],
            ),
            new self(
                key: 'siblings',
                name: __('capell-admin::generic.page_siblings'),
                typeCreatorMethod: 'resultsWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::PageSiblings,
                    'content_divider' => true,
                    'with_children_count' => true,
                    'with_summary' => true,
                    'heading_style' => 'secondary',
                    'margin' => ['b-lg'],
                ],
                admin: [
                    'icon' => 'heroicon-c-user-group',
                ],
                translations: [
                    'title' => __('capell-mosaic::heading.page_siblings'),
                ],
            ),
        ];
    }

    /**
     * @return array<int, self>
     */
    public static function extraCatalog(): array
    {
        return [
            new self(
                key: 'default',
                name: 'Default Widget',
                typeCreatorMethod: 'defaultWidgetType',
            ),
            new self(
                key: 'assets-accordion',
                name: __('capell-mosaic::generic.accordion'),
                typeCreatorMethod: 'contentsWidgetType',
                meta: [
                    'icon' => 'heroicon-m-question-mark-circle',
                    'component' => WidgetComponentEnum::AssetAccordion,
                    'margin' => ['lg'],
                    'align' => 'center',
                ],
                admin: [
                    'asset_types' => [
                        AssetEnum::Section,
                    ],
                ],
            ),
            new self(
                key: 'assets-banner',
                name: 'Banner Showcase',
                typeCreatorMethod: 'contentsWidgetType',
                meta: [
                    'align' => 'center',
                    'background_overlay' => true,
                    'component' => WidgetComponentEnum::AssetBanner,
                ],
            ),
            new self(
                key: 'assets-block',
                name: 'Blocks',
                typeCreatorMethod: 'assetsWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::AssetBlock,
                    'component_item' => 'capell-mosaic::section.block',
                    'spacing' => 'none',
                    'columns' => 0,
                    'margin' => 'none',
                    'with_summary' => true,
                    'container' => ContainerWidthEnum::Small,
                ],
                admin: [
                    'icon' => 'heroicon-o-chart-bar',
                ],
            ),
            new self(
                key: 'asset-features',
                name: 'Features',
                typeCreatorMethod: 'contentsWidgetType',
                meta: [
                    'align' => 'center',
                    'component' => WidgetComponentEnum::AssetFeatures,
                    'margin' => ['lg'],
                ],
            ),
            new self(
                key: 'asset-testimonials',
                name: 'Testimonials',
                typeCreatorMethod: 'contentsWidgetType',
                meta: [
                    'align' => 'center',
                    'spacing' => 'none',
                    'background_overlay' => true,
                    'background_color' => DefaultColorEnum::Gray,
                    'carousel' => true,
                    'carousel_arrows' => false,
                    'carousel_auto_delay' => 5000,
                    'carousel_disable_on_interaction' => true,
                    'carousel_drag' => false,
                    'carousel_effect' => 'fade',
                    'carousel_fade' => true,
                    'carousel_auto_play' => true,
                    'carousel_loop' => true,
                    'carousel_pagination' => true,
                    'carousel_pause_on_hover' => true,
                    'carousel_speed' => 300,
                    'carousel_touch' => false,
                    'carousel_wheel' => false,
                    'component' => WidgetComponentEnum::AssetTestimonials,
                ],
                admin: [
                    'configurator' => CarouselWidgetConfigurator::getKey(),
                ],
            ),
            new self(
                key: 'widget-navigation',
                name: __('Navigation'),
                typeCreatorMethod: 'navigationWidgetType',
                meta: [
                    'margin' => ['lg'],
                ],
                navigationKey: 'navigation',
                navigationName: 'Navigation',
            ),
            new self(
                key: 'widget-navigation-tabs',
                name: __('Navigation'),
                typeCreatorMethod: 'navigationWidgetType',
                meta: [
                    'margin' => ['lg'],
                    'component' => 'capell-mosaic::widget.navigation.tabs',
                    'view_file' => 'capell-mosaic::components.widget.navigation.tabs',
                ],
                navigationKey: 'navigation-tabs',
                navigationName: 'Tabs',
            ),
            new self(
                key: 'banner-image',
                name: 'Banner Image',
                typeCreatorMethod: 'defaultWidgetType',
                meta: [
                    'component' => WidgetComponentEnum::BannerImage,
                    'margin' => ['none'],
                    'padding' => ['xl'],
                ],
            ),
        ];
    }

    public function hasNavigation(): bool
    {
        return $this->navigationKey !== null && $this->navigationName !== null;
    }
}
