<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use BackedEnum;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\MediaConversionEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
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
use Capell\Navigation\Support\Creator\NavigationCreator;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;

class DemoCreator
{
    private const DEMO_CREATOR = \Capell\DemoKit\Support\Creator\DemoCreator::class;

    private const DemoKitPackage = 'capell-app/demo-kit';

    private const NavigationPackage = 'capell-app/navigation';

    /**
     * @var class-string<Model>
     */
    private readonly string $contentModel;

    /**
     * @var class-string<Element>
     */
    private readonly string $elementModel;

    /**
     * @var class-string<Blueprint>
     */
    private readonly string $typeModel;

    /**
     * @var class-string<Page>
     */
    private readonly string $pageModel;

    public function __construct(
        protected readonly ?Model $user = null,
    ) {
        throw_unless(CapellCore::hasAsset('Section'), RuntimeException::class, 'Content Sections must be installed to create section demo content.');
        $this->contentModel = CapellCore::getAsset('Section')->model;
        $this->elementModel = Element::class;
        $this->typeModel = Blueprint::class;
        $this->pageModel = Page::class;
    }

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

        $relatedPages->each(fn (Page $relatedPage): ElementAsset => $element->assets()->create([
            'pageable_id' => $page->id,
            'pageable_type' => $page->getMorphClass(),
            'asset_id' => $relatedPage->id,
            'asset_type' => resolve($this->pageModel)->getMorphClass(),
            'container' => $container,
            'occurrence' => $occurrence,
        ]));

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

                $content->translations()->updateOrCreate(
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
                $content->translations()->updateOrCreate(
                    ['language_id' => $language->id],
                    [
                        'title' => $feature['title'],
                        'content' => sprintf('<p>%s</p>', $feature['content']),
                    ],
                );
            }

            $this->createMedia($content);

