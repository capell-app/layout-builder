<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Actions;

use Capell\Themes\Agency\Widgets\AwardsBadgesWidget;
use Capell\Themes\Agency\Widgets\ClientsMarqueeWidget;
use Capell\Themes\Agency\Widgets\ContactInquiryWidget;
use Capell\Themes\Agency\Widgets\HeroStatementWidget;
use Capell\Themes\Agency\Widgets\PortfolioGridWidget;
use Capell\Themes\Agency\Widgets\ProcessFlowWidget;
use Capell\Themes\Agency\Widgets\ServicesShowcaseWidget;
use Capell\Themes\Agency\Widgets\TestimonialsQuoteWidget;

/**
 * Seed 3 pre-built Mosaic layouts (home, work, contact) using the widgets
 * defined in this theme. If Mosaic is not installed we no-op and return [].
 */
class SeedAgencyLayoutsAction
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
                ['key' => $slug, 'theme' => 'agency'],
                [
                    'name' => $definition['name'],
                    'widgets' => $definition['widgets'],
                    'status' => 1,
                ],
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
                'name' => 'Agency · Home',
                'widgets' => [
                    ['widget' => HeroStatementWidget::class, 'data' => ['statement' => 'Brands worth the attention they demand.']],
                    ['widget' => ClientsMarqueeWidget::class, 'data' => []],
                    ['widget' => PortfolioGridWidget::class, 'data' => []],
                    ['widget' => ServicesShowcaseWidget::class, 'data' => []],
                    ['widget' => ProcessFlowWidget::class, 'data' => []],
                    ['widget' => TestimonialsQuoteWidget::class, 'data' => []],
                    ['widget' => AwardsBadgesWidget::class, 'data' => []],
                    ['widget' => ContactInquiryWidget::class, 'data' => []],
                ],
            ],
            'work' => [
                'name' => 'Agency · Work',
                'widgets' => [
                    ['widget' => HeroStatementWidget::class, 'data' => ['statement' => 'The work.', 'eyebrow' => 'Case studies']],
                    ['widget' => PortfolioGridWidget::class, 'data' => []],
                    ['widget' => TestimonialsQuoteWidget::class, 'data' => []],
                ],
            ],
            'contact' => [
                'name' => 'Agency · Contact',
                'widgets' => [
                    ['widget' => HeroStatementWidget::class, 'data' => ['statement' => "Let's make something.", 'eyebrow' => 'Start a project']],
                    ['widget' => ContactInquiryWidget::class, 'data' => []],
                ],
            ],
        ];
    }
}
