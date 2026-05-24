<?php

declare(strict_types=1);

use Capell\ContentBlocks\Data\BlockCompatibilityData;
use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\BlockVariantData;
use Capell\ContentBlocks\Data\BlockVariantKey;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Database\Factories\MediaFactory;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadContributor;
use Capell\LayoutBuilder\Data\PublicLayoutBlockData;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Capell\LayoutBuilder\Tests\Fixtures\View\Components\PackageAlert;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    app()->bind(BladeComponentResolverInterface::class, fn (): BladeComponentResolverInterface => new class implements BladeComponentResolverInterface
    {
        /**
         * @return array<string, class-string>
         */
        public function getClassComponentAliases(): array
        {
            return [
                'capell::block.default' => PackageAlert::class,
            ];
        }

        /**
         * @return array<string, string>
         */
        public function getClassComponentNamespaces(): array
        {
            return [];
        }
    });
});

it('builds public layout data for selected containers only', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);

    $mainBlock = Widget::factory()->create(['key' => 'main-block']);
    $sidebarBlock = Widget::factory()->create(['key' => 'sidebar-block']);

    TranslationFactory::new()
        ->translatable($mainBlock)
        ->language($language)
        ->create([
            'title' => 'Main Widget',
            'content' => '<p>Main content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'key' => 'article',
        'widgets' => [$mainBlock->key, $sidebarBlock->key],
        'containers' => [
            'main' => [
                'label' => 'Main',
                'widgets' => [
                    $mainBlock->key,
                ],
            ],
            'sidebar' => [
                'label' => 'Sidebar',
                'widgets' => [
                    ['widget_key' => $sidebarBlock->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, ['main']);

    expect($graph)->toBeInstanceOf(PublicLayoutGraphData::class)
        ->and($graph->key)->toBe('article')
        ->and($graph->containers)->toHaveCount(1)
        ->and($graph->containers[0])->toBeInstanceOf(PublicLayoutContainerData::class)
        ->and($graph->containers[0]->key)->toBe('main')
        ->and($graph->containers[0]->blocks)->toHaveCount(1)
        ->and($graph->containers[0]->blocks[0])->toBeInstanceOf(PublicLayoutBlockData::class)
        ->and($graph->containers[0]->blocks[0]->key)->toBe('main-block')
        ->and($graph->containers[0]->blocks[0]->data['title'])->toBe('Main Widget')
        ->and($graph->containers[0]->blocks[0]->data['content'])->toBe('<p>Main content</p>');
});

it('uses the site theme key for public block compatibility even when the site relation is not preloaded', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'theme-limited',
        label: 'Theme limited',
        description: 'Theme limited block.',
        category: 'marketing',
        view: 'vendor-package::blocks.theme-limited',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::blocks.variants.split_media'),
        ],
        compatibility: new BlockCompatibilityData(themeKeys: ['foundation']),
    ));

    $language = Language::factory()->create();
    $theme = Theme::factory()->create(['key' => 'unsupported-theme']);
    $site = Site::factory()->create(['language_id' => $language->id, 'theme_id' => $theme->getKey()]);
    $site->unsetRelation('theme');

    $block = Widget::factory()->create(['key' => 'theme-limited']);
    $layout = Layout::factory()->site($site)->create([
        'widgets' => [$block->key],
        'containers' => [
            'main' => ['widgets' => [[
                'widget_key' => $block->key,
                'occurrence' => 1,
                'meta' => ['block_variant' => 'split-media'],
            ]]],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);

    expect($graph->containers[0]->blocks[0]->data['presentation']['variant'])->toBe('default');
});

it('uses the layout theme before the site theme for public block compatibility', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'layout-theme-limited',
        label: 'Layout theme limited',
        description: 'Layout theme limited block.',
        category: 'marketing',
        view: 'vendor-package::blocks.layout-theme-limited',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::blocks.variants.split_media'),
        ],
        compatibility: new BlockCompatibilityData(themeKeys: ['layout-theme']),
    ));

    $language = Language::factory()->create();
    $siteTheme = Theme::factory()->create(['key' => 'site-theme']);
    $layoutTheme = Theme::factory()->create(['key' => 'layout-theme']);
    $site = Site::factory()->create(['language_id' => $language->id, 'theme_id' => $siteTheme->getKey()]);
    $block = Widget::factory()->create(['key' => 'layout-theme-limited']);
    $layout = Layout::factory()->site($site)->create([
        'theme_id' => $layoutTheme->getKey(),
        'widgets' => [$block->key],
        'containers' => [
            'main' => ['widgets' => [[
                'widget_key' => $block->key,
                'occurrence' => 1,
                'meta' => ['block_variant' => 'split-media'],
            ]]],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $page->setRelation('site', $site);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);

    expect($graph->containers[0]->blocks[0]->data['presentation']['variant'])->toBe('split-media');
});

