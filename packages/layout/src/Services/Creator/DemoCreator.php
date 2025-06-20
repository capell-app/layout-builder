<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Admin\Services\Creator\NavigationCreator;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Core\Models\Page;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Enums\WidgetTypeEnum;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

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
     * @var class-string<Models\Type>
     */
    private readonly string $typeModel;

    /**
     * @var class-string<Models\Media>
     */
    private readonly string $mediaModel;

    /**
     * @var class-string<Page>
     */
    private readonly string $pageModel;

    /**
     * @var class-string<Models\Tag>
     */
    private readonly string $tagModel;

    public function __construct()
    {
        $this->contentModel = CapellCore::getModel(LayoutModelEnum::Content->name);
        $this->widgetModel = CapellCore::getModel(LayoutModelEnum::Widget->name);
        $this->typeModel = CapellCore::getModel(ModelEnum::Type);
        $this->mediaModel = CapellCore::getModel(ModelEnum::Media);
        $this->pageModel = CapellCore::getModel(ModelEnum::Page);
        $this->tagModel = CapellCore::getModel(ModelEnum::Tag);
    }

    public function createStaticWidget(Collection $languages): Widget
    {
        $widget = $this->widgetModel::firstOrCreate(['key' => 'example-content'], [
            'name' => 'Example Static Contents',
            'type_id' => $this->typeModel::query()->where('type', LayoutTypeEnum::Widget)->default()->first()->id,
            'meta' => [
                'size' => 'sm',
                'margin' => ['lg'],
            ],
        ]);

        foreach ($languages as $language) {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Example Content',
                'contents' => [
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

    public function createGalleryWidget($occurrence = 1): Widget
    {
        $widget = $this->widgetModel::where('key', 'gallery')->first();

        if ($widget->assets()->exists()) {
            return $widget;
        }

        $media = $this->mediaModel::query()
            ->where('type', 'LIKE', 'image/%')
            ->inRandomOrder()
            ->limit(5)
            ->pluck('uuid');

        foreach ($media as $mediaUuid) {
            $widget->assets()->firstOrcreate([
                'asset_id' => $mediaUuid,
                'asset_type' => app($this->mediaModel)->getMorphClass(),
            ]);
        }

        return $widget;
    }

    public function createPageCardsWidget(Collection $languages, Page $page, int $occurrence = 1, ?string $title = null): Widget
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
                'admin' => [
                    'notes' => 'Displays a list of pages in a tile layout',
                ],
            ]);
        }

        if ($title) {
            foreach ($languages as $language) {
                $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                    'title' => $title,
                ]);
            }
        }

        $this->pageModel::query()
            ->whereHas('type', fn (BuilderContract $query) => $query->default())
            ->where('site_id', $page->site_id)
            ->hasImage()
            ->isNotHomePage()
            ->inRandomOrder()
            ->limit(3)
            ->pluck('uuid')
            ->each(function ($related_page_uuid) use ($widget, $page, $occurrence): void {
                $widget->assets()->firstOrcreate([
                    'page_id' => $page->id,
                    'asset_id' => $related_page_uuid,
                    'asset_type' => app($this->pageModel)->getMorphClass(),
                    'container' => 'main',
                    'occurrence' => $occurrence,
                ]);
            });

        return $widget;
    }

    public function createFaqWidget(Collection $languages): Widget
    {
        $widgetType = $this->typeModel::query()->where('type', LayoutTypeEnum::Widget)->firstWhere('key', 'assets');

        $widget = $this->widgetModel::firstOrCreate(['key' => 'faq'], [
            'key' => 'faq',
            'name' => __('capell-admin::generic.faq'),
            'type_id' => $widgetType->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => WidgetComponentEnum::ResourcesAccordion,
                'margin' => ['lg'],
                'align' => 'center',
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
            ->each(fn (Models\Language $language) => $this->tagModel::findOrCreate('faq', 'content', $language->code));

        for ($i = 0; $i < 6; ++$i) {
            $content = $this->contentModel::firstOrCreate([
                'name' => $questions['en'][$i],
                'parent_uuid' => $parentContent->uuid,
                'type_id' => $contentType->id,
            ]);

            $content->tags()->syncWithoutDetaching([$faqTag->id]);

            $widget->assets()->firstOrcreate([
                'asset_id' => $content->uuid,
                'asset_type' => app($this->contentModel)->getMorphClass(),
            ]);

            foreach ($languages as $language) {
                $desc_content = config('capell-demo.contents')[$language->code] ?? '';

                $content->translations()->firstOrCreate(['language_id' => $language->id], [
                    'title' => Str::title($questions[$language->code][$i]),
                    'contents' => [
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

        $video = $this->mediaModel::query()
            ->where('type', 'LIKE', 'video/%')
            ->inRandomOrder()
            ->limit(1)
            ->first();

        if ($video) {
            $image = $this->mediaModel::query()
                ->where('type', 'LIKE', 'image/%')
                ->inRandomOrder()
                ->first();

            $widget->assets()->firstOrcreate(
                [
                    'asset_id' => $video->uuid,
                    'asset_type' => app($this->mediaModel)->getMorphClass(),
                ],
                [
                    'meta' => [
                        'media_type' => 'video',
                        'image_id' => $image?->id,
                    ],
                ]
            );
        }

        $media = $this->mediaModel::query()
            ->where('type', 'LIKE', 'image/%')
            ->inRandomOrder()
            ->limit(8)
            ->pluck('uuid');

        foreach ($media as $mediaUuid) {
            $widget->assets()->firstOrcreate([
                'asset_id' => $mediaUuid,
                'asset_type' => app($this->mediaModel)->getMorphClass(),
            ]);
        }

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

    public function createStaticNavigationWidget(Collection $languages, Page $page): Widget
    {
        /** @var class-string<Models\Navigation> $model */
        $model = CapellCore::getModel(ModelEnum::Navigation);

        // Create menu + items
        $name = 'Example Menu';
        $handle = Str::slug($name);

        $pages = Page::where([
            'site_id' => $page->site_id,
        ])
            ->whereHas(
                'type',
                /** @param  Models\Type  $query */
                fn (BuilderContract $query) => $query->whereIn('type', ['page', 'article'])
                    ->enabled()
                    ->visible()
                    ->accessible()
            )
            ->with([
                'children' => fn (BuilderContract $query) => $query->whereHas('type'),
            ])
            ->visible()
            ->limit(6)
            ->get();

        $model::updateOrCreate([
            'handle' => $handle,
            'site_id' => $page->site_id,
        ], [
            'name' => $name,
            'items' => $this->navigationPageItems($pages, $languages->first()),
        ]);

        // Create widget
        $widget = $this->widgetModel::firstOrCreate(['key' => 'example-navigation'], [
            'name' => __('capell-admin::generic.navigation'),
            'type_id' => $this->typeModel::firstWhere(['key' => 'navigation', 'type' => LayoutTypeEnum::Widget])->id,
            'meta' => [
                'navigation' => $handle,
                'margin' => ['lg'],
            ],
        ]);

        foreach ($languages as $language) {
            $widget->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Example Navigation',
                'contents' => [
                    [
                        'type' => 'title',
                        'data' => [
                            'content' => config('capell-demo.contents')[$language->code],
                        ],
                    ],
                ],
            ]);
        }

        return $widget;
    }

    protected function navigationPageItems(\Illuminate\Support\Collection $siteTree, Models\Language $language): array
    {
        $items = [];

        foreach ($siteTree as $page) {
            $items[(string) Str::uuid()] = [
                'label' => NavigationCreator::getPageNavigationLabel($page, $language),
                'type' => 'page',
                'data' => [
                    'page_uuid' => $page->uuid,
                ],
                'children' => $page->relationLoaded('children') ? $this->navigationPageItems($page->children, $language) : [],
            ];
        }

        return $items;
    }
}
