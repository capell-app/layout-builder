<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum ElementComponentEnum: string
{
    case AssetAccordion = 'capell.element.asset.accordion';
    case AssetBanner = 'capell.element.asset.banners';
    case AssetBlock = 'capell.element.asset.blocks';
    case AssetCarousel = 'capell.element.asset.carousel';
    case AssetFeatures = 'capell.element.asset.features';
    case AssetMedia = 'capell.element.asset.media';
    case AssetTestimonials = 'capell.element.asset.testimonials';
    case AnnouncementBar = 'capell.element.announcement-bar';
    case Assets = 'capell.element.asset';
    case BannerImage = 'capell.element.banner-image';
    case Default = 'capell.element.default';
    case Hero = 'capell.element.hero';
    case Navigation = 'capell.element.navigation';
    case NavigationTabs = 'capell.element.navigation.tabs';
    case PageBreadcrumbs = 'capell.element.page.breadcrumbs';
    case PageChildren = 'capell.element.page.children';
    case PageContent = 'capell.element.page.content';
    case PageLatest = 'capell.element.page.latest';
    case PageSiblings = 'capell.element.page.siblings';
    case PageSlot = 'capell.element.slot';
    case Pages = 'capell.element.asset.pages';
    case Snippet = 'capell.element.snippet';
    case ApHeroBanner = 'capell.element.modern.hero-banner';
    case ApCardGrid = 'capell.element.modern.card-grid';
    case ApFeatureList = 'capell.element.modern.feature-list';
    case ApCTASection = 'capell.element.modern.cta-section';
    case ApImageGallery = 'capell.element.modern.image-gallery';
    case ApTeamMembers = 'capell.element.modern.team-members';
    case ApPricingTable = 'capell.element.modern.pricing-table';
    case ApTestimonials = 'capell.element.modern.testimonials';
    case ApFaqSection = 'capell.element.modern.faq-section';
    case ApStatsSection = 'capell.element.modern.stats-section';
    case ApAlternatingContent = 'capell.element.modern.alternating-content';
    case ApProcessSteps = 'capell.element.modern.process-steps';
}
