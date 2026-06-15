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
use Capell\LayoutBuilder\Actions\InstallLayoutBuilderWidgetCatalogAction;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WidgetCreator
{
    private const string NavigationPackage = 'capell-app/navigation';

    /**
     * @var class-string<Widget>
     */
    private readonly string $widgetModel;

    public function __construct()
    {
        $this->widgetModel = Widget::class;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createWidgets(Collection $languages, bool $extraWidgets = false): void
    {
        InstallLayoutBuilderWidgetCatalogAction::run($languages, $extraWidgets);
    }

    public function breadcrumbWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->systemWidgetType();

        $widget = $this->widgetModel::query()->firstOrNew([
            'key' => 'breadcrumbs',
        ]);

        $widget->forceFill([
            'name' => __('capell-admin::generic.breadcrumbs'),
            'blueprint_id' => $type->id,
            'component' => WidgetComponentEnum::PageBreadcrumbs->value,
            'is_livewire' => false,
            'meta' => [
                'component' => WidgetComponentEnum::PageBreadcrumbs->value,
                'minimum_items' => 1,
                'show_current_page' => true,
                'show_home' => true,
                'show_parent' => true,
            ],
        ])->save();

        return $widget;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function childrenWidget(?Blueprint $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsWidgetType();

        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'children',
        ], [
            'name' => __('capell-admin::generic.page_children'),
            'blueprint_id' => $type->id,
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
                ...(is_array($widget->meta) ? $widget->meta : []),
                'component' => WidgetComponentEnum::PageChildren->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.page_children'),
            ]);
        });

        return $widget;
    }

    public function assetsWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
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
    public function galleryWidget(?Blueprint $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->mediaWidgetType();

        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'gallery',
        ], [
            'name' => __('capell-admin::generic.gallery'),
            'blueprint_id' => $type->id,
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
                'title' => __('capell-layout-builder::heading.gallery'),
            ]);
        });

        return $widget;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function latestPagesWidget(?Blueprint $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsWidgetType();

        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'latest-pages',
        ], [
            'name' => __('capell-admin::generic.latest_pages'),
            'blueprint_id' => $type->id,
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
                ...(is_array($widget->meta) ? $widget->meta : []),
                'component' => WidgetComponentEnum::PageLatest->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-admin::heading.latest_pages'),
                'content' => '<p>' . __('capell-layout-builder::generic.latest_pages_description') . '</p>',
            ]);
        });

        return $widget;
    }

    public function mediaCarouselWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->mediaWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
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
                'component' => WidgetComponentEnum::AssetCarousel,
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

    public function pageContentWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->pageContentWidgetType();

        $widget = $this->widgetModel::query()->firstOrNew([
            'key' => 'page-content',
        ]);

        $widget->forceFill([
            'name' => __('capell-admin::generic.page_content'),
            'blueprint_id' => $type->id,
            'component' => WidgetComponentEnum::PageContent->value,
            'is_livewire' => false,
            'meta' => [
                'component' => WidgetComponentEnum::PageContent->value,
                'margin' => ['t-lg', 'b-xl'],
                'page_content' => ['title', 'content'],
            ],
        ])->save();

        return $widget;
    }

    public function pagesCardWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->pagesWidgetType();

        return $this->widgetModel::query()->firstOrCreate([
            'key' => 'pages-card',
        ], [
            'name' => __('capell-admin::generic.pages_card'),
            'blueprint_id' => $type->id,
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

    public function pageSlotWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->systemWidgetType();

        $widget = $this->widgetModel::query()->firstOrNew([
            'key' => 'page-slot',
        ]);

        $widget->forceFill([
            'name' => __('capell-admin::generic.page_slot'),
            'blueprint_id' => $type->id,
            'component' => WidgetComponentEnum::PageSlot->value,
            'is_livewire' => false,
            'meta' => [
                'component' => WidgetComponentEnum::PageSlot->value,
                'type' => 'slot',
            ],
        ])->save();

        return $widget;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function siblingsWidget(?Blueprint $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsWidgetType();

        $widget = $this->widgetModel::query()->firstOrCreate([
            'key' => 'siblings',
        ], [
            'name' => __('capell-admin::generic.page_siblings'),
            'blueprint_id' => $type->id,
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
                ...(is_array($widget->meta) ? $widget->meta : []),
                'component' => WidgetComponentEnum::PageSiblings->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.page_siblings'),
            ]);
        });

        return $widget;
    }

    public function defaultWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'default'], [
            'name' => 'Default Widget',
            'blueprint_id' => $type->id,
        ]);
    }

    public function accordionWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'assets-accordion'], [
            'key' => 'assets-accordion',
            'name' => __('capell-layout-builder::generic.accordion'),
            'blueprint_id' => $type->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => WidgetComponentEnum::AssetAccordion,
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

    public function bannerWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'assets-banner'], [
            'name' => 'Banner Showcase',
            'blueprint_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'background_overlay' => true,
                'component' => WidgetComponentEnum::AssetBanner,
            ],
        ]);
    }

    public function widgetWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->assetsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'assets-widget'], [
            'name' => 'Widgets',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::AssetWidget,
                'component_item' => FrontendComponentKeyEnum::SectionWidget->value,
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

    public function featuresWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'asset-features'], [
            'name' => 'Features',
            'blueprint_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'component' => WidgetComponentEnum::AssetFeatures,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function testimonialsWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->contentsWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'asset-testimonials'], [
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
                'component' => WidgetComponentEnum::AssetTestimonials,
            ],
            'admin' => [
                'configurator' => 'Carousel',
            ],
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $widgetMeta
     * @param  array<array-key, mixed>  $navigationItems
     */
    public function navigationWidget(
        ?Blueprint $type = null,
        ?Site $site = null,
        string $widgetKey = 'widget-navigation',
        array $widgetMeta = [],
        string $navigationKey = 'navigation',
        string $navigationName = 'Navigation',
        array $navigationItems = [],
    ): Widget {
        $type ??= resolve(TypeCreator::class)->navigationWidgetType();
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

        return $this->widgetModel::query()->firstOrCreate(['key' => $widgetKey], [
            'name' => __('Navigation'),
            'blueprint_id' => $type->id,
            'meta' => [
                'navigation' => $navigation instanceof Model ? (string) $navigation->getAttribute('key') : $navigationKey,
                'margin' => ['lg'],
                ...$widgetMeta,
            ],
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $widgetMeta
     * @param  array<array-key, mixed>  $navigationItems
     */
    public function navigationTabsWidget(
        ?Blueprint $type = null,
        ?Site $site = null,
        string $widgetKey = 'widget-navigation-tabs',
        array $widgetMeta = [
            'component' => WidgetComponentEnum::NavigationTabs,
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

        if (($widget->view_file ?? null) !== ($widgetMeta['view_file'] ?? null)) {
            $widget->forceFill([
                'meta' => [
                    ...($widget->meta ?? []),
                    ...$widgetMeta,
                ],
            ])->save();
        }

        return $widget;
    }

    public function bannerImageWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'banner-image'], [
            'name' => 'Banner Image',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::BannerImage,
                'margin' => ['none'],
                'padding' => ['xl'],
            ],
        ]);
    }

    public function apHeroBannerWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-hero-banner'], [
            'name' => 'AP Hero Banner',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApHeroBanner,
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '#',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apCardGridWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-card-grid'], [
            'name' => 'AP Card Grid',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApCardGrid,
                'columns' => 3,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apFeatureListWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-feature-list'], [
            'name' => 'AP Feature List',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApFeatureList,
                'layout' => 'grid',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apCtaSectionWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-cta-section'], [
            'name' => 'AP CTA Section',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::ApCTASection,
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '#',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apImageGalleryWidget(?Blueprint $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'ap-image-gallery'], [
            'name' => 'AP Image Gallery',
            'blueprint_id' => $type->id,
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
