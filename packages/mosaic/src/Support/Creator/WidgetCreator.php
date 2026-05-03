<?php

declare(strict_types=1);

namespace Capell\Mosaic\Support\Creator;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Mosaic\Actions\InstallMosaicWidgetCatalogAction;
use Capell\Mosaic\Enums\AssetEnum;
use Capell\Mosaic\Enums\WidgetComponentEnum;
use Capell\Mosaic\Filament\Configurators\Widgets\CarouselWidgetConfigurator;
use Capell\Mosaic\Models\Widget;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Collection;

class WidgetCreator
{
    /**
     * @var class-string<Widget>
     */
    private readonly string $widgetModel;

    public function __construct()
    {
        $this->widgetModel = Widget::class;
    }

    public function createWidgets(Collection $languages, bool $extraWidgets = false): void
    {
        InstallMosaicWidgetCatalogAction::run($languages, $extraWidgets);
    }

    public function breadcrumbWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->systemWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
            'key' => 'breadcrumbs',
        ], [
            'name' => __('capell-admin::generic.breadcrumbs'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageBreadcrumbs,
            ],
        ]);
    }

    public function childrenWidget(?Type $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsWidgetType();

        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'children',
        ], [
            'name' => __('capell-admin::generic.page_children'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageChildren,
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

        $widget->forceFill([
            'meta' => [
                ...$widget->meta,
                'component' => WidgetComponentEnum::PageChildren->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-mosaic::heading.page_children'),
            ]);
        });

        return $widget;
    }

    public function assetsWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
            'key' => 'assets',
        ], [
            'name' => __('capell-mosaic::generic.assets'),
            'type_id' => $type->id,
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

    public function galleryWidget(?Type $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->mediaWidgetType();

        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'gallery',
        ], [
            'name' => __('capell-admin::generic.gallery'),
            'type_id' => $type->id,
            'meta' => [
                'widget_theme' => 'masonry',
                'spacing' => 'md',
                'margin' => ['lg'],
                'container' => ContainerWidthEnum::Full,
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-mosaic::heading.gallery'),
            ]);
        });

        return $widget;
    }

    public function latestPagesWidget(?Type $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsWidgetType();

        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'latest-pages',
        ], [
            'name' => __('capell-admin::generic.latest_pages'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageLatest,
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

        $widget->forceFill([
            'meta' => [
                ...$widget->meta,
                'component' => WidgetComponentEnum::PageLatest->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-admin::heading.latest_pages'),
                'content' => '<p>' . __('capell-mosaic::generic.latest_pages_description') . '</p>',
            ]);
        });

        return $widget;
    }

    public function mediaCarouselWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->mediaWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
            'key' => 'media-carousel',
        ], [
            'name' => __('capell-admin::generic.media_carousel'),
            'type_id' => $type->id,
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
                'component' => WidgetComponentEnum::AssetCarousel,
                'limit' => 20,
                'container' => ContainerWidthEnum::Full,
                'background_color' => 'light-gray',
                'spacing' => 'md',
                'margin' => 0,
                'padding' => ['md'],
            ],
            'admin' => [
                'configurator' => CarouselWidgetConfigurator::getKey(),
            ],
        ]);
    }

    public function pageContentWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->pageContentWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
            'key' => 'page-content',
        ], [
            'name' => __('capell-admin::generic.page_content'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageContent,
                'margin' => ['t-lg', 'b-xl'],
                'page_content' => ['title', 'content'],
            ],
        ]);
    }

    public function pagesCardWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->pagesWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
            'key' => 'pages-card',
        ], [
            'name' => __('capell-admin::generic.pages_card'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::Pages,
                'limit' => 10,
                'with_image' => true,
                'with_summary' => true,
                'with_link_text' => true,
                'spacing' => 'lg',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function pageSlotWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->systemWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
            'key' => 'page-slot',
        ], [
            'name' => __('capell-admin::generic.page_slot'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageSlot,
                'type' => 'slot',
            ],
        ]);
    }

    public function siblingsWidget(?Type $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsWidgetType();

        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'siblings',
        ], [
            'name' => __('capell-admin::generic.page_siblings'),
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::PageSiblings,
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

        $widget->forceFill([
            'meta' => [
                ...$widget->meta,
                'component' => WidgetComponentEnum::PageSiblings->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-mosaic::heading.page_siblings'),
            ]);
        });

        return $widget;
    }

    public function defaultWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'default'], [
            'name' => 'Default Widget',
            'type_id' => $type->id,
        ]);
    }

    public function accordionWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'assets-accordion'], [
            'key' => 'assets-accordion',
            'name' => __('capell-mosaic::generic.accordion'),
            'type_id' => $type->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => WidgetComponentEnum::AssetAccordion,
                'margin' => ['lg'],
                'align' => 'center',
            ],
            'admin' => [
                'asset_types' => [
                    AssetEnum::Section->value,
                ],
            ],
        ]);
    }

    public function bannerWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'assets-banner'], [
            'name' => 'Banner Showcase',
            'type_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'background_overlay' => true,
                'component' => WidgetComponentEnum::AssetBanner,
            ],
        ]);
    }

    public function blockWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->assetsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'assets-block'], [
            'name' => 'Blocks',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::AssetBlock,
                'component_item' => 'capell-mosaic::section.block',
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

    public function featuresWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'asset-features'], [
            'name' => 'Features',
            'type_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'component' => WidgetComponentEnum::AssetFeatures,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function testimonialsWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'asset-testimonials'], [
            'name' => 'Testimonials',
            'type_id' => $type->id,
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
                'component' => WidgetComponentEnum::AssetTestimonials,
            ],
            'admin' => [
                'configurator' => CarouselWidgetConfigurator::getKey(),
            ],
        ]);
    }

    public function navigationWidget(
        ?Type $type = null,
        ?Site $site = null,
        string $widgetKey = 'widget-navigation',
        array $widgetMeta = [],
        string $navigationKey = 'navigation',
        string $navigationName = 'Navigation',
        array $navigationItems = [],
    ): Widget {
        $type ??= resolve(TypeCreator::class)->navigationWidgetType();
        $typeModel = Type::class;
        $navigationModel = Navigation::class;

        $navigationType = $typeModel::query()->navigationType()->default()->first();
        if (! $navigationType) {
            $navigationType = resolve(\Capell\Core\Support\Creator\TypeCreator::class)->createNavigationType();
        }

        /** @var Navigation $navigation */
        $navigation = $navigationModel::query()->firstOrCreate([
            'key' => $navigationKey,
            'type_id' => $navigationType->id,
            'site_id' => $site?->id,
        ], [
            'name' => $navigationName,
            'items' => $navigationItems,
        ]);

        if ($navigationItems !== [] && $navigation->items !== $navigationItems) {
            $navigation->forceFill(['items' => $navigationItems])->save();
        }

        return $this->widgetModel::query()->firstOrCreate(['key' => $widgetKey], [
            'name' => __('Navigation'),
            'type_id' => $type->id,
            'meta' => [
                'navigation' => $navigation->key,
                'margin' => ['lg'],
                ...$widgetMeta,
            ],
        ]);
    }

    public function navigationTabsWidget(
        ?Type $type = null,
        ?Site $site = null,
        string $widgetKey = 'widget-navigation-tabs',
        array $widgetMeta = [
            'component' => 'capell-mosaic::widget.navigation.tabs',
            'view_file' => 'capell-mosaic::components.widget.navigation.tabs',
        ],
        string $navigationKey = 'navigation-tabs',
        string $navigationName = 'Tabs',
        array $navigationItems = [],
    ): Widget {
        $widget = $this->navigationWidget(
            type: $type,
            site: $site,
            widgetKey: $widgetKey,
            widgetMeta: $widgetMeta,
            navigationKey: $navigationKey,
            navigationName: $navigationName,
            navigationItems: $navigationItems,
        );

        if (($widget->meta['view_file'] ?? null) !== ($widgetMeta['view_file'] ?? null)) {
            $widget->forceFill([
                'meta' => [
                    ...$widget->meta,
                    ...$widgetMeta,
                ],
            ])->save();
        }

        return $widget;
    }

    public function bannerImageWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'banner-image'], [
            'name' => 'Banner Image',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::BannerImage,
                'margin' => ['none'],
                'padding' => ['xl'],
            ],
        ]);
    }

    public function apHeroBannerWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-hero-banner'], [
            'name' => 'AP Hero Banner',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApHeroBanner,
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '#',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apCardGridWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-card-grid'], [
            'name' => 'AP Card Grid',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApCardGrid,
                'columns' => 3,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apFeatureListWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-feature-list'], [
            'name' => 'AP Feature List',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApFeatureList,
                'layout' => 'grid',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apCtaSectionWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-cta-section'], [
            'name' => 'AP CTA Section',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApCTASection,
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '#',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apImageGalleryWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-image-gallery'], [
            'name' => 'AP Image Gallery',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApImageGallery,
                'columns' => 3,
                'layout' => 'grid',
                'lightbox' => true,
                'margin' => ['lg'],
            ],
        ]);
    }
}
