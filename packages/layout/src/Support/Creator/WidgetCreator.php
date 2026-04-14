<?php

declare(strict_types=1);

namespace Capell\Layout\Support\Creator;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Navigation;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\CarouselWidgetSchema;
use Capell\Layout\Models\Widget;
use Illuminate\Support\Collection;

class WidgetCreator
{
    /**
     * @var class-string<Widget>
     */
    private readonly string $widgetModel;

    public function __construct()
    {
        $this->widgetModel = CapellCore::getModel(ModelEnum::Widget->name);
    }

    public function createWidgets(Collection $languages, bool $extraWidgets = false): void
    {
        $typeCreator = resolve(TypeCreator::class);

        $assetsWidgetType = $typeCreator->assetsWidgetType();
        $contentsWidgetType = $typeCreator->contentsWidgetType();
        $defaultWidgetType = $typeCreator->defaultWidgetType();
        $mediaWidgetType = $typeCreator->mediaWidgetType();
        $navigationWidgetType = $typeCreator->navigationWidgetType();
        $pageContentWidgetType = $typeCreator->pageContentWidgetType();
        $resultsWidgetType = $typeCreator->resultsWidgetType();
        $pagesWidgetType = $typeCreator->pagesWidgetType();
        $systemWidgetType = $typeCreator->systemWidgetType();

        $this->breadcrumbWidget($systemWidgetType);
        $this->childrenWidget($resultsWidgetType, $languages);
        $this->assetsWidget($contentsWidgetType);
        $this->galleryWidget($mediaWidgetType, $languages);
        $this->latestPagesWidget($resultsWidgetType, $languages);
        $this->mediaCarouselWidget($mediaWidgetType);
        $this->pageContentWidget($pageContentWidgetType);
        $this->pageSlotWidget($systemWidgetType);
        $this->pagesCardWidget($pagesWidgetType);
        $this->siblingsWidget($resultsWidgetType, $languages);

        if ($extraWidgets) {
            $this->defaultWidget($defaultWidgetType);
            $this->accordionWidget($contentsWidgetType);
            $this->bannerWidget($contentsWidgetType);
            $this->blockWidget($assetsWidgetType);
            $this->featuresWidget($contentsWidgetType);
            $this->testimonialsWidget($contentsWidgetType);
            $this->navigationWidget($navigationWidgetType);
            $this->navigationTabsWidget($navigationWidgetType);
            $this->bannerImageWidget();
        }
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
        $model = CapellCore::getModel(CoreModelEnum::Language);

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

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout::heading.page_children'),
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
            'name' => __('capell-layout::generic.assets'),
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
        $model = CapellCore::getModel(CoreModelEnum::Language);

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
                'title' => __('capell-layout::heading.gallery'),
            ]);
        });

        return $widget;
    }

    public function latestPagesWidget(?Type $type = null, ?Collection $languages = null): Widget
    {
        /** @var class-string<Language> $model */
        $model = CapellCore::getModel(CoreModelEnum::Language);

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
                'with_summary' => false,
                'with_link_text' => true,
                'with_image' => true,
                'columns' => 1,
            ],
            'admin' => [
                'icon' => 'heroicon-o-rectangle-stack',
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-admin::heading.latest_pages'),
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
                'schema' => CarouselWidgetSchema::getKey(),
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
        $model = CapellCore::getModel(CoreModelEnum::Language);

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

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout::heading.page_siblings'),
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
            'name' => __('capell-layout::generic.accordion'),
            'type_id' => $type->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => WidgetComponentEnum::AssetAccordion,
                'margin' => ['lg'],
                'align' => 'center',
            ],
            'admin' => [
                'asset_types' => [
                    AssetEnum::Content->value,
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
                'view_file' => 'capell-layout::components.widget.asset.banners',
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
                'component_item' => 'capell-layout::content.block',
                'view_file' => 'capell-layout::components.widget.asset.blocks',
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
                'margin' => ['lg'],
                'view_file' => 'capell-layout::components.widget.asset.features',
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
                'view_file' => 'capell-layout::components.widget.asset.testimonials',
            ],
            'admin' => [
                'schema' => CarouselWidgetSchema::getKey(),
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
        $typeModel = CapellCore::getModel(CoreModelEnum::Type);
        $navigationModel = CapellCore::getModel(CoreModelEnum::Navigation);

        $navigationType = $typeModel::navigationType()->default()->first();
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
            'view_file' => 'capell-layout::components.widget.navigation.tabs',
        ],
        string $navigationKey = 'navigation-tabs',
        string $navigationName = 'Tabs',
        array $navigationItems = [],
    ): Widget {
        return $this->navigationWidget(
            type: $type,
            site: $site,
            widgetKey: $widgetKey,
            widgetMeta: $widgetMeta,
            navigationKey: $navigationKey,
            navigationName: $navigationName,
            navigationItems: $navigationItems,
        );
    }

    public function bannerImageWidget(?Type $type = null): Widget
    {
        $type ??= resolve(TypeCreator::class)->defaultWidgetType();

        return $this->widgetModel::query()->firstOrCreate(['key' => 'banner-image'], [
            'name' => 'Banner Image',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::BannerImage,
                'margin' => ['lg'],
            ],
        ]);
    }
}
