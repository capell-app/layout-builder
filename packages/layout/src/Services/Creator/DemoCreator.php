<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Admin\Services\Creator\DemoCreator as AdminDemoCreator;
use Capell\Admin\Services\Creator\NavigationCreator;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Filament\Resources\Contents\Schemas\Types\TestimonialContentSchema;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Image\Image;
use Spatie\MediaLibrary\HasMedia;

class DemoCreator
{
    /**
     * @var class-string<Content>
     */
    private readonly string $contentModel;

    /**
     * @var class-string<Widget>
     */
    private readonly string $widgetModel;

    /**
     * @var class-string<Type>
     */
    private readonly string $typeModel;

    /**
     * @var class-string<Page>
     */
    private readonly string $pageModel;

    /**
     * @var class-string<Models\Tag>
     */
    private readonly string $tagModel;

    public function __construct(
        protected readonly ?Model $user = null,
    ) {
        $this->contentModel = CapellCore::getModel(LayoutModelEnum::Content->name);
        $this->widgetModel = CapellCore::getModel(LayoutModelEnum::Widget->name);
        $this->typeModel = CapellCore::getModel(ModelEnum::Type);
        $this->pageModel = CapellCore::getModel(ModelEnum::Page);
        $this->tagModel = CapellCore::getModel(ModelEnum::Tag);
    }

