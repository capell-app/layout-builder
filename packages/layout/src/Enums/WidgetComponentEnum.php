<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum WidgetComponentEnum: string
{
    case Default = 'capell-layout::widget.default';

    case LivewirePages = 'capell-layout::livewire.widget.pages';

    case Navigation = 'capell-layout::widget.navigation';

    case Breadcrumbs = 'capell-layout::widget.breadcrumbs';
    case PageChildren = 'capell-layout::widget.pages.children';
    case PageContent = 'capell-layout::widget.page.content';
    case PageLatest = 'capell-layout::widget.pages.latest';
    case PageRelated = 'capell-layout::widget.pages.related';
    case PageSiblings = 'capell-layout::widget.pages.siblings';
    case PageSitemap = 'capell-layout::widget.page.sitemap';

    case Assets = 'capell-layout::widget.assets';
    case AssetAccordion = 'capell-layout::widget.assets.accordion';
    case AssetBanner = 'capell-layout::widget.assets.banners';
    case AssetBlock = 'capell-layout::widget.assets.blocks';
    case AssetCarousel = 'capell-layout::widget.assets.carousel';
    case AssetFeatures = 'capell-layout::widget.assets.features';
    case AssetMedia = 'capell-layout::widget.assets.media';
    case AssetTestimonials = 'capell-layout::widget.assets.testimonials';

    case Tags = 'capell-layout::widget.tag.tags';
}