it('reuses public payload resolver contributor caches across page blocks', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'cached-theme',
        label: 'Cached theme',
        description: 'Cached theme block.',
        category: 'marketing',
        view: 'vendor-package::blocks.cached-theme',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
        ],
    ));

    $language = Language::factory()->create();
    $theme = Theme::factory()->create(['key' => 'site-theme']);
    $site = Site::factory()->create(['language_id' => $language->id, 'theme_id' => $theme->getKey()]);
    $firstBlock = Widget::factory()->create(['key' => 'cached-theme']);
    $secondBlock = Widget::factory()->create(['key' => 'cached-theme']);
    $layout = Layout::factory()->site($site)->create([
        'widgets' => [$firstBlock->key, $secondBlock->key],
        'containers' => [
            'main' => ['widgets' => [
                ['widget_key' => $firstBlock->key, 'occurrence' => 1],
                ['widget_key' => $secondBlock->key, 'occurrence' => 1],
            ]],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $site->unsetRelation('theme');
    $page->setRelation('site', $site);

    $themeQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$themeQueries): void {
        if (str_contains($query->sql, 'from "themes"') || str_contains($query->sql, 'from `themes`')) {
            $themeQueries++;
        }
    });

    BuildPublicLayoutGraphAction::run($layout, $page, $language);

    expect($themeQueries)->toBe(1);
});

it('lets package tagged contributors extend block payload data and html', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $block = Widget::factory()->create(['key' => 'featured']);

    TranslationFactory::new()
        ->translatable($block)
        ->language($language)
        ->create([
            'title' => 'Featured',
            'content' => '<p>Featured content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'widgets' => [$block->key],
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $block->key, 'occurrence' => 3]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.layout-builder-payload-contributor', fn (): PublicBlockPayloadContributor => new class implements PublicBlockPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'source' => 'package',
                'items' => [
                    ['label' => $containerKey . ':' . $occurrence],
                ],
            ];
        }

        public function html(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return '<section>' . $block->key . ':' . $containerKey . ':' . $occurrence . '</section>';
        }
    });

    app()->tag(['test.layout-builder-payload-contributor'], PublicBlockPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);
    $blockData = $graph->containers[0]->blocks[0];

    expect($blockData->data)
        ->toMatchArray([
            'title' => 'Featured',
            'content' => '<p>Featured content</p>',
            'source' => 'package',
            'items' => [
                ['label' => 'main:3'],
            ],
        ])
        ->and($blockData->html)->toBe('<section>featured:main:3</section>');
});

it('adds sanitized block presentation data without exposing authoring metadata', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'hero',
        label: 'Hero',
        description: 'Hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.hero',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::blocks.variants.split_media'),
        ],
    ));

    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $block = Widget::factory()->create([
        'key' => 'hero',
        'meta' => [
            'block_variant' => 'default',
            'block_settings' => [
                'signed_url' => 'https://example.test/admin/signed',
            ],
            'admin_schema' => ['secret' => true],
        ],
    ]);

    $layout = Layout::factory()->site($site)->create([
        'widgets' => [$block->key],
        'containers' => [
            'main' => ['widgets' => [[
                'widget_key' => $block->key,
                'occurrence' => 1,
                'meta' => [
                    'block_variant' => 'split-media',
                    'block_settings' => [
                        'spacing' => 'tight',
                        'anchor_id' => 'Hero Section',
                        'signed_url' => 'https://example.test/admin/signed',
                    ],
                    'admin_schema' => ['secret' => true],
                ],
            ]]],
        ],
    ]);

    app()->singleton('test.block-settings-spy-contributor', fn (): PublicBlockPayloadContributor => new class implements PublicBlockPayloadContributor
    {
        public function priority(): int
        {
            return 20;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'seenSettings' => $block->meta['block_settings'] ?? [],
            ];
        }

        public function html(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return null;
        }
    });
    app()->tag(['test.block-settings-spy-contributor'], PublicBlockPayloadContributor::TAG);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);
    $payload = $graph->containers[0]->blocks[0]->data;

    expect($payload['presentation'])->toBe([
        'variant' => 'split-media',
        'spacing' => 'tight',
        'background' => 'default',
        'mediaPosition' => 'top',
        'cardsPerRow' => 3,
        'showCta' => true,
        'headingWidth' => 'normal',
        'anchorId' => 'hero-section',
    ])
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('signed_url')
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('admin_schema')
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('block_settings')
        ->and($payload['seenSettings'])->toBe([
            'spacing' => 'tight',
            'anchor_id' => 'hero-section',
        ]);
});

