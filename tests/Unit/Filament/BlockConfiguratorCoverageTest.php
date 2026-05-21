<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Filament\Configurators\Blocks\AssetsBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\CardGridBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\CarouselBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\CTASectionBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\DefaultBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\FeatureListBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\HeroBannerBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\HeroBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ImageGalleryBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernAlternatingContentConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernCardGridConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernCTASectionConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernFaqConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernFeatureListConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernHeroBannerConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernImageGalleryConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernPricingTableConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernProcessStepsConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernStatsSectionConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernTeamMembersConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ModernTestimonialsConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\NavigationBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\PageContentBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\ResultsBlockConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Blocks\SystemBlockConfigurator;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

it('builds block configurator schemas for each supported form operation', function (
    string $configuratorClass,
    string $operation,
): void {
    $components = (new $configuratorClass)->make(Schema::make()->operation($operation));

    expect($components)->not->toBeEmpty()
        ->and($components)
        ->each->toBeInstanceOf(Component::class);
})->with([
    'default create' => [DefaultBlockConfigurator::class, 'create'],
    'default create option' => [DefaultBlockConfigurator::class, 'createOption'],
    'default edit option' => [DefaultBlockConfigurator::class, 'editOption'],
    'assets create' => [AssetsBlockConfigurator::class, 'create'],
    'assets edit option' => [AssetsBlockConfigurator::class, 'editOption'],
    'carousel edit option' => [CarouselBlockConfigurator::class, 'editOption'],
    'hero edit option' => [HeroBlockConfigurator::class, 'editOption'],
    'navigation create option' => [NavigationBlockConfigurator::class, 'createOption'],
    'navigation edit option' => [NavigationBlockConfigurator::class, 'editOption'],
    'navigation form' => [NavigationBlockConfigurator::class, 'edit'],
    'page content option' => [PageContentBlockConfigurator::class, 'editOption'],
    'page content form' => [PageContentBlockConfigurator::class, 'edit'],
    'results option' => [ResultsBlockConfigurator::class, 'editOption'],
    'results form' => [ResultsBlockConfigurator::class, 'edit'],
    'system option' => [SystemBlockConfigurator::class, 'editOption'],
    'system form' => [SystemBlockConfigurator::class, 'edit'],
    'hero banner option' => [HeroBannerBlockConfigurator::class, 'editOption'],
    'card grid option' => [CardGridBlockConfigurator::class, 'editOption'],
    'feature list option' => [FeatureListBlockConfigurator::class, 'editOption'],
    'cta option' => [CTASectionBlockConfigurator::class, 'editOption'],
    'image gallery option' => [ImageGalleryBlockConfigurator::class, 'editOption'],
]);

it('exposes static modern block configurator schemas and defaults', function (
    string $configuratorClass,
    string $expectedDefaultKey,
    mixed $expectedDefaultValue,
): void {
    $schema = $configuratorClass::getFormSchema();
    $defaults = $configuratorClass::getDefaults();

    expect($schema)
        ->not->toBeEmpty()
        ->each->toBeInstanceOf(Component::class)
        ->and($defaults[$expectedDefaultKey])->toBe($expectedDefaultValue);
})->with([
    'alternating content' => [ModernAlternatingContentConfigurator::class, 'title', 'How It Works'],
    'card grid' => [ModernCardGridConfigurator::class, 'title', 'Featured Blocks'],
    'cta section' => [ModernCTASectionConfigurator::class, 'heading', 'Ready to Create Stunning Layouts?'],
    'faq' => [ModernFaqConfigurator::class, 'title', 'Frequently Asked Questions'],
    'feature list' => [ModernFeatureListConfigurator::class, 'title', 'Why Choose Our Platform'],
    'hero banner' => [ModernHeroBannerConfigurator::class, 'title', 'Welcome to Capell'],
    'image gallery' => [ModernImageGalleryConfigurator::class, 'title', 'Our Work'],
    'pricing table' => [ModernPricingTableConfigurator::class, 'title', 'Simple, Transparent Pricing'],
    'process steps' => [ModernProcessStepsConfigurator::class, 'title', 'Our Process'],
    'stats section' => [ModernStatsSectionConfigurator::class, 'title', 'By The Numbers'],
    'team members' => [ModernTeamMembersConfigurator::class, 'title', 'Our Team'],
    'testimonials' => [ModernTestimonialsConfigurator::class, 'title', 'What Customers Say'],
]);
