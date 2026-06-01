<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\LayoutBuilder\Actions\InstallLayoutBuilderBlockCatalogAction;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BlockCreator
{
    private const string NavigationPackage = 'capell-app/navigation';

    /**
     * @var class-string<Widget>
     */
    private readonly string $blockModel;

    public function __construct()
    {
        $this->blockModel = Widget::class;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createBlocks(Collection $languages, bool $extraBlocks = false): void
    {
        InstallLayoutBuilderBlockCatalogAction::run($languages, $extraBlocks);
    }

    public function breadcrumbBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->systemBlockType();

        $block = $this->blockModel::query()->firstOrNew([
            'key' => 'breadcrumbs',
        ]);

        $block->forceFill([
            'name' => __('capell-admin::generic.breadcrumbs'),
            'blueprint_id' => $type->id,
            'component' => BlockComponentEnum::PageBreadcrumbs->value,
            'is_livewire' => false,
            'meta' => [
                'component' => BlockComponentEnum::PageBreadcrumbs->value,
            ],
        ])->save();

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function childrenBlock(?Blueprint $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsBlockType();

        $block = $this->blockModel::query()->firstOrCreate([
            'key' => 'children',
        ], [
            'name' => __('capell-admin::generic.page_children'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::PageChildren,
                'content_divider' => true,
                'with_children_count' => true,
                'with_summary' => true,
                'with_image' => true,
                'heading_style' => 'secondary',
                'margin' => ['b-lg'],
            ],
            'admin' => [
                'icon' => 'heroicon-c-users',
            ],
        ]);

        $block->forceFill([
            'meta' => [
                ...(is_array($block->meta) ? $block->meta : []),
                'component' => BlockComponentEnum::PageChildren->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($block): void {
            $block->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.page_children'),
            ]);
        });

        return $block;
    }

    public function assetsBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsBlockType();

        return $this->blockModel::query()->firstOrCreate([
            'key' => 'assets',
        ], [
            'name' => __('capell-layout-builder::generic.assets'),
            'blueprint_id' => $type->id,
            'meta' => [
                'limit' => 6,
                'pagination' => false,
                'with_summary' => true,
                'with_link_text' => true,
                'with_image' => true,
                'columns' => 1,
            ],
            'admin' => [
                'icon' => 'heroicon-o-rectangle-stack',
            ],
        ]);
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function galleryBlock(?Blueprint $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->mediaBlockType();

        $block = $this->blockModel::query()->firstOrCreate([
            'key' => 'gallery',
        ], [
            'name' => __('capell-admin::generic.gallery'),
            'blueprint_id' => $type->id,
            'meta' => [
                'block_theme' => 'masonry',
                'spacing' => 'md',
                'margin' => ['lg'],
                'container' => ContainerWidthEnum::Full,
            ],
        ]);

        $languages->each(function (Language $language) use ($block): void {
            $block->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.gallery'),
            ]);
        });

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function latestPagesBlock(?Blueprint $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsBlockType();

        $block = $this->blockModel::query()->firstOrCreate([
            'key' => 'latest-pages',
        ], [
            'name' => __('capell-admin::generic.latest_pages'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::PageLatest,
                'content_divider' => true,
                'limit' => 6,
                'pagination' => false,
                'with_summary' => true,
                'with_link_text' => true,
                'with_image' => true,
                'columns' => 1,
            ],
            'admin' => [
                'icon' => 'heroicon-o-rectangle-stack',
            ],
        ]);

        $block->forceFill([
            'meta' => [
                ...(is_array($block->meta) ? $block->meta : []),
                'component' => BlockComponentEnum::PageLatest->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($block): void {
            $block->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-admin::heading.latest_pages'),
                'content' => '<p>' . __('capell-layout-builder::generic.latest_pages_description') . '</p>',
            ]);
        });

        return $block;
    }

    public function mediaCarouselBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->mediaBlockType();

        return $this->blockModel::query()->firstOrCreate([
            'key' => 'media-carousel',
        ], [
            'name' => __('capell-admin::generic.media_carousel'),
            'blueprint_id' => $type->id,
            'meta' => [
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
                'margin' => ['none'],
                'padding' => ['md'],
            ],
            'admin' => [
                'configurator' => 'Carousel',
            ],
        ]);
    }

    public function pageContentBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->pageContentBlockType();

        $block = $this->blockModel::query()->firstOrNew([
            'key' => 'page-content',
        ]);

        $block->forceFill([
            'name' => __('capell-admin::generic.page_content'),
            'blueprint_id' => $type->id,
            'component' => BlockComponentEnum::PageContent->value,
            'is_livewire' => false,
            'meta' => [
                'component' => BlockComponentEnum::PageContent->value,
                'margin' => ['t-lg', 'b-xl'],
                'page_content' => ['title', 'content'],
            ],
        ])->save();

        return $block;
    }

    public function pagesCardBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->pagesBlockType();

        return $this->blockModel::query()->firstOrCreate([
            'key' => 'pages-card',
        ], [
            'name' => __('capell-admin::generic.pages_card'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::Pages,
                'limit' => 10,
                'with_image' => true,
                'with_summary' => true,
                'with_link_text' => true,
                'spacing' => 'lg',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function pageSlotBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->systemBlockType();

        $block = $this->blockModel::query()->firstOrNew([
            'key' => 'page-slot',
        ]);

        $block->forceFill([
            'name' => __('capell-admin::generic.page_slot'),
            'blueprint_id' => $type->id,
            'component' => BlockComponentEnum::PageSlot->value,
            'is_livewire' => false,
            'meta' => [
                'component' => BlockComponentEnum::PageSlot->value,
                'type' => 'slot',
            ],
        ])->save();

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function siblingsBlock(?Blueprint $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsBlockType();

        $block = $this->blockModel::query()->firstOrCreate([
            'key' => 'siblings',
        ], [
            'name' => __('capell-admin::generic.page_siblings'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::PageSiblings,
                'content_divider' => true,
                'with_children_count' => true,
                'with_summary' => true,
                'heading_style' => 'secondary',
                'margin' => ['b-lg'],
            ],
            'admin' => [
                'icon' => 'heroicon-c-user-group',
            ],
        ]);

        $block->forceFill([
            'meta' => [
                ...(is_array($block->meta) ? $block->meta : []),
                'component' => BlockComponentEnum::PageSiblings->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($block): void {
            $block->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.page_siblings'),
            ]);
        });

        return $block;
    }

    public function defaultBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'default'], [
            'name' => 'Default Widget',
            'blueprint_id' => $type->id,
        ]);
    }

    public function accordionBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'assets-accordion'], [
            'key' => 'assets-accordion',
            'name' => __('capell-layout-builder::generic.accordion'),
            'blueprint_id' => $type->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => BlockComponentEnum::AssetAccordion,
                'margin' => ['lg'],
                'align' => 'center',
            ],
            'admin' => [
                'asset_types' => [
                    'section',
                ],
            ],
        ]);
    }

    public function bannerBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'assets-banner'], [
            'name' => 'Banner Showcase',
            'blueprint_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'background_overlay' => true,
                'component' => BlockComponentEnum::AssetBanner,
            ],
        ]);
    }

    public function blockBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->assetsBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'assets-block'], [
            'name' => 'Blocks',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::AssetBlock,
                'component_item' => FrontendComponentKeyEnum::SectionBlock->value,
                'spacing' => 'none',
                'columns' => 0,
                'margin' => 'none',
                'with_summary' => true,
                'container' => ContainerWidthEnum::Small->value,
            ],
            'admin' => [
                'icon' => 'heroicon-o-chart-bar',
            ],
        ]);
    }

    public function featuresBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'asset-features'], [
            'name' => 'Features',
            'blueprint_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'component' => BlockComponentEnum::AssetFeatures,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function testimonialsBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'asset-testimonials'], [
            'name' => 'Testimonials',
            'blueprint_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'spacing' => 'none',
                'background_overlay' => true,
                'background_color' => DefaultColorEnum::Gray->value,
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
            'admin' => [
                'configurator' => 'Carousel',
            ],
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $blockMeta
     * @param  array<array-key, mixed>  $navigationItems
     */
    public function navigationBlock(
        ?Blueprint $type = null,
        ?Site $site = null,
        string $widgetKey = 'block-navigation',
        array $blockMeta = [],
        string $navigationKey = 'navigation',
        string $navigationName = 'Navigation',
        array $navigationItems = [],
    ): Widget {
        $type ??= resolve(TypeCreator::class)->navigationBlockType();
        $typeModel = Blueprint::class;
        $navigationModel = Navigation::class;

        $navigationType = $typeModel::query()->navigationType()->default()->first();
        if ($navigationType === null) {
            $navigationType = resolve(BlueprintCreator::class)->createNavigationType();
        }

        $navigation = CapellCore::isPackageInstalled(self::NavigationPackage) && class_exists($navigationModel)
            ? $navigationModel::query()->firstOrCreate([
                'key' => $navigationKey,
                'blueprint_id' => $navigationType->id,
                'site_id' => $site?->id,
            ], [
                'name' => $navigationName,
                'items' => $navigationItems,
            ])
            : null;

        if ($navigation instanceof Model && $navigationItems !== [] && $navigation->getAttribute('items') !== $navigationItems) {
            $navigation->forceFill(['items' => $navigationItems])->save();
        }

        return $this->blockModel::query()->firstOrCreate(['key' => $widgetKey], [
            'name' => __('Navigation'),
            'blueprint_id' => $type->id,
            'meta' => [
                'navigation' => $navigation instanceof Model ? (string) $navigation->getAttribute('key') : $navigationKey,
                'margin' => ['lg'],
                ...$blockMeta,
            ],
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $blockMeta
     * @param  array<array-key, mixed>  $navigationItems
     */
    public function navigationTabsBlock(
        ?Blueprint $type = null,
        ?Site $site = null,
        string $widgetKey = 'block-navigation-tabs',
        array $blockMeta = [
            'component' => BlockComponentEnum::NavigationTabs,
        ],
        string $navigationKey = 'navigation-tabs',
        string $navigationName = 'Tabs',
        array $navigationItems = [],
    ): Widget {
        $block = $this->navigationBlock(
            type: $type,
            site: $site,
            widgetKey: $widgetKey,
            blockMeta: $blockMeta,
            navigationKey: $navigationKey,
            navigationName: $navigationName,
            navigationItems: $navigationItems,
        );

        if (($block->view_file ?? null) !== ($blockMeta['view_file'] ?? null)) {
            $block->forceFill([
                'meta' => [
                    ...($block->meta ?? []),
                    ...$blockMeta,
                ],
            ])->save();
        }

        return $block;
    }

    public function bannerImageBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'banner-image'], [
            'name' => 'Banner Image',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::BannerImage,
                'margin' => ['none'],
                'padding' => ['xl'],
            ],
        ]);
    }

    public function apHeroBannerBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'ap-hero-banner'], [
            'name' => 'AP Hero Banner',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::ApHeroBanner,
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '#',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apCardGridBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'ap-card-grid'], [
            'name' => 'AP Card Grid',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::ApCardGrid,
                'columns' => 3,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apFeatureListBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'ap-feature-list'], [
            'name' => 'AP Feature List',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::ApFeatureList,
                'layout' => 'grid',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apCtaSectionBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'ap-cta-section'], [
            'name' => 'AP CTA Section',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::ApCTASection,
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '#',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apImageGalleryBlock(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultBlockType();

        return $this->blockModel::query()->firstOrCreate(['key' => 'ap-image-gallery'], [
            'name' => 'AP Image Gallery',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => BlockComponentEnum::ApImageGallery,
                'columns' => 3,
                'layout' => 'grid',
                'lightbox' => true,
                'margin' => ['lg'],
            ],
        ]);
    }
}
