<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

enum WidgetComponentEnum: string
{
    case AssetAccordion = 'capell-mosaic::widget.asset.accordion';
    case AssetBanner = 'capell-mosaic::widget.asset.banners';
    case AssetBlock = 'capell-mosaic::widget.asset.blocks';
    case AssetCarousel = 'capell-mosaic::widget.asset.carousel';
    case AssetFeatures = 'capell-mosaic::widget.asset.features';
    case AssetMedia = 'capell-mosaic::widget.asset.media';
    case AssetTestimonials = 'capell-mosaic::widget.asset.testimonials';
    case Assets = 'capell-mosaic::widget.asset';
    case BannerImage = 'capell-mosaic::widget.banner-image';
    case Default = 'capell-mosaic::widget.default';
    case Hero = 'capell-mosaic::widget.hero';
    case Navigation = 'capell-mosaic::widget.navigation';
    case NavigationTabs = 'capell-mosaic::widget.navigation.tabs';
    case PageBreadcrumbs = 'capell-mosaic::widget.page.breadcrumbs';
    case PageChildren = 'capell-mosaic::widget.page.children';
    case PageContent = 'capell-mosaic::widget.page.content';
    case PageLatest = 'capell-mosaic::widget.page.latest';
    case PageSiblings = 'capell-mosaic::widget.page.siblings';
    case PageSlot = 'capell-mosaic::widget.slot';
    case Pages = 'capell-mosaic::widget.asset.pages';

    case ApHeroBanner = 'capell-mosaic::modern.hero-banner';
    case ApCardGrid = 'capell-mosaic::modern.card-grid';
    case ApFeatureList = 'capell-mosaic::modern.feature-list';
    case ApCTASection = 'capell-mosaic::modern.cta-section';
    case ApImageGallery = 'capell-mosaic::modern.image-gallery';
}
