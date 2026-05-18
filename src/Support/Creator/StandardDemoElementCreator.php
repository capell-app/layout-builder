<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\MediaConversionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\DemoKit\Actions\DummyContentGeneratorAction;
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Capell\LayoutBuilder\Enums\ContentTypeEnum;
use Capell\LayoutBuilder\Enums\ElementComponentEnum;
use Capell\LayoutBuilder\Enums\ElementTypeEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Capell\Navigation\Models\Navigation;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class StandardDemoElementCreator extends BaseDemoCreator
{
    public function createContentElement(Collection $languages): Element
    {
        $siteId = Site::query()->default()?->value('id');

        $type = resolve(TypeCreator::class)->contentBuilderElementType();

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'example-content'], [
            'name' => 'Example Content',
            'blueprint_id' => $type->id,
            'meta' => [
                'size' => 'md',
                'margin' => 'none',
                'padding' => 'md',
                'reverse_order' => true,
                'background_color' => 'light-gray',
                'actions' => [
                    [
                        'type' => ActionLinkEnum::Page->value,
                        'pageable_type' => resolve(Page::class)->getMorphClass(),
                        'pageable_id' => Page::query()->where('site_id', $siteId)
                            ->whereHas(
                                'type',
                                /** @param Blueprint $query */
                                fn (BuilderContract $query): BuilderContract => $query->listable()->enabled()->accessible(),
                            )
                            ->inRandomOrder()
                            ->value('id'),
                        'site_id' => $siteId,
                    ],
                ],
            ],
        ]);

        $this->createElementMedia($element);

        foreach ($languages as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Example Content',
                    'content' => [
                        [
                            'type' => 'content',
                            'data' => [
                                'content' => DummyContentGeneratorAction::run($language->code),
                            ],
                        ],
                    ],
                ],
            );
        }

        return $element;
    }

    public function createSplitContentElement(Collection $languages): Element
    {
        $siteId = Site::query()->default()?->value('id');

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'example-split-content'], [
            'name' => 'Example Split Content',
            'blueprint_id' => $this->typeModel::query()->firstWhere(['key' => ElementTypeEnum::SectionBuilder, 'type' => LayoutTypeEnum::Element])->id,
            'meta' => [
                'align' => 'center',
                'size' => 'md',
                'style' => 'column',
                'padding' => 'xl',
                'margin' => 'none',
                'actions' => [
                    [
                        'type' => ActionLinkEnum::Page->value,
                        'pageable_type' => resolve(Page::class)->getMorphClass(),
                        'pageable_id' => Page::query()->where('site_id', $siteId)
                            ->whereHas(
                                'type',
                                /** @param Blueprint $query */
                                fn (BuilderContract $query): BuilderContract => $query->listable()->enabled()->accessible(),
                            )
                            ->inRandomOrder()
                            ->value('id'),
                        'site_id' => $siteId,
                    ],
                ],
            ],
        ]);

        $this->createElementMedia($element);

        foreach ($languages as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Example Content',
                    'content' => [
                        [
                            'type' => 'content',
                            'data' => [
                                'content' => str(DummyContentGeneratorAction::run($language->code))->limit(200)->toString(),
                            ],
                        ],
                    ],
                ],
            );
        }

        return $element;
    }

    public function createBannerImageElement(Collection $languages): Element
    {
        $element = resolve(ElementCreator::class)->bannerImageElement();

        $media = $this->createElementMedia($element);

        $meta = $element->meta;

        $meta['background_color'] = 'light-gray';
        $meta['background_image'] = $media->getFullUrl(MediaConversionEnum::Medium->value);

        $element->meta = $meta;

        foreach ($languages as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Example Banner',
                    'content' => DummyContentGeneratorAction::run($language->code),
                ],
            );
        }

        return $element;
    }

    public function createGalleryElement(): Element
    {
        $element = resolve(ElementCreator::class)->galleryElement();

        if ($element->assets()->exists()) {
            return $element;
        }

        for ($i = 1; $i <= 5; $i++) {
            $this->createElementMedia($element);
        }

        return $element;
    }

    public function createPageCardsElement(Pageable $page, string $container = 'main', int $occurrence = 1): Element
    {
        $element = resolve(ElementCreator::class)->pagesCardElement();

        if (
            $element->assets()
                ->where([
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                    'container' => $container,
                    'occurrence' => $occurrence,
                ])
                ->exists()
        ) {
            return $element;
        }

        $relatedPages = $this->pageModel::query()
            ->whereHas('type', fn (BuilderContract $query): BuilderContract => $query->default())
            ->whereHas('image')
            ->where('site_id', $page->site_id)
            ->notHomePage()
            ->inRandomOrder()
            ->limit(3)
            ->get();

        if ($relatedPages->isEmpty()) {
            return $element;
        }

        $relatedPages->each(
            fn (Page $relatedPage): ElementAsset => $this->createPageElementAsset($element, $page, $container, $occurrence, $relatedPage),
        );

        return $element;
    }

    public function createFaqElement(Collection $languages): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', 'assets');

        if ($elementType === null) {
            $elementType = resolve(TypeCreator::class)->assetsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'faq'], [
            'key' => 'faq',
            'name' => __('capell-admin::generic.faq'),
            'blueprint_id' => $elementType->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => ElementComponentEnum::AssetAccordion,
                'margin' => ['lg'],
                'align' => 'center',
            ],
            'admin' => [
                'asset_types' => [
                    'section',
                ],
            ],
        ]);

        foreach ($languages as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => __('capell-layout-builder::heading.faq'),
                    'content' => '<p>You can find answers for commonly asked questions</p>',
                ],
            );
        }

        $contentType = $this->typeModel::query()
            ->where('type', 'section')
            ->where('key', ContentTypeEnum::Builder)
            ->first();

        $parentContent = $this->contentModel::query()->firstOrCreate([
            'name' => 'FAQs',
            'blueprint_id' => $contentType->id,
        ], [
        ]);

        $questions = [
            'en' => [
                'How was this website created?',
                'What is the purpose of this website?',
                'Where did you learn to fly?',
                'When did you become so popular?',
                'Who else helped create this website?',
                'Why did you create this website?',
            ],
            'fr' => [
                'Comment ce site a-t-il été créé?',
                'Quel est le but de ce site?',
                'Où avez-vous appris à voler?',
                'Quand êtes-vous devenu si populaire?',
                'Qui d\'autre a aidé à créer ce site?',
                'Pourquoi avez-vous créé ce site?',
            ],
            'it' => [
                'Come è stato creato questo sito?',
                'Qual è lo scopo di questo sito?',
                'Dove hai imparato a volare?',
                'Quando sei diventato così popolare?',
                'Chi altro ha contribuito a creare questo sito?',
                'Perché hai creato questo sito?',
            ],
            'de' => [
                'Wie wurde diese Website erstellt?',
                'Was ist der Zweck dieser Website?',
                'Wo haben Sie fliegen gelernt?',
                'Wann sind Sie so beliebt geworden?',
                'Wer hat sonst noch bei der Erstellung dieser Website geholfen?',
                'Warum haben Sie diese Website erstellt?',
            ],
            'es' => [
                '¿Cómo se creó este sitio web?',
                '¿Cuál es el propósito de este sitio?',
                '¿Dónde aprendiste a volar?',
                '¿Cuándo te volviste tan popular?',
                '¿Quién más ayudó a crear este sitio?',
                '¿Por qué creaste este sitio?',
            ],
        ];

        foreach ($questions['en'] as $i => $question) {
            $content = $this->contentModel::query()->firstOrCreate([
                'name' => $question,
                'parent_id' => $parentContent->id,
                'blueprint_id' => $contentType->id,
            ]);

            $element->assets()->firstOrCreate([
                'asset_id' => $content->getKey(),
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);

            foreach ($languages as $language) {
                $desc_content = DummyContentGeneratorAction::run($language->code);

                $this->translationsFor($content)->updateOrCreate(
                    ['language_id' => $language->id],
                    [
                        'title' => Str::title($questions[$language->code][$i]),
                        'content' => [
                            [
                                'type' => 'content',
                                'data' => [
                                    'content' => $desc_content,
                                ],
                            ],
                        ],
                    ],
                );
            }
        }

        return $element;
    }

    public function createMediaCarouselElement(): Element
    {
        $element = resolve(ElementCreator::class)->mediaCarouselElement();

        if ($element->assets()->exists()) {
            return $element;
        }

        for ($i = 1; $i <= 7; $i++) {
            $this->createElementMedia($element);
        }

        $this->createElementMedia($element, type: 'video');

        return $element;
    }

    public function createStaticNavigationElement(Collection $languages, Site $site): Element
    {
        $model = Navigation::class;

        // Create menu + items
        $name = 'Example Menu';
        $key = Str::slug($name);

        $pages = Page::query()->where([
            'site_id' => $site->id,
        ])
            ->whereHas(
                'type',
                /** @param  Blueprint  $query */
                fn (BuilderContract $query): BuilderContract => $query->where('type', 'page')
                    ->enabled()
                    ->listable()
                    ->accessible()
                    ->hiddenSystemGroup(),
            )
            ->withWhereHas(
                'children',
                fn (BuilderContract $query): BuilderContract => $query->whereHas('type')->limit(2),
            )
            ->limit(4)
            ->get();

        $elementType = resolve(TypeCreator::class)->navigationElementType();

        $navigationType = $this->typeModel::query()->navigationType()->default()->first();
        if ($navigationType === null) {
            $navigationType = resolve(BlueprintCreator::class)->createNavigationType();
        }

        $navigation = CapellCore::isPackageInstalled(self::NavigationPackage) && class_exists($model)
            ? $model::query()->updateOrCreate([
                'key' => $key,
                'site_id' => $site->id,
                'blueprint_id' => $navigationType->id,
            ], [
                'name' => $name,
                'items' => $this->navigationPageItems($pages, $languages->first()),
            ])
            : null;

        // Create element
        $element = $this->elementModel::query()->firstOrCreate(['key' => 'example-navigation'], [
            'name' => __('Example Navigation'),
            'blueprint_id' => $elementType->id,
            'meta' => [
                'navigation' => $navigation instanceof Model ? (string) $navigation->getAttribute('key') : $key,
                'margin' => ['lg'],
            ],
        ]);

        foreach ($languages as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Example Navigation',
                ],
            );
        }

        return $element;
    }

    public function createContentsElement(Element $element, Pageable $page, string $container, int $occurrence = 1, ?Blueprint $type = null): void
    {
        $pageElementAssets = $element->assets()->where([
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'container' => $container,
            'occurrence' => $occurrence,
        ])
            ->exists();

        if ($pageElementAssets) {
            return;
        }

        if (! $type instanceof Blueprint) {
            $type = $this->typeModel::query()
                ->where('type', 'section')
                ->default()
                ->first();
        }

        $features = [
            [
                'title' => 'Empower Your Vision',
                'content' => '<p>Step into a world where your ideas become reality. Experience innovation and growth with us.</p>',
            ],
            [
                'title' => 'Start Your Journey',
                'content' => '<p>Begin your adventure today and unlock new opportunities for success.</p>',
            ],
            [
                'title' => 'Explore Our Achievements',
                'content' => '<p>Discover the groundbreaking projects and milestones that define our excellence.</p>',
            ],
            [
                'title' => 'See Our Story Unfold',
                'content' => '<p>Watch our journey and learn how we create impact through passion and expertise.</p>',
            ],
        ];

        foreach ($features as $feature) {
            $content = $this->contentModel::query()->firstOrCreate([
                'name' => $feature['title'],
                'blueprint_id' => $type->getKey(),
            ], [
                'meta' => [
                    'actions' => [
                        [
                            'type' => ActionLinkEnum::Page->value,
                            'pageable_type' => resolve(Page::class)->getMorphClass(),
                            'pageable_id' => Page::query()->where('site_id', $page->site->id)
                                ->whereHas(
                                    'type',
                                    /** @param Blueprint $query */
                                    fn (BuilderContract $query): BuilderContract => $query->listable()->enabled()->accessible(),
                                )
                                ->inRandomOrder()
                                ->value('id'),
                            'site_id' => $page->site->id,
                        ],
                        [
                            'type' => ActionLinkEnum::Page->value,
                            'pageable_type' => resolve(Page::class)->getMorphClass(),
                            'pageable_id' => Page::query()->where('site_id', $page->site->id)
                                ->whereHas(
                                    'type',
                                    /** @param Blueprint $query */
                                    fn (BuilderContract $query): BuilderContract => $query->listable()->enabled()->accessible(),
                                )
                                ->inRandomOrder()
                                ->value('id'),
                            'site_id' => $page->site->id,
                            'color' => 'secondary',
                        ],
                        [
                            'type' => ActionLinkEnum::Link->value,
                            'url' => 'https://example.com',
                            'label' => 'External',
                            'hide_label' => true,
                            'icon' => 'heroicon-o-arrow-top-right-on-square',
                            'color' => 'default',
                        ],
                    ],
                ],
            ]);

            foreach ($page->site->languages as $language) {
                $this->translationsFor($content)->updateOrCreate(
                    ['language_id' => $language->id],
                    [
                        'title' => $feature['title'],
                        'content' => sprintf('<p>%s</p>', $feature['content']),
                    ],
                );
            }

            $this->createMedia($content);

            $this->createPageElementAsset($element, $page, $container, $occurrence, $content);
        }
    }

    public function createClientLogosElement(Collection $languages): Element
    {
        $element = Element::query()->firstOrCreate([
            'key' => 'client-logos',
        ], [
            'name' => 'Client Logos',
            'blueprint_id' => $this->typeModel::query()->firstWhere(['key' => ElementTypeEnum::Assets, 'type' => LayoutTypeEnum::Element])->id,
            'meta' => [
                'align' => 'center',
                'margin' => ['lg'],
                'columns' => 6,
                'spacing' => 'lg',
                'max_width' => '3xl',
            ],
            'admin' => [
                'icon' => 'heroicon-o-photo',
            ],
        ]);

        if ($element->assets()->exists()) {
            return $element;
        }

        $languages->each(function (Language $language) use ($element): void {
            $element->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => 'Client Logos',
                'content' => '<p>We are proud to work with these amazing partners.</p>',
            ]);
        });

        for ($i = 1; $i <= 12; $i++) {
            $this->createElementMedia($element);
        }

        return $element;
    }

    public function createBusinessFeaturesElement(Site $site): Element
    {
        $element = Element::query()->firstOrCreate([
            'key' => 'business-features',
        ], [
            'name' => 'Business Features',
            'blueprint_id' => $this->typeModel::query()->firstWhere(['key' => ElementTypeEnum::Sections, 'type' => LayoutTypeEnum::Element])->id,
            'meta' => [
                'align' => 'center',
                'margin' => ['lg'],
                'view_file' => 'capell-foundation-theme::components.element.asset.features',
            ],
        ]);

        $this->createMedia($element);

        $title = 'Fundamental Capabilities That Set Us Apart';
        $content = '<p>We combine innovation, efficiency, and deep expertise to deliver exceptional results. Our adaptable, client-focused approach ensures measurable value and lasting impact.</p>';

        $site->languages->each(function (Language $language) use ($element, $title, $content): void {
            $element->translations()->updateOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => $title,
                'content' => $content,
            ]);
        });

        $features = $this->createFeatures($site);

        $features->each(function (Model $content) use ($element): void {
            if ($element->assets()->where('asset_id', $content->getKey())->exists()) {
                return;
            }

            $element->assets()->create([
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->getKey(),
            ]);
        });

        return $element;
    }

    public function createBannersElement(): Element
    {
        $creator = resolve(ElementCreator::class);
        $element = $creator->bannerElement();

        $site = Site::getDefault();

        $features = $this->createFeatures($site);

        $features->each(function (Model $content) use ($element): void {
            if ($element->assets()->where('asset_id', $content->getKey())->exists()) {
                return;
            }

            $element->assets()->create([
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->getKey(),
            ]);
        });

        return $element;
    }

    public function createTestimonialsElement(Collection $languages): Element
    {
        $elementCreator = resolve(ElementCreator::class);
        $element = $elementCreator->testimonialsElement();

        $this->createMedia($element, collection: MediaCollectionEnum::BackgroundImage);

        $languages->each(function (Language $language) use ($element): void {
            $element->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'What Our Clients Say',
            ]);
        });

        $testimonials = $this->createTestimonials($languages);

        $testimonials->each(function (Model $content) use ($element): void {
            if ($element->assets()->where('asset_id', $content->getKey())->exists()) {
                return;
            }

            $element->assets()->create([
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->getKey(),
            ]);
        });

        return $element;
    }

    public function createStatisticsElement(): Element
    {
        $element = $this->elementModel::query()->firstOrCreate(['key' => 'statistics'], [
            'name' => 'Statistic Blocks',
            'blueprint_id' => $this->typeModel::query()->firstWhere(['key' => ElementTypeEnum::Assets, 'type' => LayoutTypeEnum::Element])->id,
            'meta' => [
                'component_item' => FrontendComponentKeyEnum::SectionBlock->value,
                'view_file' => 'capell-foundation-theme::components.element.asset.blocks',
                'spacing' => 'none',
                'columns' => 4,
                'margin' => 'none',
                'container' => ContainerWidthEnum::Small->value,
            ],
            'admin' => [
                'icon' => 'heroicon-o-chart-bar',
            ],
        ]);

        if ($element->assets()->exists()) {
            return $element;
        }

        $statistics = [
            [
                'icon' => 'heroicon-o-users',
                'title' => 'Users',
                'value' => '<p><b>1,200</b></p>',
                'color' => 'primary',
            ],
            [
                'icon' => 'heroicon-o-chart-bar',
                'title' => 'Revenue Increases',
                'value' => '<p><b>300%</b></p>',
                'color' => 'success',
            ],
            [
                'icon' => 'heroicon-o-globe-alt',
                'title' => 'Countries Reached',
                'value' => '<p><b>50+</b></p>',
                'color' => 'info',
            ],
            [
                'icon' => 'heroicon-o-clock',
                'title' => 'Hours Worked',
                'value' => '<p><b>10,000+</b></p>',
                'color' => 'secondary',
            ],
        ];

        $site = Site::getDefault();

        foreach ($statistics as $statistic) {
            $content = $this->contentModel::query()->firstOrCreate([
                'name' => $statistic['title'],
            ], [
                'meta' => [
                    'icon' => $statistic['icon'],
                    'color' => $statistic['color'],
                ],
            ]);

            foreach ($site->languages as $language) {
                $this->translationsFor($content)->create([
                    'language_id' => $language->id,
                    'title' => $statistic['title'],
                    'content' => sprintf('<p>%s</p>', $statistic['value']),
                ]);
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $content->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createTeamPortfolioElement(Collection $languages): Element
    {
        $type = $this->typeModel::query()
            ->where([
                'key' => ElementTypeEnum::Sections,
                'type' => LayoutTypeEnum::Element,
            ])
            ->first();

        if ($type === null) {
            $type = resolve(TypeCreator::class)->contentsElementType();
        }

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'team-portfolio'], [
            'name' => 'Team Portfolio',
            'blueprint_id' => $type->id,
            'meta' => [
                'align' => 'center',
                'padding' => ['lg'],
                'columns' => 4,
                'spacing' => 'lg',
                'background_color' => 'light-gray',
                'with_summary' => true,
                'carousel_fade' => true,
                'carousel_arrows' => false,
                'carousel_pagination' => true,
                'carousel_loop' => true,
                'carousel_auto_play' => true,
                'carousel_auto_delay' => 50000,
                'component_item' => FrontendComponentKeyEnum::SectionTeamMember->value,
            ],
        ]);

        $languages->each(function (Language $language) use ($element): void {
            $element->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Meet Our Team',
                'content' => '<p>Discover the talented individuals behind our success.</p>',
            ]);
        });

        $teamMembers = $this->createTeamMembers($languages);

        $teamMembers->each(function (Model $content) use ($element): void {
            if ($element->assets()->where('asset_id', $content->getKey())->exists()) {
                return;
            }

            $element->assets()->create([
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->getKey(),
            ]);
        });

        return $element;
    }
}
