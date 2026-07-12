<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Models\Widget;

abstract class ApDemoWidgetCreator extends ModernDemoWidgetCreator
{
    public function createApHeroBannerWidget(): Widget
    {
        $widgetType = $this->requiredWidgetType(WidgetTypeEnum::HeroBanner, WidgetTypeEnum::Default);

        $widget = $this->widgetModel::query()->firstOrCreate(['key' => 'ap-hero-banner'], [
            'name' => 'AP Hero Banner',
            'blueprint_id' => $widgetType->id,
            'meta' => [
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '/docs/installation',
                'secondary_button_text' => 'View on GitHub',
                'secondary_button_url' => 'https://github.com/capell-app/capell',
                'margin' => ['none'],
            ],
        ]);

        foreach (Site::getDefault()->languages ?? [] as $language) {
            $widget->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Architecture-Grade CMS',
                    'content' => '<p>Build, ship, and scale content-driven platform-builder with precision and zero compromise.</p>',
                ],
            );
        }

        return $widget;
    }

    public function createApCardGridWidget(): Widget
    {
        $widgetType = $this->requiredWidgetType(WidgetTypeEnum::CardGrid, WidgetTypeEnum::Default);

        $widget = $this->widgetModel::query()->firstOrCreate(['key' => 'ap-card-grid'], [
            'name' => 'AP Card Grid',
            'blueprint_id' => $widgetType->id,
            'meta' => [
                'columns' => 3,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()->languages ?? [] as $language) {
            $widget->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'AP Card Grid'],
            );
        }

        if ($widget->assets()->exists()) {
            return $widget;
        }

        $cards = [
            ['icon' => '⚡', 'title' => 'Static-first Architecture', 'description' => 'Zero PHP on page load. Every request served from Nginx-cached HTML.', 'link_text' => 'Learn More', 'link_url' => '/docs/caching'],
            ['icon' => '🌐', 'title' => 'Multi-site Support', 'description' => 'One installation, unlimited sites with shared or isolated content pools.', 'link_text' => 'Learn More', 'link_url' => '/docs/multi-site'],
            ['icon' => '🎨', 'title' => 'Visual Layout Builder', 'description' => 'Drag-and-drop widgets with Livewire-powered live preview in Filament.', 'link_text' => 'Learn More', 'link_url' => '/docs/layout-builder'],
        ];

        foreach ($cards as $card) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $card['title']], [
                'meta' => [
                    'icon' => $card['icon'],
                    'link_text' => $card['link_text'],
                    'link_url' => $card['link_url'],
                ],
            ]);

            foreach (Site::getDefault()->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $card['title'], 'content' => sprintf('<p>%s</p>', $card['description'])],
                );
            }

            $widget->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $widget;
    }

    public function createApFeatureListWidget(): Widget
    {
        $widgetType = $this->requiredWidgetType(WidgetTypeEnum::FeatureList, WidgetTypeEnum::Default);

        $widget = $this->widgetModel::query()->firstOrCreate(['key' => 'ap-feature-list'], [
            'name' => 'AP Feature List',
            'blueprint_id' => $widgetType->id,
            'meta' => [
                'layout' => 'vertical',
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()->languages ?? [] as $language) {
            $widget->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'AP Feature List'],
            );
        }

        if ($widget->assets()->exists()) {
            return $widget;
        }

        $features = [
            ['icon' => '✓', 'title' => 'Soft-radius design', 'description' => '8px controls and 16px layout containers keep the workspace precise without feeling severe.'],
            ['icon' => '▲', 'title' => 'Blue accent system', 'description' => 'Primary blue (#4648D4) against quiet neutral surfaces for maximum clarity.'],
            ['icon' => '◆', 'title' => 'Tonal border language', 'description' => '1px structural lines and soft blue focus rings define state without heavy decoration.'],
            ['icon' => '●', 'title' => 'Ambient depth layering', 'description' => 'Soft shadows and tonal layers separate canvas, containers, and floating controls.'],
        ];

        foreach ($features as $feature) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $feature['title']], [
                'meta' => ['icon' => $feature['icon']],
            ]);

            foreach (Site::getDefault()->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $feature['title'], 'content' => sprintf('<p>%s</p>', $feature['description'])],
                );
            }

            $widget->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $widget;
    }

    public function createFeatureListWidget(): Widget
    {
        $widget = resolve(WidgetCreator::class)->featuresWidget();

        foreach (Site::getDefault()->languages ?? [] as $language) {
            $widget->translations()->firstOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Features'],
            );
        }

        if ($widget->assets()->exists()) {
            return $widget;
        }

        $features = [
            ['icon' => 'heroicon-o-light-bulb', 'title' => 'Reusable CMS Patterns', 'description' => 'We use Laravel packages, Filament resources, and reusable widgets to keep CMS implementations maintainable.'],
            ['icon' => 'heroicon-o-academic-cap', 'title' => 'Deep Expertise', 'description' => 'Our team brings deep industry knowledge and experience to every project.'],
            ['icon' => 'heroicon-o-user-group', 'title' => 'Client-Centric Approach', 'description' => "We prioritize our clients' needs and work collaboratively to achieve their goals."],
            ['icon' => 'heroicon-o-chart-bar', 'title' => 'Operational Checks', 'description' => 'We ship with checks for content, assets, cache, and frontend output so teams can verify each release.'],
            ['icon' => 'heroicon-o-sparkles', 'title' => 'Sustainable Practices', 'description' => 'We are committed to sustainable practices that benefit our clients and the environment.'],
            ['icon' => 'heroicon-o-globe-alt', 'title' => 'Global Reach', 'description' => 'Our global presence allows us to serve clients across diverse markets and industries.'],
        ];

        foreach ($features as $feature) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $feature['title']], [
                'meta' => ['icon' => $feature['icon']],
            ]);

            foreach (Site::getDefault()->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $feature['title'], 'content' => sprintf('<p>%s</p>', $feature['description'])],
                );
            }

            $widget->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $widget;
    }

    public function createApCtaSectionWidget(): Widget
    {
        $widgetType = $this->requiredWidgetType(WidgetTypeEnum::CTASection, WidgetTypeEnum::Default);

        $widget = $this->widgetModel::query()->firstOrCreate(['key' => 'ap-cta-section'], [
            'name' => 'AP CTA Section',
            'blueprint_id' => $widgetType->id,
            'meta' => [
                'primary_button_text' => 'Get Started Free',
                'primary_button_url' => '/docs/installation',
                'secondary_button_text' => 'View on GitHub',
                'secondary_button_url' => 'https://github.com/capell-app/capell',
                'margin' => ['none'],
            ],
        ]);

        foreach (Site::getDefault()->languages ?? [] as $language) {
            $widget->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Ready to build with precision?',
                    'content' => '<p>Join the growing community of developers shipping content platform-builder on Capell.</p>',
                ],
            );
        }

        return $widget;
    }

    public function createApImageGalleryWidget(): Widget
    {
        $widgetType = $this->requiredWidgetType(WidgetTypeEnum::ImageGallery, WidgetTypeEnum::Default);

        $widget = $this->widgetModel::query()->firstOrCreate(['key' => 'ap-image-gallery'], [
            'name' => 'AP Image Gallery',
            'blueprint_id' => $widgetType->id,
            'meta' => [
                'layout' => 'grid',
                'columns' => 3,
                'lightbox' => true,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()->languages ?? [] as $language) {
            $widget->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Our Work'],
            );
        }

        if ($widget->assets()->exists()) {
            return $widget;
        }

        for ($i = 1; $i <= 6; $i++) {
            $this->createWidgetMedia($widget);
        }

        return $widget;
    }

    public function addSplitTwoBackgroundMedia(Layout $layout): void
    {
        if ($layout->getMedia('split-two-background')->isNotEmpty()) {
            return;
        }

        $this->createMedia($layout, collection: 'split-two-background');
    }
}
