<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Actions;

use Capell\Themes\Saas\Widgets\CTABannerWidget;
use Capell\Themes\Saas\Widgets\FAQAccordionWidget;
use Capell\Themes\Saas\Widgets\FeatureMatrixWidget;
use Capell\Themes\Saas\Widgets\HeroWithScreenshotWidget;
use Capell\Themes\Saas\Widgets\IntegrationsGridWidget;
use Capell\Themes\Saas\Widgets\PricingTableWidget;
use Capell\Themes\Saas\Widgets\TestimonialsWallWidget;
use Capell\Themes\Saas\Widgets\UseCasesTabsWidget;

/**
 * Seed 3 pre-built Mosaic layouts (home, pricing, features) using the widgets
 * defined in this theme. If Mosaic is not installed we no-op and return [].
 */
class SeedSaasLayoutsAction
{
    /**
     * @return array<int, int> Layout IDs created (empty if Mosaic absent).
     */
    public function handle(): array
    {
        if (! class_exists('Capell\\Mosaic\\Models\\Layout')) {
            return [];
        }

        $layoutClass = 'Capell\\Mosaic\\Models\\Layout';
        $created = [];

        foreach ($this->layouts() as $slug => $definition) {
            /** @var object $model */
            $model = $layoutClass::query()->updateOrCreate(
                ['key' => $slug, 'theme' => 'saas'],
                [
                    'name' => $definition['name'],
                    'widgets' => $definition['widgets'],
                    'status' => 1,
                ]
            );

            if (isset($model->id)) {
                $created[] = (int) $model->id;
            }
        }

        return $created;
    }

    /**
     * Layout definitions. Widgets are described as [class, data] tuples.
     *
     * @return array<string, array{name: string, widgets: array<int, array{widget: class-string, data: array<string, mixed>}>}>
     */
    public function layouts(): array
    {
        return [
            'home' => [
                'name' => 'SaaS · Home',
                'widgets' => [
                    ['widget' => HeroWithScreenshotWidget::class, 'data' => []],
                    ['widget' => IntegrationsGridWidget::class, 'data' => []],
                    ['widget' => FeatureMatrixWidget::class, 'data' => []],
                    ['widget' => UseCasesTabsWidget::class, 'data' => []],
                    ['widget' => PricingTableWidget::class, 'data' => []],
                    ['widget' => TestimonialsWallWidget::class, 'data' => []],
                    ['widget' => FAQAccordionWidget::class, 'data' => []],
                    ['widget' => CTABannerWidget::class, 'data' => []],
                ],
            ],
            'pricing' => [
                'name' => 'SaaS · Pricing',
                'widgets' => [
                    ['widget' => HeroWithScreenshotWidget::class, 'data' => ['title' => 'Simple, transparent pricing', 'eyebrow' => 'Pricing', 'subtitle' => 'No surprises. Cancel anytime.']],
                    ['widget' => PricingTableWidget::class, 'data' => []],
                    ['widget' => FeatureMatrixWidget::class, 'data' => []],
                    ['widget' => FAQAccordionWidget::class, 'data' => []],
                    ['widget' => CTABannerWidget::class, 'data' => []],
                ],
            ],
            'features' => [
                'name' => 'SaaS · Features',
                'widgets' => [
                    ['widget' => HeroWithScreenshotWidget::class, 'data' => ['title' => 'Everything you need to ship great products', 'eyebrow' => 'Features']],
                    ['widget' => UseCasesTabsWidget::class, 'data' => []],
                    ['widget' => IntegrationsGridWidget::class, 'data' => []],
                    ['widget' => TestimonialsWallWidget::class, 'data' => []],
                    ['widget' => CTABannerWidget::class, 'data' => []],
                ],
            ],
        ];
    }
}
