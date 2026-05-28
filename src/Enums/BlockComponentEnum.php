<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

enum BlockComponentEnum: string
{
    case AssetAccordion = 'capell.block.asset.accordion';
    case AssetBanner = 'capell.block.asset.banners';
    case AssetBlock = 'capell.block.asset.blocks';
    case AssetCarousel = 'capell.block.asset.carousel';
    case AssetFeatures = 'capell.block.asset.features';
    case AssetMedia = 'capell.block.asset.media';
    case AssetTestimonials = 'capell.block.asset.testimonials';
    case AnnouncementBar = 'capell.block.announcement-bar';
    case Assets = 'capell.block.asset';
    case BannerImage = 'capell.block.banner-image';
    case Default = 'capell.block.default';
    case Hero = 'capell.block.hero';
    case Navigation = 'capell.block.navigation';
    case NavigationTabs = 'capell.block.navigation.tabs';
    case PageBreadcrumbs = 'capell.block.page.breadcrumbs';
    case PageChildren = 'capell.block.page.children';
    case PageContent = 'capell.block.page.content';
    case PageLatest = 'capell.block.page.latest';
    case PageSiblings = 'capell.block.page.siblings';
    case PageSlot = 'capell.block.slot';
    case Pages = 'capell.block.asset.pages';
    case Snippet = 'capell.block.snippet';
    case ApHeroBanner = 'capell.block.modern.hero-banner';
    case ApCardGrid = 'capell.block.modern.card-grid';
    case ApFeatureList = 'capell.block.modern.feature-list';
    case ApCTASection = 'capell.block.modern.cta-section';
    case ApImageGallery = 'capell.block.modern.image-gallery';
    case ApTeamMembers = 'capell.block.modern.team-members';
    case ApPricingTable = 'capell.block.modern.pricing-table';
    case ApTestimonials = 'capell.block.modern.testimonials';
    case ApFaqSection = 'capell.block.modern.faq-section';
    case ApStatsSection = 'capell.block.modern.stats-section';
    case ApAlternatingContent = 'capell.block.modern.alternating-content';
    case ApProcessSteps = 'capell.block.modern.process-steps';
    case KitchenSinkRichText = 'capell.block.kitchen-sink.rich-text';
    case KitchenSinkStructuredText = 'capell.block.kitchen-sink.structured-text';
    case KitchenSinkDataDisplay = 'capell.block.kitchen-sink.data-display';
    case KitchenSinkForms = 'capell.block.kitchen-sink.forms';
    case KitchenSinkInteractions = 'capell.block.kitchen-sink.interactions';
    case KitchenSinkEmbeds = 'capell.block.kitchen-sink.embeds';
    case KitchenSinkUtilityStates = 'capell.block.kitchen-sink.utility-states';
}