it('sanitizes stored block meta before public contributors see it', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'stored-meta',
        label: 'Stored meta',
        description: 'Stored meta block.',
        category: 'marketing',
        view: 'vendor-package::blocks.stored-meta',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::blocks.variants.default'),
        ],
    ));

    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $block = Widget::factory()->create([
        'key' => 'stored-meta',
        'meta' => [
            'block_variant' => 'signed_url',
            'widget_key' => 'admin_schema',
            'block_settings' => [
                'spacing' => 'spacious',
                'anchor_id' => 'signed editor url',
                'background' => 'https://example.test/admin/signed',
                'show_cta' => ['admin_schema' => true],
                'cards_per_row' => '2',
                'signed_url' => 'https://example.test/admin/signed',
            ],
            'admin_schema' => ['secret' => true],
        ],
    ]);
    $layout = Layout::factory()->site($site)->create([
        'widgets' => [$block->key],
        'containers' => [
            'main' => ['widgets' => [[
                'widget_key' => $block->key,
                'occurrence' => 1,
            ]]],
        ],
    ]);

    app()->singleton('test.stored-meta-spy-contributor', fn (): PublicBlockPayloadContributor => new class implements PublicBlockPayloadContributor
    {
        public function priority(): int
        {
            return 20;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'seenMeta' => $block->meta,
            ];
        }

        public function html(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return null;
        }
    });
    app()->tag(['test.stored-meta-spy-contributor'], PublicBlockPayloadContributor::TAG);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);
    $payload = $graph->containers[0]->blocks[0]->data;

    expect($payload['seenMeta'])->toBe([
        'block_settings' => [
            'spacing' => 'spacious',
            'cards_per_row' => 2,
        ],
    ])
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('signed_url')
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('admin_schema');
});

it('scopes default block assets to the matching occurrence when building public layout data', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $block = Widget::factory()->create(['key' => 'featured']);
    $firstAsset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'First asset']);
    $secondAsset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Second asset']);

    WidgetAsset::factory()
        ->block($block)
        ->asset($firstAsset)
        ->occurrence(1)
        ->create();
    WidgetAsset::factory()
        ->block($block)
        ->asset($secondAsset)
        ->occurrence(2)
        ->create();

    $layout = Layout::factory()->site($site)->create([
        'widgets' => [$block->key],
        'containers' => [
            'main' => ['widgets' => [
                ['widget_key' => $block->key, 'occurrence' => 1],
                ['widget_key' => $block->key, 'occurrence' => 2],
            ]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.layout-builder-asset-contributor', fn (): PublicBlockPayloadContributor => new class implements PublicBlockPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'asset_ids' => $block->assets
                    ->map(fn (WidgetAsset $blockAsset): mixed => $blockAsset->asset?->getKey())
                    ->values()
                    ->all(),
            ];
        }

        public function html(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return null;
        }
    });

    app()->tag(['test.layout-builder-asset-contributor'], PublicBlockPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);

    expect($graph->containers[0]->blocks[0]->data['asset_ids'])->toBe([$firstAsset->getKey()])
        ->and($graph->containers[0]->blocks[1]->data['asset_ids'])->toBe([$secondAsset->getKey()]);
});

it('reuses scoped preloaded block assets when building public layout data', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $block = Widget::factory()->create(['key' => 'featured']);
    $asset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Preloaded asset']);

    WidgetAsset::factory()
        ->block($block)
        ->asset($asset)
        ->occurrence(1)
        ->create();

    $layout = Layout::factory()->site($site)->create([
        'widgets' => [$block->key],
        'containers' => [
            'main' => ['widgets' => [
                ['widget_key' => $block->key, 'occurrence' => 1],
            ]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    resolve(LayoutLoader::class)->preloadLayoutBlocks($layout, $language, $page, ['main']);

    $blockAssetQueries = 0;

    DB::listen(function (QueryExecuted $query) use (&$blockAssetQueries): void {
        if (str_contains($query->sql, 'block_assets')) {
            $blockAssetQueries++;
        }
    });

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, ['main']);

    expect($graph->containers[0]->blocks)->toHaveCount(1)
        ->and($blockAssetQueries)->toBe(0);
});

it('preloads layout media for public container backgrounds', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);
    $media = MediaFactory::new()->model($layout)->create([
        'collection_name' => 'main-background',
    ]);

    resolve(LayoutLoader::class)->preloadLayoutBlocks($layout, $language, null, ['main']);

    expect($layout->relationLoaded('media'))->toBeTrue()
        ->and($layout->media->first()?->getKey())->toBe($media->getKey());
});