            $element->assets()->create([
                'pageable_id' => $page->id,
                'pageable_type' => $page->getMorphClass(),
                'container' => $container,
                'occurrence' => $occurrence,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->id,
            ]);
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
                'view_file' => 'capell-layout-builder::components.element.asset.features',
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
                'view_file' => 'capell-layout-builder::components.element.asset.blocks',
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
                $content->translations()->create([
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
                $section->translations()->firstOrCreate(
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
                $section->translations()->firstOrCreate(
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
                $section->translations()->firstOrCreate(
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
                $section->translations()->firstOrCreate(
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
                $section->translations()->firstOrCreate(
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
                $section->translations()->firstOrCreate(
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
                $section->translations()->firstOrCreate(
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
                $section->translations()->firstOrCreate(
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

    public function createApHeroBannerElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::HeroBanner)
            ?? $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
                ->firstWhere('key', ElementTypeEnum::Default);

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'ap-hero-banner'], [
            'name' => 'AP Hero Banner',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'primary_button_text' => 'Get Started',
                'primary_button_url' => '/docs/installation',
                'secondary_button_text' => 'View on GitHub',
                'secondary_button_url' => 'https://github.com/capell-app/capell',
                'margin' => ['none'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Architecture-Grade CMS',
                    'content' => '<p>Build, ship, and scale content-driven platform-builder with precision and zero compromise.</p>',
                ],
            );
        }

        return $element;
    }

    public function createApCardGridElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::CardGrid)
            ?? $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
                ->firstWhere('key', ElementTypeEnum::Default);

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'ap-card-grid'], [
            'name' => 'AP Card Grid',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'columns' => 3,
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'AP Card Grid'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $cards = [
            ['icon' => '⚡', 'title' => 'Static-first Architecture', 'description' => 'Zero PHP on page load. Every request served from Nginx-cached HTML.', 'link_text' => 'Learn More', 'link_url' => '/docs/caching'],
            ['icon' => '🌐', 'title' => 'Multi-site Support', 'description' => 'One installation, unlimited sites with shared or isolated content pools.', 'link_text' => 'Learn More', 'link_url' => '/docs/multi-site'],
            ['icon' => '🎨', 'title' => 'Visual Layout Builder', 'description' => 'Drag-and-drop elements with Livewire-powered live preview in Filament.', 'link_text' => 'Learn More', 'link_url' => '/docs/layout-builder'],
        ];

        foreach ($cards as $card) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $card['title']], [
                'meta' => [
                    'icon' => $card['icon'],
                    'link_text' => $card['link_text'],
                    'link_url' => $card['link_url'],
                ],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $section->translations()->firstOrCreate(
                    ['language_id' => $language->id],
                    ['title' => $card['title'], 'content' => sprintf('<p>%s</p>', $card['description'])],
                );
            }

            $element->assets()->firstOrCreate([
                'asset_id' => $section->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $element;
    }

    public function createApFeatureListElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::FeatureList)
            ?? $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
                ->firstWhere('key', ElementTypeEnum::Default);

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'ap-feature-list'], [
            'name' => 'AP Feature List',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'layout' => 'vertical',
                'margin' => ['lg'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                ['title' => 'AP Feature List'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
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

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $section->translations()->firstOrCreate(
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

    public function createFeatureListElement(): Element
    {
        $element = resolve(ElementCreator::class)->featuresElement();

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->firstOrCreate(
                ['language_id' => $language->id],
                ['title' => 'Features'],
            );
        }

        if ($element->assets()->exists()) {
            return $element;
        }

        $features = [
            ['icon' => 'heroicon-o-light-bulb', 'title' => 'Innovative Solutions', 'description' => 'We leverage cutting-edge technology to create innovative solutions that drive success.'],
            ['icon' => 'heroicon-o-academic-cap', 'title' => 'Deep Expertise', 'description' => 'Our team brings deep industry knowledge and experience to every project.'],
            ['icon' => 'heroicon-o-user-group', 'title' => 'Client-Centric Approach', 'description' => "We prioritize our clients' needs and work collaboratively to achieve their goals."],
            ['icon' => 'heroicon-o-chart-bar', 'title' => 'Measurable Results', 'description' => 'We focus on delivering measurable results that drive growth and success.'],
            ['icon' => 'heroicon-o-sparkles', 'title' => 'Sustainable Practices', 'description' => 'We are committed to sustainable practices that benefit our clients and the environment.'],
            ['icon' => 'heroicon-o-globe-alt', 'title' => 'Global Reach', 'description' => 'Our global presence allows us to serve clients across diverse markets and industries.'],
        ];

        foreach ($features as $feature) {
            $section = $this->contentModel::query()->firstOrCreate(['name' => $feature['title']], [
                'meta' => ['icon' => $feature['icon']],
            ]);

            foreach (Site::getDefault()?->languages ?? [] as $language) {
                $section->translations()->firstOrCreate(
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

    public function createApCtaSectionElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::CTASection)
            ?? $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
                ->firstWhere('key', ElementTypeEnum::Default);

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'ap-cta-section'], [
            'name' => 'AP CTA Section',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'primary_button_text' => 'Get Started Free',
                'primary_button_url' => '/docs/installation',
                'secondary_button_text' => 'View on GitHub',
                'secondary_button_url' => 'https://github.com/capell-app/capell',
                'margin' => ['none'],
            ],
        ]);

        foreach (Site::getDefault()?->languages ?? [] as $language) {
            $element->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Ready to build with precision?',
                    'content' => '<p>Join the growing community of developers shipping content platform-builder on Capell.</p>',
                ],
            );
        }

        return $element;
    }

    public function createApImageGalleryElement(): Element
    {
        $elementType = $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
            ->firstWhere('key', ElementTypeEnum::ImageGallery)
            ?? $this->typeModel::query()->where('type', LayoutTypeEnum::Element)
                ->firstWhere('key', ElementTypeEnum::Default);

        $element = $this->elementModel::query()->firstOrCreate(['key' => 'ap-image-gallery'], [
            'name' => 'AP Image Gallery',
            'blueprint_id' => $elementType->id,
            'meta' => [
                'layout' => 'grid',
                'columns' => 3,
                'lightbox' => true,
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

    public function addSplitTwoBackgroundMedia(Layout $layout): void
    {
        if ($layout->getMedia('split-two-background')->isNotEmpty()) {
            return;
        }

        $this->createMedia($layout, collection: 'split-two-background');
    }

    protected function navigationPageItems(Collection $siteTree, Language $language): array
    {
        $items = [];

        foreach ($siteTree as $page) {
            $items[(string) Str::uuid()] = [
                'label' => $this->getPageNavigationLabel($page, $language),
                'type' => 'page',
                'data' => [
                    'pageable_id' => $page->id,
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => $page->relationLoaded('children') ? $this->navigationPageItems($page->children, $language) : [],
            ];
        }

        return $items;
    }

    protected function getPageNavigationLabel(Page $page, Language $language): string
    {
        $navigationCreator = NavigationCreator::class;

        if (CapellCore::isPackageInstalled(self::NavigationPackage) && class_exists($navigationCreator) && method_exists($navigationCreator, 'getPageNavigationLabel')) {
            return (string) $navigationCreator::getPageNavigationLabel($page, $language);
        }

        return $page->translation?->title ?? $page->name;
    }

    private function createFeatures(Site $site): Collection
    {
        $features = [
            [
                'icon' => 'heroicon-o-light-bulb',
                'title' => 'Innovative Solutions',
                'content' => '<p>We leverage cutting-edge technology to create innovative solutions that drive success.</p>',
            ],
            [
                'icon' => 'heroicon-o-academic-cap',
                'title' => 'Expertise',
                'content' => '<p>Our team of experts brings deep industry knowledge and experience to every project.</p>',
            ],
            [
                'icon' => 'heroicon-o-user-group',
                'title' => 'Client-Centric Approach',
                'content' => "<p>We prioritize our clients' needs and work collaboratively to achieve their goals.</p>",
            ],
            [
                'icon' => 'heroicon-o-chart-bar',
                'title' => 'Measurable Results',
                'content' => '<p>We focus on delivering measurable results that drive growth and success.</p>',
            ],
            [
                'icon' => 'heroicon-o-sparkles',
                'title' => 'Sustainable Practices',
                'content' => '<p>We are committed to sustainable practices that benefit our clients and the environment.</p>',
            ],
            [
                'icon' => 'heroicon-o-shield-check',
                'title' => 'Lockdown',
                'content' => '<p>Lock down the public frontend during an incident while keeping break-glass admin access and preserving the live static page cache for recovery.</p>',
            ],
            [
                'icon' => 'heroicon-o-globe-alt',
                'title' => 'Global Reach',
                'content' => '<p>Our global presence allows us to serve clients across diverse markets and industries.</p>',
            ],
        ];

        $layout = Layout::query()->default()->first();

        throw_unless($layout instanceof Layout, Exception::class, 'Default layout not found');

        $parentPage = Page::query()->firstOrNew([
            'site_id' => $site->id,
            'layout_id' => $layout->id,
            'name' => 'Features',
        ]);

        $parentPage->save();

        $site->languages->each(function (Language $language) use ($parentPage): void {
            $parentPage->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => $parentPage->name,
            ]);
        });

        $contentFeatures = new Collection;

        foreach ($features as $feature) {
            $page = Page::query()->firstOrNew([
                'site_id' => $site->id,
                'name' => $feature['title'],
            ]);

            $page->fill([
                'parent_id' => $parentPage->id,
                'meta' => [
                    'icon' => $feature['icon'],
                ],
            ]);

            $page->save();

            $this->createMedia($page);

            $content = $this->contentModel::query()->updateOrCreate([
                'name' => $feature['title'],
            ], [
                'meta' => [
                    'icon' => $feature['icon'],
                    'pageable_id' => $page->id,
                    'pageable_type' => $page->getMorphClass(),
                ],
            ]);

            $this->createMedia($content);

            $contentFeatures->push($content);

            $site->languages->each(function (Language $language) use ($page, $content, $feature): void {
                $page->translations()->firstOrCreate([
                    'language_id' => $language->id,
                ], [
                    'title' => $feature['title'],
                    'content' => $feature['content'],
                ]);

                $content->translations()->firstOrCreate([
                    'language_id' => $language->id,
                ], [
                    'title' => $feature['title'],
                    'content' => $feature['content'],
                ]);
            });
        }

        return $contentFeatures;
    }

    private function createTestimonials(Collection $languages): Collection
    {
        $testimonialContent = $this->contentModel::query()->firstOrCreate([
            'name' => 'Testimonials',
        ], [
            'meta' => [
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
        ]);

        $this->createMedia($testimonialContent);

        $testimonials = [
            [
                'name' => 'John Doe',
                'position' => 'CEO of Example Corp',
                'content' => 'Capell has transformed our business with their innovative solutions and exceptional service.',
            ],
            [
                'name' => 'Jane Smith',
                'position' => 'CTO of Tech Innovations',
                'content' => 'The team at Capell is incredibly knowledgeable and always goes the extra mile for us.',
            ],
            [
                'name' => 'Jeff Wilson',
                'position' => 'Marketing Director at Creative Agency',
                'content' => 'We have seen significant growth since partnering with Capell. Their expertise is unmatched.',
            ],
        ];

        $testimonialsCollection = new Collection;

        $testimonialType = Blueprint::query()->updateOrCreate([
            'key' => 'testimonial',
            'type' => 'section',
        ], [
            'name' => 'Testimonial',
            'admin' => [
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'configurator' => 'testimonial-section',
            ],
        ]);

        foreach ($testimonials as $testimonial) {
            $content = $this->contentModel::query()->firstOrCreate([
                'name' => $testimonial['name'],
                'parent_id' => $testimonialContent->id,
                'blueprint_id' => $testimonialType->id,
            ], [
                'meta' => [
                    'position' => $testimonial['position'],
                ],
            ]);

            $this->createMedia($content);

            $content->translations()->createMany(
                $languages
                    ->reject(fn (Language $language): bool => $content->translations->contains('language_id', $language->id))
                    ->map(fn (Language $language): array => [
                        'language_id' => $language->id,
                        'title' => $testimonial['name'],
                        'content' => sprintf('<p>%s</p>', $testimonial['content']),
                    ])
                    ->all(),
            );

            $testimonialsCollection->push($content);
        }

        return $testimonialsCollection;
    }

    private function createTeamMembers(Collection $languages): Collection
    {
        $teamMembers = [
            [
                'name' => 'Alice Johnson',
                'position' => 'CEO',
                'bio' => '<p>Alice is the visionary behind our success, leading the team with passion and expertise.</p>',
            ],
            [
                'name' => 'Charlie Brown',
                'position' => 'CFO',
                'bio' => '<p>Charlie manages our finances with precision, ensuring sustainable growth and stability.</p>',
            ],
            [
                'name' => 'Fiona Green',
                'position' => 'Head of HR',
                'bio' => "<p>Fiona is dedicated to building a strong team culture and supporting our employees' growth.</p>",
            ],
            [
                'name' => 'George White',
                'position' => 'Lead Designer',
                'bio' => '<p>George brings creativity and innovation to our design projects, making them visually stunning.</p>',
            ],
            [
                'name' => 'Hannah Blue',
                'position' => 'Senior Developer',
                'bio' => '<p>Hannah is a coding wizard, turning complex problems into elegant solutions.</p>',
            ],
            [
                'name' => 'Ian Black',
                'position' => 'Project Manager',
                'bio' => '<p>Ian keeps our projects on track, ensuring timely delivery and client satisfaction.</p>',
            ],
            [
                'name' => 'Julia Red',
                'position' => 'Content Strategist',
                'bio' => '<p>Julia crafts compelling content strategies that engage and inform our audience.</p>',
            ],
            [
                'name' => 'Kevin Yellow',
                'position' => 'Data Analyst',
                'bio' => '<p>Kevin turns data into insights, helping us make informed decisions for our clients.</p>',
            ],
            [
                'name' => 'Laura Purple',
                'position' => 'Customer Success Manager',
                'bio' => '<p>Laura ensures our clients are happy and successful, building lasting relationships.</p>',
            ],
            [
                'name' => 'Mike Orange',
                'position' => 'Sales Director',
                'bio' => '<p>Mike drives our sales strategy, helping us reach new heights in revenue.</p>',
            ],
            [
                'name' => 'Nina Pink',
                'position' => 'UX Researcher',
                'bio' => '<p>Nina conducts research to understand user needs, shaping our products for better usability.</p>',
            ],
            [
                'name' => 'Oscar Gray',
                'position' => 'IT Support Specialist',
                'bio' => '<p>Oscar keeps our systems running smoothly, providing technical support to our team.</p>',
            ],
            [
                'name' => 'Quentin Silver',
                'position' => 'Business Analyst',
                'bio' => '<p>Quentin analyzes market trends, helping us identify new opportunities for growth.</p>',
            ],
            [
                'name' => 'Sam White',
                'position' => 'Quality Assurance Specialist',
                'bio' => '<p>Sam ensures our products meet the highest quality standards before they reach our clients.</p>',
            ],
            [
                'name' => 'Victor Blue',
                'position' => 'Network Administrator',
                'bio' => '<p>Victor manages our network infrastructure, ensuring reliable connectivity for our team.</p>',
            ],
            [
                'name' => 'Zane Purple',
                'position' => 'Research Scientist',
                'bio' => '<p>Zane conducts research to develop innovative solutions that push the boundaries of technology.</p>',
            ],
        ];

        $teamContent = $this->contentModel::query()->firstOrNew([
            'name' => 'Team Members',
        ]);

        $meta = $teamContent->meta ?? [];
        $meta['icon'] = 'heroicon-o-users';
        $teamContent->meta = $meta;

        $teamContent->save();

        $teamMembersCollection = new Collection;

        foreach ($teamMembers as $member) {
            $content = $this->contentModel::query()->firstOrCreate([
                'name' => $member['name'],
                'parent_id' => $teamContent->id,
            ], [
                'meta' => [
                    'position' => $member['position'],
                ],
            ]);

            $this->createMedia($content);

            $content->translations()->createMany(
                $languages
                    ->reject(fn (Language $language): bool => $content->translations->contains('language_id', $language->id))
                    ->map(fn (Language $language): array => [
                        'language_id' => $language->id,
                        'title' => $member['name'],
                        'content' => $member['bio'],
                    ])
                    ->all(),
            );

            $teamMembersCollection->push($content);
        }

        return $teamMembersCollection;
    }

    private function createMedia(HasMedia $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = MediaCollectionEnum::Image): void
    {
        $collectionName = $collection instanceof BackedEnum ? $collection->value : $collection;

        // Build an optional filter to match existing media by inferred filename when a name is provided
        $filters = [];
        if (! in_array($name, [null, '', '0'], true)) {
            $base = pathinfo(Str::slug($name), PATHINFO_FILENAME);
            $filters = [
                fn (Media $media): bool => str($media->file_name)->contains($base),
            ];
        }

        if ($model->getMedia($collectionName, $filters)->isNotEmpty()) {
            return;
        }

        $demoCreator = $this->resolveDemoCreator();

        if ($demoCreator === null || ! method_exists($demoCreator, 'createMedia')) {
            return;
        }

        $demoCreator->createMedia($model, $name, $type, $collection);
    }

    private function createElementMedia(Element $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = MediaCollectionEnum::Image): Media
    {
        // Normalize input name and derive extension if provided
        $inputName = in_array($name, [null, '', '0'], true) ? null : $name;
        $inputExt = $inputName !== null ? pathinfo($inputName, PATHINFO_EXTENSION) : '';

        // Decide base demo path and defaults per type
        $isVideo = $type === 'video';
        $demoPath = $this->getDemoResourcePath($isVideo ? 'video' : 'img');

        // Determine filename (without extension) and extension
        $filenameBase = $inputName !== null
            ? pathinfo($inputName, PATHINFO_FILENAME)
            : ($isVideo ? 'SampleVideo_1280x720_1mb' : null);

        $ext = $inputExt !== ''
            ? strtolower($inputExt)
            : ($isVideo ? 'mp4' : 'jpg');

        // Use video collection explicitly
        if ($isVideo) {
            $collection = MediaCollectionEnum::Video;
        }

        // Build the candidate file path
        $demoFile = $filenameBase !== null ? sprintf('%s/%s.%s', $demoPath, $filenameBase, $ext) : '';

        // Fallback handling: if no filename or file missing, choose a random demo image for images
        if ($filenameBase === null || $demoFile === '' || ! file_exists($demoFile)) {
            if ($isVideo) {
                // For videos, keep original demo path and defaults; we'll still attach a poster image below
                // Attempt video default file first
                $filenameBase = 'SampleVideo_1280x720_1mb';
                $ext = $inputExt !== '' ? strtolower($inputExt) : 'mp4';
            } else {
                // For images: pick a random demo image and set explicit jpg (demo images are jpg)
                $demoPath = $this->getDemoResourcePath('img');
                $filenameBase = $this->getRandomDemoImage($demoPath, 'jpg');
                $ext = 'jpg';
            }

            $demoFile = sprintf('%s/%s.%s', $demoPath, $filenameBase, $ext);
        }

        // Create content and link via ElementAsset
        $content = $this->contentModel::create([
            'name' => str($filenameBase)->title(),
        ]);

        $model->assets()->create([
            'asset_id' => $content->getKey(),
            'asset_type' => resolve($this->contentModel)->getMorphClass(),
        ]);

        // Attach primary media
        $image = null;
        if (! $isVideo) {
            $image = Image::load($demoFile);
        }

        $media = $content->addMedia($demoFile)
            ->preservingOriginal()
            ->withCustomProperties([
                ...($image instanceof Image ? ['width' => $image->getWidth(), 'height' => $image->getHeight()] : []),
            ])
            ->toMediaCollection($collection instanceof BackedEnum ? $collection->value : $collection);

        // For videos, also attach a jpg poster image
        if (! $isVideo) {
            return $media;
        }

        $posterPath = $this->getDemoResourcePath('img');
        $posterBase = $this->getRandomDemoImage($posterPath);
        $posterFile = sprintf('%s/%s.jpg', $posterPath, $posterBase);

        $posterImage = Image::load($posterFile);

        return $content->addMedia($posterFile)
            ->preservingOriginal()
            ->withCustomProperties([
                'width' => $posterImage->getWidth(),
                'height' => $posterImage->getHeight(),
            ])
            ->toMediaCollection(MediaCollectionEnum::Image->value);
    }

    private function getRandomDemoImage(string $demo_path, string $extension = 'jpg'): string
    {
        $demoCreator = $this->resolveDemoCreator();

        if ($demoCreator !== null && method_exists($demoCreator, 'getRandomDemoImage')) {
            return (string) $demoCreator->getRandomDemoImage($demo_path, $extension);
        }

        throw new RuntimeException('The demo kit package is required to create demo layout builder media.');
    }

    private function getDemoResourcePath(string $type): string
    {
        $demoCreator = self::DEMO_CREATOR;

        if (CapellCore::isPackageInstalled(self::DemoKitPackage) && class_exists($demoCreator) && method_exists($demoCreator, 'getDemoResourcePath')) {
            return $demoCreator::getDemoResourcePath($type);
        }

        throw new RuntimeException('The demo kit package is required to create demo layout builder media.');
    }

    private function resolveDemoCreator(): ?object
    {
        if (! CapellCore::isPackageInstalled(self::DemoKitPackage) || ! class_exists(self::DEMO_CREATOR)) {
            return null;
        }

        return resolve(self::DEMO_CREATOR);
    }
}
