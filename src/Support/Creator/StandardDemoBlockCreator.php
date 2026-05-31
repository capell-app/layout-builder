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
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Enums\BlockTypeEnum;
use Capell\LayoutBuilder\Enums\ContentTypeEnum;
use Capell\LayoutBuilder\Enums\FrontendComponentKeyEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\Navigation\Models\Navigation;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

abstract class StandardDemoBlockCreator extends BaseDemoCreator
{
    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createContentBlock(Collection $languages): Widget
    {
        $siteId = Site::query()->default()->value('id');

        $type = resolve(TypeCreator::class)->contentBuilderBlockType();

        $block = $this->blockModel::query()->firstOrCreate(['key' => 'example-content'], [
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

        $this->createBlockMedia($block);

        foreach ($languages as $language) {
            $block->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Example Content',
                    'content' => [
                        [
                            'type' => 'content',
                            'data' => [
                                'content' => $this->dummyContent($language->code),
                            ],
                        ],
                    ],
                ],
            );
        }

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createSplitContentBlock(Collection $languages): Widget
    {
        $siteId = Site::query()->default()->value('id');

        $block = $this->blockModel::query()->firstOrCreate(['key' => 'example-split-content'], [
            'name' => 'Example Split Content',
            'blueprint_id' => $this->requiredWidgetType(BlockTypeEnum::SectionBuilder)->id,
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

        $this->createBlockMedia($block);

        foreach ($languages as $language) {
            $block->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Example Content',
                    'content' => [
                        [
                            'type' => 'content',
                            'data' => [
                                'content' => str($this->dummyContent($language->code))->limit(200)->toString(),
                            ],
                        ],
                    ],
                ],
            );
        }

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createBannerImageBlock(Collection $languages): Widget
    {
        $block = resolve(BlockCreator::class)->bannerImageBlock();

        $media = $this->createBlockMedia($block);

        $meta = $block->meta;

        $meta['background_color'] = 'light-gray';
        $meta['background_image'] = $media->getFullUrl(MediaConversionEnum::Medium->value);

        $block->meta = $meta;

        foreach ($languages as $language) {
            $block->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Example Banner',
                    'content' => $this->dummyContent($language->code),
                ],
            );
        }

        return $block;
    }

    public function createGalleryBlock(): Widget
    {
        $block = resolve(BlockCreator::class)->galleryBlock();

        if ($block->assets()->exists()) {
            return $block;
        }

        for ($i = 1; $i <= 5; $i++) {
            $this->createBlockMedia($block);
        }

        return $block;
    }

