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
use Capell\LayoutBuilder\Actions\InstallLayoutBuilderElementCatalogAction;
use Capell\LayoutBuilder\Enums\ElementComponentEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Models\Element;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ElementCreator
{
    private const NavigationPackage = 'capell-app/navigation';

    /**
     * @var class-string<Element>
     */
    private readonly string $elementModel;

    public function __construct()
    {
        $this->elementModel = Element::class;
    }

    public function createElements(Collection $languages, bool $extraElements = false): void
    {
        InstallLayoutBuilderElementCatalogAction::run($languages, $extraElements);
    }

    public function breadcrumbElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->systemElementType();

        return $this->elementModel::query()->firstOrCreate([
            'key' => 'breadcrumbs',
        ], [
            'name' => __('capell-admin::generic.breadcrumbs'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::PageBreadcrumbs,
            ],
        ]);
    }

    public function childrenElement(?Blueprint $type = null, ?Collection $languages = null): Element
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsElementType();

        $element = $this->elementModel::query()->firstOrCreate([
            'key' => 'children',
        ], [
            'name' => __('capell-admin::generic.page_children'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::PageChildren,
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

        $element->forceFill([
            'meta' => [
                ...$element->meta,
                'component' => ElementComponentEnum::PageChildren->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($element): void {
            $element->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.page_children'),
            ]);
        });

        return $element;
    }

    public function assetsElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->contentsElementType();

        return $this->elementModel::query()->firstOrCreate([
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

    public function galleryElement(?Blueprint $type = null, ?Collection $languages = null): Element
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->mediaElementType();

        $element = $this->elementModel::query()->firstOrCreate([
            'key' => 'gallery',
        ], [
            'name' => __('capell-admin::generic.gallery'),
            'blueprint_id' => $type->id,
            'meta' => [
                'element_theme' => 'masonry',
                'spacing' => 'md',
                'margin' => ['lg'],
                'container' => ContainerWidthEnum::Full,
            ],
        ]);

        $languages->each(function (Language $language) use ($element): void {
            $element->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.gallery'),
            ]);
        });

        return $element;
    }

    public function latestPagesElement(?Blueprint $type = null, ?Collection $languages = null): Element
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsElementType();

        $element = $this->elementModel::query()->firstOrCreate([
            'key' => 'latest-pages',
        ], [
            'name' => __('capell-admin::generic.latest_pages'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::PageLatest,
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

        $element->forceFill([
            'meta' => [
                ...$element->meta,
                'component' => ElementComponentEnum::PageLatest->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($element): void {
            $element->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-admin::heading.latest_pages'),
                'content' => '<p>' . __('capell-layout-builder::generic.latest_pages_description') . '</p>',
            ]);
        });

        return $element;
    }

    public function mediaCarouselElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->mediaElementType();

        return $this->elementModel::query()->firstOrCreate([
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
                'component' => ElementComponentEnum::AssetCarousel,
                'limit' => 20,
                'container' => ContainerWidthEnum::Full,
                'background_color' => 'light-gray',
                'spacing' => 'md',
                'margin' => 0,
                'padding' => ['md'],
            ],
            'admin' => [
                'configurator' => 'Carousel',
            ],
        ]);
    }

    public function pageContentElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->pageContentElementType();

        return $this->elementModel::query()->firstOrCreate([
            'key' => 'page-content',
        ], [
            'name' => __('capell-admin::generic.page_content'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::PageContent,
                'margin' => ['t-lg', 'b-xl'],
                'page_content' => ['title', 'content'],
            ],
        ]);
    }

    public function pagesCardElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->pagesElementType();

        return $this->elementModel::query()->firstOrCreate([
            'key' => 'pages-card',
        ], [
            'name' => __('capell-admin::generic.pages_card'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::Pages,
                'limit' => 10,
                'with_image' => true,
                'with_summary' => true,
                'with_link_text' => true,
                'spacing' => 'lg',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function pageSlotElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->systemElementType();

        return $this->elementModel::query()->firstOrCreate([
            'key' => 'page-slot',
        ], [
            'name' => __('capell-admin::generic.page_slot'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::PageSlot,
                'type' => 'slot',
            ],
        ]);
    }

    public function siblingsElement(?Blueprint $type = null, ?Collection $languages = null): Element
    {
        /** @var class-string<Language> $model */
        $model = Language::class;

        $languages ??= $model::query()->get();
        $type ??= resolve(TypeCreator::class)->resultsElementType();

        $element = $this->elementModel::query()->firstOrCreate([
            'key' => 'siblings',
        ], [
            'name' => __('capell-admin::generic.page_siblings'),
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::PageSiblings,
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

        $element->forceFill([
            'meta' => [
                ...$element->meta,
                'component' => ElementComponentEnum::PageSiblings->value,
            ],
        ])->save();

        $languages->each(function (Language $language) use ($element): void {
            $element->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => __('capell-layout-builder::heading.page_siblings'),
            ]);
        });

        return $element;
    }

    public function defaultElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->defaultElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'default'], [
            'name' => 'Default Element',
            'blueprint_id' => $type->id,
        ]);
    }

    public function accordionElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->contentsElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'assets-accordion'], [
            'key' => 'assets-accordion',
            'name' => __('capell-layout-builder::generic.accordion'),
            'blueprint_id' => $type->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => ElementComponentEnum::AssetAccordion,
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

    public function bannerElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->contentsElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'assets-banner'], [
            'name' => 'Banner Showcase',
            'blueprint_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'background_overlay' => true,
                'component' => ElementComponentEnum::AssetBanner,
            ],
        ]);
    }

    public function blockElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->assetsElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'assets-block'], [
            'name' => 'Blocks',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::AssetBlock,
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

    public function featuresElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->contentsElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'asset-features'], [
            'name' => 'Features',
            'blueprint_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'component' => ElementComponentEnum::AssetFeatures,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function testimonialsElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->contentsElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'asset-testimonials'], [
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
                'component' => ElementComponentEnum::AssetTestimonials,
            ],
            'admin' => [
                'configurator' => 'Carousel',
            ],
        ]);
    }

    public function navigationElement(
        ?Blueprint $type = null,
        ?Site $site = null,
        string $elementKey = 'element-navigation',
        array $elementMeta = [],
        string $navigationKey = 'navigation',
        string $navigationName = 'Navigation',
        array $navigationItems = [],
    ): Element {
        $type ??= resolve(TypeCreator::class)->navigationElementType();
        $typeModel = Blueprint::class;
        $navigationModel = Navigation::class;

        $navigationType = $typeModel::query()->navigationType()->default()->first();
        if ($navigationType === null) {
            $navigationType = resolve(BlueprintCreator::class)->createNavigationType();
        }

        $navigation = CapellCore::isPackageInstalled(self::NavigationPackage) && class_exists($navigationModel)
            /** @phpstan-ignore-next-line Navigation is an optional package model resolved by class string. */
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

        return $this->elementModel::query()->firstOrCreate(['key' => $elementKey], [
            'name' => __('Navigation'),
            'blueprint_id' => $type->id,
            'meta' => [
                'navigation' => $navigation instanceof Model ? (string) $navigation->getAttribute('key') : $navigationKey,
                'margin' => ['lg'],
                ...$elementMeta,
            ],
        ]);
    }

    public function navigationTabsElement(
        ?Blueprint $type = null,
        ?Site $site = null,
        string $elementKey = 'element-navigation-tabs',
        array $elementMeta = [
            'component' => ElementComponentEnum::NavigationTabs,
        ],
        string $navigationKey = 'navigation-tabs',
        string $navigationName = 'Tabs',
        array $navigationItems = [],
    ): Element {
        $element = $this->navigationElement(
            type: $type,
            site: $site,
            elementKey: $elementKey,
            elementMeta: $elementMeta,
            navigationKey: $navigationKey,
            navigationName: $navigationName,
            navigationItems: $navigationItems,
        );

        if (($element->view_file ?? null) !== ($elementMeta['view_file'] ?? null)) {
            $element->forceFill([
                'meta' => [
                    ...($element->meta ?? []),
                    ...$elementMeta,
                ],
            ])->save();
        }

        return $element;
    }

    public function bannerImageElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->defaultElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'banner-image'], [
            'name' => 'Banner Image',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::BannerImage,
                'margin' => ['none'],
                'padding' => ['xl'],
            ],
        ]);
    }

    public function apHeroBannerElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->defaultElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'ap-hero-banner'], [
            'name' => 'AP Hero Banner',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::ApHeroBanner,
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '#',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apCardGridElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->defaultElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'ap-card-grid'], [
            'name' => 'AP Card Grid',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::ApCardGrid,
                'columns' => 3,
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apFeatureListElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->defaultElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'ap-feature-list'], [
            'name' => 'AP Feature List',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::ApFeatureList,
                'layout' => 'grid',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apCtaSectionElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->defaultElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'ap-cta-section'], [
            'name' => 'AP CTA Section',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::ApCTASection,
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '#',
                'margin' => ['lg'],
            ],
        ]);
    }

    public function apImageGalleryElement(?Blueprint $type = null): Element
    {
        $type ??= resolve(TypeCreator::class)->defaultElementType();

        return $this->elementModel::query()->firstOrCreate(['key' => 'ap-image-gallery'], [
            'name' => 'AP Image Gallery',
            'blueprint_id' => $type->id,
            'meta' => [
                'component' => ElementComponentEnum::ApImageGallery,
                'columns' => 3,
                'layout' => 'grid',
                'lightbox' => true,
                'margin' => ['lg'],
            ],
        ]);
    }
}
