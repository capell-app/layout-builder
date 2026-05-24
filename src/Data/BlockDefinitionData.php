<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Spatie\LaravelData\Data;

class BlockDefinitionData extends Data
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
                typeCreatorMethod: 'systemBlockType',
                meta: [
                    'component' => BlockComponentEnum::PageBreadcrumbs,
                ],
            ),
            new self(
                key: 'announcement-bar',
                name: __('capell-layout-builder::generic.announcement_bar'),
                typeCreatorMethod: 'defaultBlockType',
                meta: [
                    'component' => BlockComponentEnum::AnnouncementBar,
                    'container' => ContainerWidthEnum::Full,
                    'padding' => ['sm'],
                ],
                admin: [
                    'icon' => 'heroicon-o-megaphone',
                ],
                translations: [
                    'title' => __('capell-layout-builder::generic.announcement_bar'),
                    'content' => '<p>' . __('capell-layout-builder::generic.announcement_bar_description') . '</p>',
                ],
            ),
            new self(
                key: 'snippet',
                name: __('capell-layout-builder::generic.snippet'),
                typeCreatorMethod: 'defaultBlockType',
                meta: [
                    'component' => BlockComponentEnum::Snippet,
                    'container' => ContainerWidthEnum::Default,
                    'padding' => ['sm'],
                ],
                admin: [
                    'icon' => 'heroicon-o-code-bracket-square',
                ],
                translations: [
                    'title' => __('capell-layout-builder::generic.snippet'),
                    'content' => '<p>' . __('capell-layout-builder::generic.snippet_description') . '</p>',
                ],
            ),
            new self(
                key: 'children',
                name: __('capell-admin::generic.page_children'),
                typeCreatorMethod: 'resultsBlockType',
                meta: [
                    'component' => BlockComponentEnum::PageChildren,
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
                    'title' => __('capell-layout-builder::heading.page_children'),
                ],
            ),
            new self(
                key: 'assets',
                name: __('capell-layout-builder::generic.assets'),
                typeCreatorMethod: 'contentsBlockType',
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
                key: 'assets-block',
                name: 'Blocks',
                typeCreatorMethod: 'assetsBlockType',
                meta: [
                    'component' => BlockComponentEnum::AssetBlock,
                    'component_item' => FrontendComponentKeyEnum::SectionBlock->value,
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
                key: 'gallery',
                name: __('capell-admin::generic.gallery'),
                typeCreatorMethod: 'mediaBlockType',
                meta: [
                    'block_theme' => 'masonry',
                    'spacing' => 'md',
                    'margin' => ['lg'],
                    'container' => ContainerWidthEnum::Full,
                ],
                translations: [
                    'title' => __('capell-layout-builder::heading.gallery'),
                ],
            ),
            new self(
                key: 'latest-pages',
                name: __('capell-admin::generic.latest_pages'),
                typeCreatorMethod: 'resultsBlockType',
                meta: [
                    'component' => BlockComponentEnum::PageLatest,
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
                    'content' => '<p>' . __('capell-layout-builder::generic.latest_pages_description') . '</p>',
                ],
            ),
            new self(
                key: 'media-carousel',
                name: __('capell-admin::generic.media_carousel'),
                typeCreatorMethod: 'mediaBlockType',
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
                    'component' => BlockComponentEnum::AssetCarousel,
                    'limit' => 20,
                    'container' => ContainerWidthEnum::Full,
                    'background_color' => 'light-gray',
                    'spacing' => 'md',
                    'margin' => 0,
                    'padding' => ['md'],
                ],
                admin: [
                    'configurator' => 'Carousel',
                ],
            ),
            new self(
                key: 'page-content',
                name: __('capell-admin::generic.page_content'),
                typeCreatorMethod: 'pageContentBlockType',
                meta: [
                    'component' => BlockComponentEnum::PageContent,
                    'margin' => ['t-lg', 'b-xl'],
                    'page_content' => ['title', 'content'],
                ],
            ),
            new self(
                key: 'page-slot',
                name: __('capell-admin::generic.page_slot'),
                typeCreatorMethod: 'systemBlockType',
                meta: [
                    'component' => BlockComponentEnum::PageSlot,
                    'type' => 'slot',
                ],
            ),
            new self(
                key: 'pages-card',
                name: __('capell-admin::generic.pages_card'),
                typeCreatorMethod: 'pagesBlockType',
                meta: [
                    'component' => BlockComponentEnum::Pages,
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
                typeCreatorMethod: 'resultsBlockType',
                meta: [
                    'component' => BlockComponentEnum::PageSiblings,
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
                    'title' => __('capell-layout-builder::heading.page_siblings'),
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
                typeCreatorMethod: 'defaultBlockType',
            ),
            new self(
                key: 'assets-accordion',
                name: __('capell-layout-builder::generic.accordion'),
                typeCreatorMethod: 'contentsBlockType',
                meta: [
                    'icon' => 'heroicon-m-question-mark-circle',
                    'component' => BlockComponentEnum::AssetAccordion,
                    'margin' => ['lg'],
                    'align' => 'center',
                ],
                admin: [
                    'asset_types' => [
                        'section',
                    ],
                ],
            ),
            new self(
                key: 'assets-banner',
                name: 'Banner Showcase',
                typeCreatorMethod: 'contentsBlockType',
                meta: [
                    'align' => 'center',
                    'background_overlay' => true,
                    'component' => BlockComponentEnum::AssetBanner,
                ],
            ),
            new self(
                key: 'asset-features',
                name: 'Features',
                typeCreatorMethod: 'contentsBlockType',
                meta: [
                    'align' => 'center',
                    'component' => BlockComponentEnum::AssetFeatures,
                    'margin' => ['lg'],
                ],
            ),
            new self(
                key: 'asset-testimonials',
                name: 'Testimonials',
                typeCreatorMethod: 'contentsBlockType',
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
                    'component' => BlockComponentEnum::AssetTestimonials,
                ],
                admin: [
                    'configurator' => 'Carousel',
                ],
            ),
            new self(
                key: 'block-navigation',
                name: __('Navigation'),
                typeCreatorMethod: 'navigationBlockType',
                meta: [
                    'margin' => ['lg'],
                ],
                navigationKey: 'navigation',
                navigationName: 'Navigation',
            ),
            new self(
                key: 'block-navigation-tabs',
                name: __('Navigation'),
                typeCreatorMethod: 'navigationBlockType',
                meta: [
                    'margin' => ['lg'],
                    'component' => BlockComponentEnum::NavigationTabs,
                ],
                navigationKey: 'navigation-tabs',
                navigationName: 'Tabs',
            ),
            new self(
                key: 'banner-image',
                name: 'Banner Image',
                typeCreatorMethod: 'defaultBlockType',
                meta: [
                    'component' => BlockComponentEnum::BannerImage,
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
