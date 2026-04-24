<?php

declare(strict_types=1);

use Capell\Themes\Core\Widgets\AbstractThemeWidget;
use Capell\Themes\Saas\Widgets\CTABannerWidget;
use Capell\Themes\Saas\Widgets\FAQAccordionWidget;
use Capell\Themes\Saas\Widgets\FeatureMatrixWidget;
use Capell\Themes\Saas\Widgets\HeroWithScreenshotWidget;
use Capell\Themes\Saas\Widgets\IntegrationsGridWidget;
use Capell\Themes\Saas\Widgets\PricingTableWidget;
use Capell\Themes\Saas\Widgets\SaasFooterWidget;
use Capell\Themes\Saas\Widgets\TestimonialsWallWidget;
use Capell\Themes\Saas\Widgets\UseCasesTabsWidget;

$widgets = [
    CTABannerWidget::class,
    FAQAccordionWidget::class,
    FeatureMatrixWidget::class,
    HeroWithScreenshotWidget::class,
    IntegrationsGridWidget::class,
    PricingTableWidget::class,
    SaasFooterWidget::class,
    TestimonialsWallWidget::class,
    UseCasesTabsWidget::class,
];

test('saas widgets can be instantiated via make()', function (string $class): void {
    $widget = $class::make();

    expect($widget)->toBeInstanceOf(AbstractThemeWidget::class)
        ->and($widget->name)->not->toBeEmpty()
        ->and($widget->description)->not->toBeEmpty()
        ->and($widget->view)->not->toBeEmpty();
})->with(array_map(fn (string $class): array => [$class], $widgets));
