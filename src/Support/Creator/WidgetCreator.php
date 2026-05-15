<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use BadMethodCallException;
use Capell\Core\Models\Widget;
use Illuminate\Support\Collection;

/**
 * @deprecated Use ElementCreator. Kept as a compatibility bridge during the
 * widget-to-element migration.
 */
class WidgetCreator
{
    public function __construct(
        private readonly ElementCreator $elementCreator,
    ) {}

    public function __call(string $method, array $arguments): Widget
    {
        $element = match ($method) {
            'breadcrumbElement', 'breadcrumbWidget' => $this->elementCreator->breadcrumbElement(...$arguments),
            'childrenElement', 'childrenWidget' => $this->elementCreator->childrenElement(...$arguments),
            'assetsElement', 'assetsWidget' => $this->elementCreator->assetsElement(...$arguments),
            'galleryElement', 'galleryWidget' => $this->elementCreator->galleryElement(...$arguments),
            'latestPagesElement', 'latestPagesWidget' => $this->elementCreator->latestPagesElement(...$arguments),
            'mediaCarouselElement', 'mediaCarouselWidget' => $this->elementCreator->mediaCarouselElement(...$arguments),
            'pageContentElement', 'pageContentWidget' => $this->elementCreator->pageContentElement(...$arguments),
            'pagesCardElement', 'pagesCardWidget' => $this->elementCreator->pagesCardElement(...$arguments),
            'pageSlotElement', 'pageSlotWidget' => $this->elementCreator->pageSlotElement(...$arguments),
            'siblingsElement', 'siblingsWidget' => $this->elementCreator->siblingsElement(...$arguments),
            'defaultElement', 'defaultWidget' => $this->elementCreator->defaultElement(...$arguments),
            'accordionElement', 'accordionWidget' => $this->elementCreator->accordionElement(...$arguments),
            'bannerElement', 'bannerWidget' => $this->elementCreator->bannerElement(...$arguments),
            'blockElement', 'blockWidget' => $this->elementCreator->blockElement(...$arguments),
            'featuresElement', 'featuresWidget' => $this->elementCreator->featuresElement(...$arguments),
            'testimonialsElement', 'testimonialsWidget' => $this->elementCreator->testimonialsElement(...$arguments),
            'navigationElement', 'navigationWidget' => $this->elementCreator->navigationElement(...$arguments),
            'navigationTabsElement', 'navigationTabsWidget' => $this->elementCreator->navigationTabsElement(...$arguments),
            'bannerImageElement', 'bannerImageWidget' => $this->elementCreator->bannerImageElement(...$arguments),
            'apHeroBannerElement', 'apHeroBannerWidget' => $this->elementCreator->apHeroBannerElement(...$arguments),
            'apCardGridElement', 'apCardGridWidget' => $this->elementCreator->apCardGridElement(...$arguments),
            'apFeatureListElement', 'apFeatureListWidget' => $this->elementCreator->apFeatureListElement(...$arguments),
            'apCtaSectionElement', 'apCtaSectionWidget' => $this->elementCreator->apCtaSectionElement(...$arguments),
            'apImageGalleryElement', 'apImageGalleryWidget' => $this->elementCreator->apImageGalleryElement(...$arguments),
            default => throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', self::class, $method)),
        };

        return Widget::query()->findOrFail($element->getKey());
    }

    public function createWidgets(Collection $languages, bool $extraWidgets = false): void
    {
        $this->elementCreator->createElements($languages, $extraWidgets);
    }
}
