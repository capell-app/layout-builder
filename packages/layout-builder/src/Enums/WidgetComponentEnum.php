<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum WidgetComponentEnum: string
{
    case AssetAccordion = 'capell-layout-builder::widget.asset.accordion';
    case AssetBanner = 'capell-layout-builder::widget.asset.banners';
    case AssetBlock = 'capell-layout-builder::widget.asset.blocks';
    case AssetCarousel = 'capell-layout-builder::widget.asset.carousel';
    case AssetFeatures = 'capell-layout-builder::widget.asset.features';
    case AssetMedia = 'capell-layout-builder::widget.asset.media';
    case AssetTestimonials = 'capell-layout-builder::widget.asset.testimonials';
    case AnnouncementBar = 'capell-layout-builder::widget.announcement-bar';
    case Assets = 'capell-layout-builder::widget.asset';
    case BannerImage = 'capell-layout-builder::widget.banner-image';
    case Default = 'capell-layout-builder::widget.default';
    case Hero = 'capell-layout-builder::widget.hero';
    case Navigation = 'capell-layout-builder::widget.navigation';
    case NavigationTabs = 'capell-layout-builder::widget.navigation.tabs';
    case PageBreadcrumbs = 'capell-layout-builder::widget.page.breadcrumbs';
    case PageChildren = 'capell-layout-builder::widget.page.children';
    case PageContent = 'capell-layout-builder::widget.page.content';
    case PageLatest = 'capell-layout-builder::widget.page.latest';
    case PageSiblings = 'capell-layout-builder::widget.page.siblings';
    case PageSlot = 'capell-layout-builder::widget.slot';
    case Pages = 'capell-layout-builder::widget.asset.pages';
    case Snippet = 'capell-layout-builder::widget.snippet';

    case ApHeroBanner = 'capell-layout-builder::modern.hero-banner';
    case ApCardGrid = 'capell-layout-builder::modern.card-grid';
    case ApFeatureList = 'capell-layout-builder::modern.feature-list';
    case ApCTASection = 'capell-layout-builder::modern.cta-section';
    case ApImageGallery = 'capell-layout-builder::modern.image-gallery';
}
