<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Actions;

use Capell\Themes\Corporate\Widgets\BlogListingWidget;
use Capell\Themes\Corporate\Widgets\CaseStudiesCarouselWidget;
use Capell\Themes\Corporate\Widgets\ContactFormWidget;
use Capell\Themes\Corporate\Widgets\FeaturesGridWidget;
use Capell\Themes\Corporate\Widgets\HeroSectionWidget;
use Capell\Themes\Corporate\Widgets\TeamGridWidget;

/**
 * Seed 3 pre-built Mosaic layouts (home, about, contact) using the widgets
 * defined in this theme. If Mosaic is not installed we no-op and return [].
 */
class SeedCorporateLayoutsAction
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
                ['key' => $slug, 'theme' => 'corporate'],
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
                'name' => 'Corporate · Home',
                'widgets' => [
                    ['widget' => HeroSectionWidget::class, 'data' => ['title' => 'Built for serious businesses.']],
                    ['widget' => FeaturesGridWidget::class, 'data' => []],
                    ['widget' => CaseStudiesCarouselWidget::class, 'data' => []],
                    ['widget' => BlogListingWidget::class, 'data' => []],
                    ['widget' => ContactFormWidget::class, 'data' => []],
                ],
            ],
            'about' => [
                'name' => 'Corporate · About',
                'widgets' => [
                    ['widget' => HeroSectionWidget::class, 'data' => ['title' => 'About us', 'eyebrow' => 'Our story']],
                    ['widget' => TeamGridWidget::class, 'data' => []],
                    ['widget' => CaseStudiesCarouselWidget::class, 'data' => []],
                ],
            ],
            'contact' => [
                'name' => 'Corporate · Contact',
                'widgets' => [
                    ['widget' => HeroSectionWidget::class, 'data' => ['title' => 'Contact us', 'eyebrow' => 'Get in touch']],
                    ['widget' => ContactFormWidget::class, 'data' => []],
                ],
            ],
        ];
    }
}