    public function createPageCardsBlock(Pageable $page, string $container = 'main', int $occurrence = 1): Widget
    {
        $block = resolve(BlockCreator::class)->pagesCardBlock();

        if (
            $block->assets()
                ->where([
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                    'container' => $container,
                    'occurrence' => $occurrence,
                ])
                ->exists()
        ) {
            return $block;
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
            return $block;
        }

        $relatedPages->each(
            fn (Page $relatedPage): WidgetAsset => $this->createPageBlockAsset($block, $page, $container, $occurrence, $relatedPage),
        );

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createFaqBlock(Collection $languages): Widget
    {
        $blockType = $this->typeModel::query()->where('type', LayoutTypeEnum::Widget->value)
            ->firstWhere('key', 'assets');

        if ($blockType === null) {
            $blockType = resolve(TypeCreator::class)->assetsBlockType();
        }

        $block = $this->blockModel::query()->firstOrCreate(['key' => 'faq'], [
            'key' => 'faq',
            'name' => __('capell-admin::generic.faq'),
            'blueprint_id' => $blockType->id,
            'meta' => [
                'icon' => 'heroicon-m-question-mark-circle',
                'component' => BlockComponentEnum::AssetAccordion,
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
            $block->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => __('capell-layout-builder::heading.faq'),
                    'content' => '<p>You can find answers for commonly asked questions</p>',
                ],
            );
        }

        $contentType = $this->typeModel::query()
            ->where('type', 'section')
            ->where('key', ContentTypeEnum::Builder->value)
            ->first();

        throw_unless($contentType instanceof Blueprint, RuntimeException::class, 'A builder content type is required to create FAQ content.');

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

            $block->assets()->firstOrCreate([
                'asset_id' => $content->getKey(),
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);

            foreach ($languages as $language) {
                $desc_content = $this->dummyContent($language->code);
                $translatedQuestion = $questions[$language->code][$i] ?? $question;

                $this->translationsFor($content)->updateOrCreate(
                    ['language_id' => $language->id],
                    [
                        'title' => Str::title($translatedQuestion),
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

        return $block;
    }

    public function createMediaCarouselBlock(): Widget
    {
        $block = resolve(BlockCreator::class)->mediaCarouselBlock();

        if ($block->assets()->exists()) {
            return $block;
        }

        for ($i = 1; $i <= 7; $i++) {
            $this->createBlockMedia($block);
        }

        $this->createBlockMedia($block, type: 'video');

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createStaticNavigationBlock(Collection $languages, Site $site): Widget
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

        $blockType = resolve(TypeCreator::class)->navigationBlockType();

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

        // Create block
        $block = $this->blockModel::query()->firstOrCreate(['key' => 'example-navigation'], [
            'name' => __('Example Navigation'),
            'blueprint_id' => $blockType->id,
            'meta' => [
                'navigation' => $navigation instanceof Model ? (string) $navigation->getAttribute('key') : $key,
                'margin' => ['lg'],
            ],
        ]);

        foreach ($languages as $language) {
            $block->translations()->updateOrCreate(
                ['language_id' => $language->id],
                [
                    'title' => 'Example Navigation',
                ],
            );
        }

        return $block;
    }

    public function createContentsBlock(Widget $block, Pageable $page, string $container, int $occurrence = 1, ?Blueprint $type = null): void
    {
        $pageBlockAssets = $block->assets()->where([
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
            'container' => $container,
            'occurrence' => $occurrence,
        ])
            ->exists();

        if ($pageBlockAssets) {
            return;
        }

        if (! $type instanceof Blueprint) {
            $type = $this->typeModel::query()
                ->where('type', 'section')
                ->default()
                ->first();
        }

        throw_unless($type instanceof Blueprint, RuntimeException::class, 'A section content type is required to create features.');

        $site = $page->site;

        throw_unless($site instanceof Site, RuntimeException::class, 'A page site is required to create feature actions.');

        $features = [
            [
                'title' => 'Empower Your Vision',
                'content' => '<p>Turn an outline into structured CMS content, then preview it before publishing.</p>',
            ],
            [
                'title' => 'Start Your Journey',
                'content' => '<p>Create the next content section, review it in admin, and publish when the preview is ready.</p>',
            ],
            [
                'title' => 'Explore Our Achievements',
                'content' => '<p>Review the project notes, release milestones, and checks that keep this demo honest.</p>',
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
                            'pageable_id' => Page::query()->where('site_id', $site->id)
                                ->whereHas(
                                    'type',
                                    /** @param Blueprint $query */
                                    fn (BuilderContract $query): BuilderContract => $query->listable()->enabled()->accessible(),
                                )
                                ->inRandomOrder()
                                ->value('id'),
                            'site_id' => $site->id,
                        ],
                        [
                            'type' => ActionLinkEnum::Page->value,
                            'pageable_type' => resolve(Page::class)->getMorphClass(),
                            'pageable_id' => Page::query()->where('site_id', $site->id)
                                ->whereHas(
                                    'type',
                                    /** @param Blueprint $query */
                                    fn (BuilderContract $query): BuilderContract => $query->listable()->enabled()->accessible(),
                                )
                                ->inRandomOrder()
                                ->value('id'),
                            'site_id' => $site->id,
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

            foreach ($site->languages as $language) {
                $this->translationsFor($content)->updateOrCreate(
                    ['language_id' => $language->id],
                    [
                        'title' => $feature['title'],
                        'content' => sprintf('<p>%s</p>', $feature['content']),
                    ],
                );
            }

            $this->createMedia($content);

            $this->createPageBlockAsset($block, $page, $container, $occurrence, $content);
        }
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createClientLogosBlock(Collection $languages): Widget
    {
        $block = Widget::query()->firstOrCreate([
            'key' => 'client-logos',
        ], [
            'name' => 'Client Logos',
            'blueprint_id' => $this->requiredWidgetType(BlockTypeEnum::Assets)->id,
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

        if ($block->assets()->exists()) {
            return $block;
        }

        $languages->each(function (Language $language) use ($block): void {
            $block->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => 'Client Logos',
                'content' => '<p>We are proud to work with these amazing partners.</p>',
            ]);
        });

        for ($i = 1; $i <= 12; $i++) {
            $this->createBlockMedia($block);
        }

        return $block;
    }

    public function createBusinessFeaturesBlock(Site $site): Widget
    {
        $block = Widget::query()->firstOrCreate([
            'key' => 'business-features',
        ], [
            'name' => 'Business Features',
            'blueprint_id' => $this->requiredWidgetType(BlockTypeEnum::Sections)->id,
            'meta' => [
                'align' => 'center',
                'margin' => ['lg'],
                'view_file' => 'capell-foundation-theme::components.block.asset.features',
            ],
        ]);

        $this->createMedia($block);

        $title = 'Fundamental Capabilities That Set Us Apart';
        $content = '<p>We combine innovation, efficiency, and deep expertise to deliver exceptional results. Our adaptable, client-focused approach ensures measurable value and lasting impact.</p>';

        $site->languages->each(function (Language $language) use ($block, $title, $content): void {
            $block->translations()->updateOrCreate([
                'language_id' => $language->id,
            ], [
                'title' => $title,
                'content' => $content,
            ]);
        });

        $features = $this->createFeatures($site);

        $features->each(function (Model $content) use ($block): void {
            if ($block->assets()->where('asset_id', $content->getKey())->exists()) {
                return;
            }

            $block->assets()->create([
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->getKey(),
            ]);
        });

        return $block;
    }

    public function createBannersBlock(): Widget
    {
        $creator = resolve(BlockCreator::class);
        $block = $creator->bannerBlock();

        $site = $this->defaultSite();

        $features = $this->createFeatures($site);

        $features->each(function (Model $content) use ($block): void {
            if ($block->assets()->where('asset_id', $content->getKey())->exists()) {
                return;
            }

            $block->assets()->create([
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->getKey(),
            ]);
        });

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createTestimonialsBlock(Collection $languages): Widget
    {
        $blockCreator = resolve(BlockCreator::class);
        $block = $blockCreator->testimonialsBlock();

        $this->createMedia($block, collection: MediaCollectionEnum::BackgroundImage);

        $languages->each(function (Language $language) use ($block): void {
            $block->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'What Our Clients Say',
            ]);
        });

        $testimonials = $this->createTestimonials($languages);

        $testimonials->each(function (Model $content) use ($block): void {
            if ($block->assets()->where('asset_id', $content->getKey())->exists()) {
                return;
            }

            $block->assets()->create([
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->getKey(),
            ]);
        });

        return $block;
    }

    public function createStatisticsBlock(): Widget
    {
        $block = $this->blockModel::query()->firstOrCreate(['key' => 'statistics'], [
            'name' => 'Statistic Blocks',
            'blueprint_id' => $this->requiredWidgetType(BlockTypeEnum::Assets)->id,
            'meta' => [
                'component_item' => FrontendComponentKeyEnum::SectionBlock->value,
                'view_file' => 'capell-foundation-theme::components.block.asset.blocks',
                'spacing' => 'none',
                'columns' => 4,
                'margin' => 'none',
                'container' => ContainerWidthEnum::Small->value,
            ],
            'admin' => [
                'icon' => 'heroicon-o-chart-bar',
            ],
        ]);

        if ($block->assets()->exists()) {
            return $block;
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

        $site = $this->defaultSite();

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

            $block->assets()->firstOrCreate([
                'asset_id' => $content->id,
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
            ]);
        }

        return $block;
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function createTeamPortfolioBlock(Collection $languages): Widget
    {
        $type = $this->typeModel::query()
            ->where([
                'key' => BlockTypeEnum::Sections->value,
                'type' => LayoutTypeEnum::Widget->value,
            ])
            ->first();

        if ($type === null) {
            $type = resolve(TypeCreator::class)->contentsBlockType();
        }

        $block = $this->blockModel::query()->firstOrCreate(['key' => 'team-portfolio'], [
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

        $languages->each(function (Language $language) use ($block): void {
            $block->translations()->firstOrCreate(['language_id' => $language->id], [
                'title' => 'Meet Our Team',
                'content' => '<p>Meet the people represented in the sample team directory.</p>',
            ]);
        });

        $teamMembers = $this->createTeamMembers($languages);

        $teamMembers->each(function (Model $content) use ($block): void {
            if ($block->assets()->where('asset_id', $content->getKey())->exists()) {
                return;
            }

            $block->assets()->create([
                'asset_type' => resolve($this->contentModel)->getMorphClass(),
                'asset_id' => $content->getKey(),
            ]);
        });

        return $block;
    }

    private function dummyContent(string $languageCode): string
    {
        $samples = [
            'en' => 'Capell demo content is written to exercise real publishing surfaces: reusable sections, structured summaries, layout blocks, and editorial workflows.',
            'fr' => 'Le contenu de demonstration Capell presente des surfaces de publication reelles: sections reutilisables, resumes structures, elements de mise en page et flux editoriaux.',
            'de' => 'Capell-Demoinhalte zeigen reale Veroffentlichungsbereiche: wiederverwendbare Abschnitte, strukturierte Zusammenfassungen, Layout-Blocke und redaktionelle Ablaufe.',
            'it' => 'I contenuti demo di Capell mostrano superfici editoriali reali: sezioni riutilizzabili, riepiloghi strutturati, blocchi di layout e flussi editoriali.',
            'es' => 'El contenido demo de Capell muestra superficies de publicacion reales: secciones reutilizables, resumenes estructurados, bloques de diseno y flujos editoriales.',
        ];

        return '<p>' . ($samples[$languageCode] ?? $samples['en']) . '</p>';
    }
}