    public function createContentWidget(Collection $languages): Widget
    {
        $siteId = Site::default()?->value('id');
        $widget = $this->widgetModel::firstOrCreate(['key' => 'example-content'], [
            'name' => 'Example Content',
            'meta' => [
                'size' => 'md',
                'margin' => '',
                'padding' => ['md'],
                'reverse_order' => true,
                'background_color' => 'light-gray',
                'actions' => [
                    [
                        'type' => 'page',
                        'page_id' => Page::where('site_id', $siteId)
                            ->whereHas(
                                'type',
                                /** @param Type $query */
                                fn (BuilderContract $query) => $query->listable()->enabled()->accessible()
                            )
                            ->inRandomOrder()
                            ->value('id'),
                        'site_id' => $siteId,
                    ],
                ],
            ],
        ]);

        $this->createMedia($widget);

        foreach ($languages as $language) {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Example Content',
                'content' => [
                    [
                        'type' => 'content',
                        'data' => [
                            'content' => config('capell-demo.contents')[$language->code],
                        ],
                    ],
                ],
            ]);
        }

        return $widget;
    }

    public function createSplitContentWidget(Collection $languages): Widget
    {
        $siteId = Site::default()?->value('id');

        $widget = $this->widgetModel::firstOrCreate(['key' => 'example-split-content'], [
            'name' => 'Example Split Content',
            'meta' => [
                'align' => 'center',
                'size' => 'md',
                'style' => 'column',
                'padding' => ['md'],
                'actions' => [
                    [
                        'type' => 'page',
                        'page_id' => Page::where('site_id', $siteId)
                            ->whereHas(
                                'type',
                                /** @param Type $query */
                                fn (BuilderContract $query) => $query->listable()->enabled()->accessible()
                            )
                            ->inRandomOrder()
                            ->value('id'),
                        'site_id' => $siteId,
                    ],
                ],
            ],
        ]);

        $this->createMedia($widget);

        foreach ($languages as $language) {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Example Content',
                'content' => [
                    [
                        'type' => 'content',
                        'data' => [
                            'content' => str(config('capell-demo.contents')[$language->code])->limit(200)->toString(),
                        ],
                    ],
                ],
            ]);
        }

        return $widget;
    }

    public function createBannerImageWidget(Collection $languages): Widget
    {
        $siteId = Site::default()?->value('id');
        $widget = $this->widgetModel::firstOrCreate(['key' => 'banner-full-width'], [
            'name' => 'Banner Full Width',
            'meta' => [
                'component' => 'capell-layout::widget.banner-image',
                'margin' => ['lg'],
                'actions' => [
                    [
                        'type' => 'page',
                        'page_id' => Page::where('site_id', $siteId)
                            ->whereHas(
                                'type',
                                /** @param Type $query */
                                fn (BuilderContract $query) => $query->listable()->enabled()->accessible()
                            )
                            ->inRandomOrder()
                            ->value('id'),
                        'site_id' => $siteId,
                    ],
                ],
            ],
        ]);

        $this->createMedia($widget);

        foreach ($languages as $language) {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Example Content',
                'content' => [
                    [
                        'type' => 'content',
                        'data' => [
                            'content' => config('capell-demo.contents')[$language->code],
                        ],
                    ],
                ],
            ]);
        }

        return $widget;
    }

    public function createGalleryWidget(): Widget
    {
        $widget = $this->widgetModel::where('key', 'gallery')->first();

        if ($widget->assets()->exists()) {
            return $widget;
        }

        for ($i = 1; $i <= 5; $i++) {
            $this->createMedia($widget);
        }

        return $widget;
    }

    public function createPageCardsWidget(Page $page, string $container = 'main', int $occurrence = 1): Widget
    {
        $widget = $this->widgetModel::firstWhere('key', 'pages-card');

        if (! $widget) {
            $widget = $this->widgetModel::create([
                'key' => 'pages-card',
                'name' => __('capell-admin::generic.pages_tile'),
                'type_id' => $this->typeModel::firstWhere('key', WidgetTypeEnum::Pages)->id,
                'meta' => [
                    'component' => WidgetComponentEnum::LivewirePages,
                    'columns' => 4,
                    'with_image' => true,
                    'with_summary' => true,
                    'with_link_text' => true,
                    'margin' => ['lg'],
                ],
            ]);
        }

        if (
            $widget->assets()
                ->where([
                    'page_id' => $page->id,
                    'container' => $container,
                    'occurrence' => $occurrence,
                ])
                ->exists()
        ) {
            return $widget;
        }

        $pages = $this->pageModel::query()
            ->whereHas('type', fn (BuilderContract $query) => $query->default())
            ->whereHas('image')
            ->where('site_id', $page->site_id)
            ->notHomePage()
            ->inRandomOrder()
            ->limit(3)
            ->pluck('id');

        if ($pages->isEmpty()) {
            throw new RuntimeException('No pages found to associate with the widget.');
        }

        $pages->each(fn ($related_page_id): WidgetAsset => $widget->assets()->create([
            'page_id' => $page->id,
            'asset_id' => $related_page_id,
            'asset_type' => app($this->pageModel)->getMorphClass(),
            'container' => $container,
            'occurrence' => $occurrence,
        ]));

        return $widget;
    }

    public function createFaqWidget(Collection $languages): Widget
    {
        $widgetType = $this->typeModel::query()->where('type', LayoutTypeEnum::Widget)
            ->firstWhere('key', 'assets');

        $widget = $this->widgetModel::firstOrCreate(['key' => 'faq'], [
            'key' => 'faq',
            'name' => __('capell-admin::generic.faq'),
            'type_id' => $widgetType->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => WidgetComponentEnum::AssetAccordion,
                'margin' => ['lg'],
                'align' => 'center',
            ],
            'admin' => [
                'asset_types' => [
                    AssetEnum::Content->value,
                ],
            ],
        ]);

        foreach ($languages as $language) {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => __('capell-admin::heading.faq'),
                'content' => '<p>You can find answers for commonly asked questions</p>',
            ]);
        }

        $contentType = $this->typeModel::query()
            ->where('type', LayoutTypeEnum::Content)
            ->default()
            ->first();

        $parentContent = $this->contentModel::firstOrCreate([
            'name' => 'FAQs',
            'type_id' => $contentType->id,
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

        $faqTag = $this->tagModel::findOrCreate('faq', 'content', $languages->first()->code);

        $languages->skip(1)
            ->each(fn (Language $language) => $this->tagModel::findOrCreate('faq', 'content', $language->code));

        for ($i = 1; $i <= 6; $i++) {
            $content = $this->contentModel::firstOrCreate([
                'name' => $questions['en'][$i],
                'parent_id' => $parentContent->id,
                'type_id' => $contentType->id,
            ]);

            $content->tags()->syncWithoutDetaching([$faqTag->id]);

            $widget->assets()->firstOrcreate([
                'asset_id' => $content->id,
                'asset_type' => app($this->contentModel)->getMorphClass(),
            ]);

            foreach ($languages as $language) {
                $desc_content = config('capell-demo.contents')[$language->code] ?? '';

                $content->translations()->firstOrCreate(['language_id' => $language->id], [
                    'title' => Str::title($questions[$language->code][$i]),
                    'content' => [
                        [
                            'type' => 'content',
                            'data' => [
                                'content' => $desc_content,
                            ],
                        ],
                    ],
                ]);
            }
        }

        return $widget;
    }

    public function createMediaCarouselWidget(): Widget
    {
        $widget = $this->widgetModel::where('key', 'media-carousel')->first();

        if ($widget->assets()->exists()) {
            return $widget;
        }

        for ($i = 1; $i <= 7; $i++) {
            $this->createMedia($widget);
        }

        $this->createMedia($widget, type: 'video');

        return $widget;
    }

    public function createArticlesCardWidget(Collection $languages, Page $page, int $occurrence = 1): Widget
    {
        $widget = $this->widgetModel::firstWhere('key', 'articles-card');

        if (! $widget) {
            $widget = $this->widgetModel::firstOrCreate([
                'key' => 'articles-card',
                'name' => __('capell-admin::generic.articles'),
                'type_id' => $this->typeModel::firstWhere('key', WidgetTypeEnum::Pages)->id,
                'meta' => [
                    'limit' => 10,
                    'with_image' => true,
                    'with_summary' => true,
                    'with_link_text' => true,
                    'spacing' => 'lg',
                    'padding' => ['t-lg'],
                ],
            ]);
        }

        foreach ($languages as $language) {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => __('Article - Cards'),
            ]);
        }

        return $widget;
    }

    public function createStaticNavigationWidget(Collection $languages, Site $site): Widget
    {
        /** @var class-string<Models\Navigation> $model */
        $model = CapellCore::getModel(ModelEnum::Navigation);

        // Create menu + items
        $name = 'Example Menu';
        $key = Str::slug($name);

        $pages = Page::where([
            'site_id' => $site->id,
        ])
            ->whereHas(
                'type',
                /** @param  Type  $query */
                fn (BuilderContract $query) => $query->where('type', 'page')
                    ->enabled()
                    ->listable()
                    ->accessible()
            )
            ->with([
                'children' => fn (BuilderContract $query) => $query->whereHas('type')->limit(2),
            ])
            ->limit(4)
            ->get();

        $model::updateOrCreate([
            'key' => $key,
            'site_id' => $site->id,
            'type_id' => $this->typeModel::navigationType()->default()->first()->id,
        ], [
            'name' => $name,
            'items' => $this->navigationPageItems($pages, $languages->first()),
        ]);

        // Create widget
        $widget = $this->widgetModel::firstOrCreate(['key' => 'example-navigation'], [
            'name' => __('Example Navigation'),
            'type_id' => $this->typeModel::firstWhere(['key' => 'navigation', 'type' => LayoutTypeEnum::Widget])->id,
            'meta' => [
                'navigation' => $key,
                'margin' => ['lg'],
            ],
        ]);

        foreach ($languages as $language) {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Example Navigation',
            ]);
        }

        return $widget;
    }

    public function createWidgetAssets(Widget $widget, Page $page, string $container, int $occurrence = 1): void
    {
        if ($widget->assets()->exists()) {
            return;
        }

        $features = [
            [
                'title' => 'Welcome to Our Platform',
                'content' => '<p>Welcome to our website! We are glad to have you here.</p>',
            ],
            [
                'title' => 'Get Started Today',
                'content' => '<p>Take the first step towards your goals. Join us today!</p>',
            ],
            [
                'title' => 'Discover Our Projects',
                'content' => '<p>Check out our latest projects and initiatives.</p>',
            ],
            [
                'title' => 'Watch Our Story',
                'content' => '<p>Watch our introduction video to learn more about us.</p>',
            ],
        ];

        foreach ($features as $feature) {
            $content = Content::factory()
                ->site($page->site)
                ->withTranslations($page->site->languages, ['content' => sprintf('<p>%s</p>', $feature['title'])])
                ->state([
                    'meta' => fn ($attributes): array => array_merge_recursive((array) $attributes['meta'], [
                        'actions' => [
                            [
                                'type' => 'page',
                                'page_id' => Page::where('site_id', $page->site->id)
                                    ->whereHas(
                                        'type',
                                        /** @param Type $query */
                                        fn (BuilderContract $query) => $query->listable()->enabled()->accessible()
                                    )
                                    ->inRandomOrder()
                                    ->value('id'),
                                'site_id' => $page->site->id,
                            ],
                            [
                                'type' => 'page',
                                'page_id' => Page::where('site_id', $page->site->id)
                                    ->whereHas(
                                        'type',
                                        /** @param Type $query */
                                        fn (BuilderContract $query) => $query->listable()->enabled()->accessible()
                                    )
                                    ->inRandomOrder()
                                    ->value('id'),
                                'site_id' => $page->site->id,
                                'color' => 'secondary',
                            ],
                            [
                                'type' => 'url',
                                'url' => 'https://example.com',
                                'label' => 'External',
                                'color' => 'default',
                            ],
                        ],
                    ]),
                ])
                ->create();

            $this->createMedia($content);

            $widget->assets()->create([
                'page_id' => $page->id,
                'container' => $container,
                'occurrence' => $occurrence,
                'asset_type' => app($this->contentModel)->getMorphClass(),
                'asset_id' => $content->id,
            ]);
        }
    }

    public function createClientLogosWidget(Collection $languages): Widget
    {
        $widget = Widget::firstOrCreate([
            'key' => 'client-logos',
        ], [
            'name' => 'Client Logos',
            'type_id' => $this->typeModel::firstWhere(['key' => WidgetTypeEnum::Assets, 'type' => LayoutTypeEnum::Widget])->id,
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

        if ($widget->assets()->exists()) {
            return $widget;
        }

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => 'Client Logos',
                'content' => '<p>We are proud to work with these amazing partners.</p>',
            ]);
        });

        for ($i = 1; $i <= 12; $i++) {
            $this->createMedia($widget);
        }

        return $widget;
    }

    public function createBusinessFeaturesWidget(Site $site): Widget
    {
        $widget = Widget::firstOrCreate([
            'key' => 'business-features',
        ], [
            'name' => 'Business Features',
            'type_id' => $this->typeModel::firstWhere(['key' => WidgetTypeEnum::Contents, 'type' => LayoutTypeEnum::Widget])->id,
            'meta' => [
                'align' => 'center',
                'margin' => ['lg'],
                'view_file' => 'capell-layout::components.widget.assets.features',
            ],
        ]);

        $this->createMedia($widget);

        if ($widget->assets()->exists()) {
            return $widget;
        }

        $title = 'Fundamental Capabilities That Set Us Apart';
        $content = '<p>We combine innovation, efficiency, and deep expertise to deliver exceptional results. Our adaptable, client-focused approach ensures measurable value and lasting impact.</p>';

        $site->languages->each(function (Language $language) use ($widget, $title, $content): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => $title,
                'content' => $content,
            ]);
        });

        $features = $this->createFeatures($site);

        $features->each(function (Content $content) use ($widget): void {
            if ($widget->assets()->where('asset_id', $content->id)->exists()) {
                return;
            }

            $widget->assets()->create([
                'asset_type' => app($this->contentModel)->getMorphClass(),
                'asset_id' => $content->id,
            ]);
        });

        return $widget;
    }

    public function createBannersWidget(): Widget
    {
        $widget = $this->widgetModel::firstOrCreate(['key' => 'banners'], [
            'name' => 'Banner Showcase',
            'type_id' => $this->typeModel::firstWhere(['key' => WidgetTypeEnum::Contents, 'type' => LayoutTypeEnum::Widget])->id,
            'meta' => [
                'align' => 'center',
                'background_overlay' => true,
                'view_file' => 'capell-layout::components.widget.assets.banners',
            ],
        ]);

        if ($widget->assets()->exists()) {
            return $widget;
        }

        $site = Site::default()->first();

        $features = $this->createFeatures($site);

        $features->each(function (Content $content) use ($widget): void {
            if ($widget->assets()->where('asset_id', $content->id)->exists()) {
                return;
            }

            $widget->assets()->create([
                'asset_type' => app($this->contentModel)->getMorphClass(),
                'asset_id' => $content->id,
            ]);
        });

        return $widget;
    }

    public function createTestimonialsWidget(Collection $languages): Widget
    {
        $widget = $this->widgetModel::firstOrCreate(['key' => 'testimonials'], [
            'name' => 'Testimonials',
            'type_id' => $this->typeModel::firstWhere(['key' => WidgetTypeEnum::Contents, 'type' => LayoutTypeEnum::Widget])->id,
            'meta' => [
                'align' => 'center',
                'background_overlay' => true,
                'background_color' => 'dark-gray',
                'view_file' => 'capell-layout::components.widget.assets.testimonials',
            ],
        ]);

        $this->createMedia($widget, collection: 'backgroundImage');

        if ($widget->assets()->exists()) {
            return $widget;
        }

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'What Our Clients Say',
            ]);
        });

        $testimonials = $this->createTestimonials($languages);

        $testimonials->each(function (Content $content) use ($widget): void {
            if ($widget->assets()->where('asset_id', $content->id)->exists()) {
                return;
            }

            $widget->assets()->create([
                'asset_type' => app($this->contentModel)->getMorphClass(),
                'asset_id' => $content->id,
            ]);
        });

        return $widget;
    }

    public function createStatisticsWidget(): Widget
    {
        $widget = $this->widgetModel::firstOrCreate(['key' => 'statistics'], [
            'name' => 'Statistics',
            'type_id' => $this->typeModel::firstWhere(['key' => WidgetTypeEnum::Assets, 'type' => LayoutTypeEnum::Widget])->id,
            'meta' => [
                'component_item' => 'capell-layout::content.block',
                'view_file' => 'capell-layout::components.widget.assets.blocks',
                'spacing' => 'none',
                'columns' => 'auto',
                'margin' => '',
            ],
            'admin' => [
                'icon' => 'heroicon-o-chart-bar',
            ],
        ]);

        if ($widget->assets()->exists()) {
            return $widget;
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

        $site = Site::default()->first();

        foreach ($statistics as $statistic) {
            $content = Content::factory()
                ->recycle($site)
                ->withTranslations($site->languages, [
                    'title' => $statistic['title'],
                    'content' => sprintf('<p>%s</p>', $statistic['value']),
                ])
                ->state([
                    'name' => $statistic['title'],
                    'meta' => [
                        'icon' => $statistic['icon'],
                        'color' => $statistic['color'],
                    ],
                ])
                ->create();

            $widget->assets()->firstOrCreate([
                'asset_id' => $content->id,
                'asset_type' => app($this->contentModel)->getMorphClass(),
            ]);
        }

        return $widget;
    }

    public function createTeamPortfolioWidget(Collection $languages): Widget
    {
        $widget = $this->widgetModel::firstOrCreate(['key' => 'team-portfolio'], [
            'name' => 'Team Portfolio',
            'type_id' => $this->typeModel::query()
                ->where([
                    'key' => WidgetTypeEnum::Contents,
                    'type' => LayoutTypeEnum::Widget,
                ])
                ->value('id'),
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
                'carousel_auto' => true,
                'carousel_auto_delay' => 50000,
                'component_item' => 'capell-layout::content.team-member',
            ],
        ]);

        $languages->each(function (Language $language) use ($widget): void {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Meet Our Team',
                'content' => '<p>Discover the talented individuals behind our success.</p>',
            ]);
        });

        $teamMembers = $this->createTeamMembers($languages);

        $teamMembers->each(function (Content $content) use ($widget): void {
            if ($widget->assets()->where('asset_id', $content->id)->exists()) {
                return;
            }

            $widget->assets()->create([
                'asset_type' => app($this->contentModel)->getMorphClass(),
                'asset_id' => $content->id,
            ]);
        });

        return $widget;
    }

    protected function navigationPageItems(\Illuminate\Support\Collection $siteTree, Language $language): array
    {
        $items = [];

        foreach ($siteTree as $page) {
            $items[(string) Str::uuid()] = [
                'label' => NavigationCreator::getPageNavigationLabel($page, $language),
                'type' => 'page',
                'data' => [
                    'page_id' => $page->id,
                ],
                'children' => $page->relationLoaded('children') ? $this->navigationPageItems($page->children, $language) : [],
            ];
        }

        return $items;
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
                'icon' => 'heroicon-o-globe-alt',
                'title' => 'Global Reach',
                'content' => '<p>Our global presence allows us to serve clients across diverse markets and industries.</p>',
            ],
        ];

        $parentPage = Page::updateOrCreate([
            'site_id' => $site->id,
            'name' => 'Features',
        ], [
            'meta' => [
                'author_id' => $this->user?->id,
            ],
        ]);

        $site->languages->each(function (Language $language) use ($parentPage): void {
            $parentPage->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => $parentPage->name,
            ]);
        });

        $contentFeatures = new Collection;

        foreach ($features as $feature) {
            $page = Page::updateOrCreate([
                'site_id' => $site->id,
                'name' => $feature['title'],
            ], [
                'parent_id' => $parentPage->id,
                'meta' => [
                    'icon' => $feature['icon'],
                    'author_id' => $this->user?->id,
                ],
            ]);

            $this->createMedia($page);

            $content = Content::updateOrCreate([
                'name' => $feature['title'],
            ], [
                'meta' => [
                    'icon' => $feature['icon'],
                    'page_id' => $page->id,
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
        $testimonialContent = Content::create([
            'name' => 'Testimonials',
            'meta' => [
                'icon' => 'heroicon-o-chat-bubble-left-right',
            ],
        ]);

        $this->createMedia($testimonialContent);

        $testimonials = [
            [
                'name' => 'John Doe',
                'position' => 'CEO of Example Corp',
                'content' => '<p>Capell has transformed our business with their innovative solutions and exceptional service.</p>',
            ],
            [
                'name' => 'Jane Smith',
                'position' => 'CTO of Tech Innovations',
                'content' => '<p>The team at Capell is incredibly knowledgeable and always goes the extra mile for us.</p>',
            ],
            [
                'name' => 'Alice Johnson',
                'position' => 'Marketing Director at Creative Agency',
                'content' => '<p>We have seen significant growth since partnering with Capell. Their expertise is unmatched.</p>',
            ],
        ];

        $testimonialsCollection = new Collection;

        $testimonialType = Type::updateOrCreate([
            'key' => 'testimonial',
            'type' => LayoutTypeEnum::Content,
        ], [
            'name' => 'Testimonial',
            'admin' => [
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'schema' => TestimonialContentSchema::getKey(),
            ],
        ]);

        foreach ($testimonials as $testimonial) {
            $content = Content::firstOrCreate([
                'name' => $testimonial['name'],
                'parent_id' => $testimonialContent->id,
                'type_id' => $testimonialType->id,
            ], [
                'meta' => [
                    'position' => $testimonial['position'],
                ],
            ]);

            $this->createMedia($content);

            $content->translations()->createMany(
                $languages->map(fn (Language $language): array => [
                    'language_id' => $language->id,
                    'title' => $testimonial['name'],
                    'content' => sprintf('<p>%s</p>', $testimonial['content']),
                ])
                    ->all()
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

        $teamContent = Content::firstOrNew([
            'name' => 'Team Members',
        ]);

        $meta = $teamContent->meta ?? [];
        $meta['icon'] = 'heroicon-o-users';
        $teamContent->meta = $meta;

        $teamContent->save();

        $teamMembersCollection = new Collection;

        foreach ($teamMembers as $member) {
            $content = Content::firstOrCreate([
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
                    ->filter(fn (Language $language): bool => ! $content->translations->contains('language_id', $language->id))
                    ->map(fn (Language $language): array => [
                        'language_id' => $language->id,
                        'title' => $member['name'],
                        'content' => $member['bio'],
                    ])
                    ->all()
            );

            $teamMembersCollection->push($content);
        }

        return $teamMembersCollection;
    }

    private function createMedia(HasMedia $model, ?string $name = null, string $type = 'image', string $collection = MediaCollectionEnum::Image->value): void
    {
        if ($model instanceof Widget) {
            $this->createWidgetMedia($model, $name, $type, $collection);

            return;
        }

        app(AdminDemoCreator::class)->createMedia($model, $name, $type, $collection);
    }

    private function createWidgetMedia(HasMedia $model, ?string $name = null, string $type = 'image', string $collection = MediaCollectionEnum::Image->value): void
    {
        if ($type === 'video') {
            $ext = 'mp4';
            $demo_path = AdminDemoCreator::getDemoResourcePath('video');
            $filename = $name ?? 'SampleVideo_1280x720_1mb';
            $collection = MediaCollectionEnum::Video->value;
        } else {
            $ext = 'jpg';
            $demo_path = AdminDemoCreator::getDemoResourcePath('img');
            $filename = $name !== null && $name !== '' && $name !== '0' ? Str::slug($name) : null;
        }

        if ($filename !== null) {
            $filename = pathinfo($filename, PATHINFO_FILENAME);
        }

        $demo_file = sprintf('%s/%s.%s', $demo_path, $filename, $ext);

        if ($filename === '' || $filename === '0' || $filename === [] || $filename === null || ! file_exists($demo_file)) {
            $demo_path = AdminDemoCreator::getDemoResourcePath('img');

            $filename = $this->getRandomDemoImage($demo_path);

            $demo_file = sprintf('%s/%s.%s', $demo_path, $filename, $ext);
        }

        // Create content and add via WidgetAsset
        $content = Content::create([
            'name' => str($filename)->title(),
        ]);

        $model->assets()->create([
            'asset_id' => $content->getKey(),
            'asset_type' => app(Content::class)->getMorphClass(),
        ]);

        $image = null;
        if ($type !== 'video') {
            $image = Image::load($demo_file);
        }

        $media = $content->addMedia($demo_file)
            ->preservingOriginal()
            ->withCustomProperties([
                ...(
                    $image instanceof Image
                ? ['width' => $image->getWidth(), 'height' => $image->getHeight()]
                : []
                ),
            ])
            ->toMediaCollection($collection);

        if ($type === 'video') {
            $demo_path = AdminDemoCreator::getDemoResourcePath('img');

            $filename = $this->getRandomDemoImage($demo_path);

            $demo_file = sprintf('%s/%s.%s', $demo_path, $filename, 'jpg');

            $image = Image::load($demo_file);

            $content->addMedia($demo_file)
                ->preservingOriginal()
                ->withCustomProperties([
                    'width' => $image->getWidth(),
                    'height' => $image->getHeight(),
                ])
                ->toMediaCollection(MediaCollectionEnum::Image->value);
        }
    }

    private function getRandomDemoImage(string $demo_path): string
    {
        return app(AdminDemoCreator::class)->getRandomDemoImage($demo_path);
    }
}
