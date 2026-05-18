<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Enums\ElementComponentEnum;
use Capell\LayoutBuilder\Enums\ElementTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Element;

abstract class ModernDemoElementCreator extends StandardDemoElementCreator
{
    public function createModernFeatureListElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-feature-list'], [
            'name' => 'Modern Feature List',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApFeatureList,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Why Choose Our Platform'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $features = [
            ['icon' => '🚀', 'title' => 'Lightning Fast', 'description' => 'Static-first architecture delivers every page from Nginx-cached HTML with zero PHP on page load.'],
            ['icon' => '🔒', 'title' => 'Enterprise Security', 'description' => 'Built-in authentication, role-based access control, and secure content workflows.'],
            ['icon' => '🌐', 'title' => 'Multi-site Ready', 'description' => 'One installation, unlimited sites with shared or isolated content pools out of the box.'],
            ['icon' => '🎨', 'title' => 'Visual Layout Builder', 'description' => 'Drag-and-drop elements with Livewire-powered live preview directly in the Filament admin.'],
            ['icon' => '⚙️', 'title' => 'Developer Friendly', 'description' => 'Built on Laravel with clean APIs, extensible packages, and first-class PHPStan support.'],
            ['icon' => '📦', 'title' => 'Modular Packages', 'description' => 'Install only what you need. Blog, address, ai-orchestrator, and layout-builder are all optional add-ons.'],
        ];

        foreach ($features as $feature) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $feature['title']], [
                'meta' => ['icon' => $feature['icon']],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $feature['title'], 'content' => sprintf('<p>%s</p>', $feature['description'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createModernTeamMembersElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-team-members'], [
            'name' => 'Modern Team Members',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApTeamMembers,
                'columns' => 3,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Our Team'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $members = [
            [
                'icon' => '👩‍💼',
                'name' => 'Alex Morgan',
                'position' => 'Product Lead',
                'bio' => 'Creative designer with 5+ years building user-centred digital products.',
                'tags' => ['Design', 'Leadership'],
                'social' => ['twitter' => 'https://twitter.com', 'linkedin' => 'https://linkedin.com'],
            ],
            [
                'icon' => '👨‍🔬',
                'name' => 'Emma Davis',
                'position' => 'Engineering Manager',
                'bio' => 'Full-stack developer and systems architect with a passion for clean APIs.',
                'tags' => ['Engineering', 'Architecture'],
                'social' => ['github' => 'https://github.com', 'linkedin' => 'https://linkedin.com'],
            ],
            [
                'icon' => '🧑‍💼',
                'name' => 'James Wilson',
                'position' => 'CEO & Co-founder',
                'bio' => 'Serial entrepreneur and technology visionary driving our strategic direction.',
                'tags' => ['Strategy', 'Leadership'],
                'social' => ['twitter' => 'https://twitter.com', 'linkedin' => 'https://linkedin.com'],
            ],
        ];

        foreach ($members as $member) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $member['name']], [
                'meta' => [
                    'icon' => $member['icon'],
                    'position' => $member['position'],
                    'tags' => $member['tags'],
                    'social' => $member['social'],
                ],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $member['name'], 'content' => sprintf('<p>%s</p>', $member['bio'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createModernPricingTableElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-pricing-table'], [
            'name' => 'Modern Pricing Table',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApPricingTable,
                'currency' => '$',
                'billing_options' => 'both',
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Simple, Transparent Pricing'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $plans = [
            [
                'name' => 'Starter',
                'description' => 'For individuals and small projects',
                'price' => '29',
                'price_annual' => '290',
                'featured' => false,
                'cta_label' => 'Get Started',
                'cta_url' => '#',
                'features' => ['Up to 5 pages', '1 site', 'Email support', 'Basic elements'],
            ],
            [
                'name' => 'Professional',
                'description' => 'For growing teams and businesses',
                'price' => '79',
                'price_annual' => '790',
                'featured' => true,
                'cta_label' => 'Start Free Trial',
                'cta_url' => '#',
                'features' => ['Unlimited pages', '5 sites', 'Priority support', 'All elements', 'Multi-language'],
            ],
            [
                'name' => 'Enterprise',
                'description' => 'For large-scale deployments',
                'price' => 'Custom',
                'price_annual' => 'Custom',
                'featured' => false,
                'cta_label' => 'Contact Sales',
                'cta_url' => '#',
                'features' => ['Unlimited everything', 'Dedicated support', 'Custom integrations', 'SLA guarantee'],
            ],
        ];

        foreach ($plans as $plan) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $plan['name']], [
                'meta' => [
                    'price' => $plan['price'],
                    'price_annual' => $plan['price_annual'],
                    'featured' => $plan['featured'],
                    'cta_label' => $plan['cta_label'],
                    'cta_url' => $plan['cta_url'],
                    'features' => $plan['features'],
                ],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $plan['name'], 'content' => sprintf('<p>%s</p>', $plan['description'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createModernTestimonialsElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-testimonials'], [
            'name' => 'Modern Testimonials',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApTestimonials,
                'columns' => 2,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'What Customers Say'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $testimonials = [
            ['icon' => '👩‍💼', 'author' => 'Sarah Johnson', 'position' => 'Marketing Manager', 'quote' => 'Amazing experience! Capell made it so easy to manage our content across multiple sites without any technical hassle.'],
            ['icon' => '👨‍💼', 'author' => 'Mike Chen', 'position' => 'CEO', 'quote' => 'Switched from other CMS platform-builder and it was the best decision we ever made. The static caching alone paid for itself.'],
            ['icon' => '🧑‍💻', 'author' => 'Priya Patel', 'position' => 'Lead Developer', 'quote' => 'The Filament integration and extensible package system means we can ship new features in days, not weeks.'],
        ];

        foreach ($testimonials as $testimonial) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $testimonial['author']], [
                'meta' => [
                    'icon' => $testimonial['icon'],
                    'position' => $testimonial['position'],
                ],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $testimonial['author'], 'content' => sprintf('<p>%s</p>', $testimonial['quote'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createModernFaqElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-faq'], [
            'name' => 'Modern FAQ Section',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApFaqSection,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Frequently Asked Questions'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $faqs = [
            ['category' => 'Getting Started', 'question' => 'How do I get started with Capell?', 'answer' => 'Install Capell via Composer, run the setup command, and follow our documentation. You can be up and running in under an hour.'],
            ['category' => 'Getting Started', 'question' => 'Do I need coding knowledge?', 'answer' => 'No! Capell is designed for content editors. Use the Filament admin panel to manage all your content without writing a single line of code.'],
            ['category' => 'Features', 'question' => 'Can I customise the design?', 'answer' => 'Absolutely. Capell provides a complete design system with tokens for colours, typography, and spacing. Customise everything to match your brand.'],
            ['category' => 'Features', 'question' => 'Does it support multiple languages?', 'answer' => 'Yes. Capell has first-class multi-language support built in, including per-site language configuration and translation management.'],
            ['category' => 'Pricing', 'question' => 'Is there a free trial?', 'answer' => 'Capell is open source. You can self-host for free. Commercial support and managed hosting plans are available separately.'],
        ];

        foreach ($faqs as $faq) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $faq['question']], [
                'meta' => ['category' => $faq['category']],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $faq['question'], 'content' => sprintf('<p>%s</p>', $faq['answer'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createModernStatsSectionElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-stats'], [
            'name' => 'Modern Stats Section',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApStatsSection,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'By The Numbers'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $stats = [
            ['icon' => '🚀', 'label' => 'Deployments per day', 'value' => '10,000+'],
            ['icon' => '🌐', 'label' => 'Sites powered', 'value' => '2,500+'],
            ['icon' => '⚡', 'label' => 'Avg page load time', 'value' => '< 50ms'],
            ['icon' => '💯', 'label' => 'Customer satisfaction', 'value' => '99.8%'],
        ];

        foreach ($stats as $stat) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $stat['label']], [
                'meta' => ['icon' => $stat['icon']],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $stat['label'], 'content' => sprintf('<p>%s</p>', $stat['value'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createModernAlternatingContentElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-alternating-content'], [
            'name' => 'Modern Alternating Content',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApAlternatingContent,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'How It Works'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $steps = [
            ['icon' => '🎨', 'position' => 'left', 'title' => 'Design Your Layout', 'description' => 'Choose from dozens of pre-built element types and arrange them visually with the LayoutBuilder layout builder.'],
            ['icon' => '⚙️', 'position' => 'right', 'title' => 'Configure & Customise', 'description' => 'Adjust every detail — typography, colours, spacing — using Filament-powered admin form-builder with live preview.'],
            ['icon' => '🚀', 'position' => 'left', 'title' => 'Publish Instantly', 'description' => 'One click publishes your changes. Static caching means your visitors see the update in milliseconds.'],
        ];

        foreach ($steps as $step) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $step['title']], [
                'meta' => ['icon' => $step['icon'], 'position' => $step['position']],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $step['title'], 'content' => sprintf('<p>%s</p>', $step['description'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createModernProcessStepsElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-process-steps'], [
            'name' => 'Modern Process Steps',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApProcessSteps,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Our Process'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $steps = [
            ['icon' => '📋', 'title' => 'Discovery', 'description' => 'We learn about your goals, audience, and content requirements in a focused kick-off session.'],
            ['icon' => '🏗️', 'title' => 'Architecture', 'description' => 'Our team designs the site structure, element library, and data model tailored to your needs.'],
            ['icon' => '🎨', 'title' => 'Design & Build', 'description' => 'Layouts are assembled in LayoutBuilder, styles applied through the design system, and content seeded.'],
            ['icon' => '🚀', 'title' => 'Launch', 'description' => 'We run preflight checks, warm the cache, and hand over a fully documented, production-ready platform.'],
        ];

        foreach ($steps as $step) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $step['title']], [
                'meta' => ['icon' => $step['icon']],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $this->translationsFor($section)->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $step['title'], 'content' => sprintf('<p>%s</p>', $step['description'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createModernImageGalleryElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::Assets);

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'modern-image-gallery'], [
            'name' => 'Modern Image Gallery',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'component' => ElementComponentEnum::ApImageGallery,
                'columns' => 3,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Our Work'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        for ($i = 1; $i <= 6; $i++) {
            $this->createElementMedia($element);
        }

        return $element;
    }
}
