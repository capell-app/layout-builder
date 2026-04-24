<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\AgencyFooterWidget;
use Capell\Themes\Agency\Widgets\AwardsBadgesWidget;
use Capell\Themes\Agency\Widgets\ClientsMarqueeWidget;
use Capell\Themes\Agency\Widgets\ContactInquiryWidget;
use Capell\Themes\Agency\Widgets\HeroStatementWidget;
use Capell\Themes\Agency\Widgets\PortfolioGridWidget;
use Capell\Themes\Agency\Widgets\ProcessFlowWidget;
use Capell\Themes\Agency\Widgets\ServicesShowcaseWidget;
use Capell\Themes\Agency\Widgets\TestimonialsQuoteWidget;
use Capell\Themes\Core\Widgets\AbstractThemeWidget;

$widgets = [
    AgencyFooterWidget::class,
    AwardsBadgesWidget::class,
    ClientsMarqueeWidget::class,
    ContactInquiryWidget::class,
    HeroStatementWidget::class,
    PortfolioGridWidget::class,
    ProcessFlowWidget::class,
    ServicesShowcaseWidget::class,
    TestimonialsQuoteWidget::class,
];

test('agency widgets can be instantiated via make()', function (string $class): void {
    $widget = $class::make();

    expect($widget)->toBeInstanceOf(AbstractThemeWidget::class)
        ->and($widget->name)->not->toBeEmpty()
        ->and($widget->description)->not->toBeEmpty()
        ->and($widget->view)->not->toBeEmpty();
})->with(array_map(fn (string $class): array => [$class], $widgets));
