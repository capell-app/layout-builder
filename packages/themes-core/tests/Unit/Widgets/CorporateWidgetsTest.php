<?php

declare(strict_types=1);

use Capell\Themes\Core\Widgets\AbstractThemeWidget;
use Capell\Themes\Corporate\Widgets\BlogListingWidget;
use Capell\Themes\Corporate\Widgets\CaseStudiesCarouselWidget;
use Capell\Themes\Corporate\Widgets\ContactFormWidget;
use Capell\Themes\Corporate\Widgets\FeaturesGridWidget;
use Capell\Themes\Corporate\Widgets\FooterWidget;
use Capell\Themes\Corporate\Widgets\HeroSectionWidget;
use Capell\Themes\Corporate\Widgets\TeamGridWidget;

$widgets = [
    BlogListingWidget::class,
    CaseStudiesCarouselWidget::class,
    ContactFormWidget::class,
    FeaturesGridWidget::class,
    FooterWidget::class,
    HeroSectionWidget::class,
    TeamGridWidget::class,
];

test('corporate widgets can be instantiated via make()', function (string $class): void {
    $widget = $class::make();

    expect($widget)->toBeInstanceOf(AbstractThemeWidget::class)
        ->and($widget->name)->not->toBeEmpty()
        ->and($widget->description)->not->toBeEmpty()
        ->and($widget->view)->not->toBeEmpty();
})->with(array_map(fn (string $class): array => [$class], $widgets));
