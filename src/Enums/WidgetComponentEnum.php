<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum WidgetComponentEnum: string
{
    case AssetAccordion = 'capell.widget.asset.accordion';
    case AssetBanner = 'capell.widget.asset.banners';
    case AssetWidget = 'capell.widget.asset.widgets';
    case AssetCarousel = 'capell.widget.asset.carousel';
    case AssetFeatures = 'capell.widget.asset.features';
    case AssetMedia = 'capell.widget.asset.media';
    case AssetTestimonials = 'capell.widget.asset.testimonials';
    case AnnouncementBar = 'capell.widget.announcement-bar';
    case Assets = 'capell.widget.asset';
    case BannerImage = 'capell.widget.banner-image';
    case Default = 'capell.widget.default';
    case Hero = 'capell.widget.hero';
    case Navigation = 'capell.widget.navigation';
    case NavigationTabs = 'capell.widget.navigation.tabs';
    case PageBreadcrumbs = 'capell.widget.page.breadcrumbs';
    case PageChildren = 'capell.widget.page.children';
    case PageContent = 'capell.widget.page.content';
    case PageLatest = 'capell.widget.page.latest';
    case PageSiblings = 'capell.widget.page.siblings';
    case PageSlot = 'capell.widget.slot';
    case Pages = 'capell.widget.asset.pages';
    case Snippet = 'capell.widget.snippet';
    case ApHeroBanner = 'capell.widget.modern.hero-banner';
    case ApCardGrid = 'capell.widget.modern.card-grid';
    case ApFeatureList = 'capell.widget.modern.feature-list';
    case ApCTASection = 'capell.widget.modern.cta-section';
    case ApImageGallery = 'capell.widget.modern.image-gallery';
    case ApTeamMembers = 'capell.widget.modern.team-members';
    case ApPricingTable = 'capell.widget.modern.pricing-table';
    case ApTestimonials = 'capell.widget.modern.testimonials';
    case ApFaqSection = 'capell.widget.modern.faq-section';
    case ApStatsSection = 'capell.widget.modern.stats-section';
    case ApAlternatingContent = 'capell.widget.modern.alternating-content';
    case ApProcessSteps = 'capell.widget.modern.process-steps';
    case KitchenSinkRichText = 'capell.widget.kitchen-sink.rich-text';
    case KitchenSinkStructuredText = 'capell.widget.kitchen-sink.structured-text';
    case KitchenSinkDataDisplay = 'capell.widget.kitchen-sink.data-display';
    case KitchenSinkForms = 'capell.widget.kitchen-sink.forms';
    case KitchenSinkInteractions = 'capell.widget.kitchen-sink.interactions';
    case KitchenSinkEmbeds = 'capell.widget.kitchen-sink.embeds';
    case KitchenSinkUtilityStates = 'capell.widget.kitchen-sink.utility-states';
}
