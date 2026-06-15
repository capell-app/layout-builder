<?php

declare(strict_types=1);

use Capell\BlockLibrary\Data\BlockCompatibilityData;
use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Data\BlockVariantData;
use Capell\BlockLibrary\Data\BlockVariantKey;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Database\Factories\MediaFactory;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\LayoutBuilder\Data\PublicLayoutWidgetData;
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
                'capell::widget.default' => PackageAlert::class,
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

/**
 * @return array<string, mixed>
 */
function publicLayoutGraphFirstWidgetPresentation(PublicLayoutGraphData $graph): array
{
    $presentation = $graph->containers[0]->widgets[0]->data['presentation'] ?? [];

    return is_array($presentation) ? $presentation : [];
}

it('builds public layout data for selected containers only', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);

    $mainWidget = Widget::factory()->create(['key' => 'main-widget']);
    $sidebarWidget = Widget::factory()->create(['key' => 'sidebar-widget']);

    TranslationFactory::new()
        ->translatable($mainWidget)
        ->language($language)
        ->create([
            'title' => 'Main Widget',
            'content' => '<p>Main content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'key' => 'article',
        'containers' => [
            'main' => [
                'label' => 'Main',
                'widgets' => [
                    $mainWidget->key,
                ],
            ],
            'sidebar' => [
                'label' => 'Sidebar',
                'widgets' => [
                    ['widget_key' => $sidebarWidget->key, 'occurrence' => 1],
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
        ->and($graph->containers[0]->widgets)->toHaveCount(1)
        ->and($graph->containers[0]->widgets[0])->toBeInstanceOf(PublicLayoutWidgetData::class)
        ->and($graph->containers[0]->widgets[0]->key)->toBe('main-widget')
        ->and($graph->containers[0]->widgets[0]->data['title'])->toBe('Main Widget')
        ->and($graph->containers[0]->widgets[0]->data['content'])->toBe('<p>Main content</p>');
});

it('uses the site theme key for public widget compatibility even when the site relation is not preloaded', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'theme-limited',
        label: 'Theme limited',
        description: 'Theme limited widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.theme-limited',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::widgets.variants.split_media'),
        ],
        compatibility: new BlockCompatibilityData(themeKeys: ['foundation']),
    ));

    $language = Language::factory()->create();
    $theme = Theme::factory()->create(['key' => 'unsupported-theme']);
    $site = Site::factory()->create(['language_id' => $language->id, 'theme_id' => $theme->getKey()]);
    $site->unsetRelation('theme');

    $widget = Widget::factory()->create(['key' => 'theme-limited']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [[
                'widget_key' => $widget->key,
                'occurrence' => 1,
                'meta' => ['widget_variant' => 'split-media'],
            ]]],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);

    expect(publicLayoutGraphFirstWidgetPresentation($graph)['variant'] ?? null)->toBe('default');
});

it('uses the layout theme before the site theme for public widget compatibility', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'layout-theme-limited',
        label: 'Layout theme limited',
        description: 'Layout theme limited widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.layout-theme-limited',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::widgets.variants.split_media'),
        ],
        compatibility: new BlockCompatibilityData(themeKeys: ['layout-theme']),
    ));

    $language = Language::factory()->create();
    $siteTheme = Theme::factory()->create(['key' => 'site-theme']);
    $layoutTheme = Theme::factory()->create(['key' => 'layout-theme']);
    $site = Site::factory()->create(['language_id' => $language->id, 'theme_id' => $siteTheme->getKey()]);
    $widget = Widget::factory()->create(['key' => 'layout-theme-limited']);
    $layout = Layout::factory()->site($site)->create([
        'theme_id' => $layoutTheme->getKey(),
        'containers' => [
            'main' => ['widgets' => [[
                'widget_key' => $widget->key,
                'occurrence' => 1,
                'meta' => ['widget_variant' => 'split-media'],
            ]]],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $page->setRelation('site', $site);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);

    expect(publicLayoutGraphFirstWidgetPresentation($graph)['variant'] ?? null)->toBe('split-media');
});

it('reuses public payload resolver contributor caches across page widgets', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'cached-theme',
        label: 'Cached theme',
        description: 'Cached theme widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.cached-theme',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
        ],
    ));

    $language = Language::factory()->create();
    $theme = Theme::factory()->create(['key' => 'site-theme']);
    $site = Site::factory()->create(['language_id' => $language->id, 'theme_id' => $theme->getKey()]);
    $firstWidget = Widget::factory()->create(['key' => 'cached-theme']);
    $secondWidget = Widget::factory()->create(['key' => 'cached-theme']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [
                ['widget_key' => $firstWidget->key, 'occurrence' => 1],
                ['widget_key' => $secondWidget->key, 'occurrence' => 1],
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

it('lets package tagged contributors extend widget payload data and html', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create(['key' => 'featured']);

    TranslationFactory::new()
        ->translatable($widget)
        ->language($language)
        ->create([
            'title' => 'Featured',
            'content' => '<p>Featured content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 3]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.layout-builder-payload-contributor', fn (): PublicWidgetPayloadContributor => new class implements PublicWidgetPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'source' => 'package',
                'items' => [
                    ['label' => $containerKey . ':' . $occurrence],
                ],
            ];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return '<section>' . $widget->key . ':' . $containerKey . ':' . $occurrence . '</section>';
        }
    });

    app()->tag(['test.layout-builder-payload-contributor'], PublicWidgetPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);
    $widgetData = $graph->containers[0]->widgets[0];

    expect($widgetData->data)
        ->toMatchArray([
            'title' => 'Featured',
            'content' => '<p>Featured content</p>',
            'source' => 'package',
            'items' => [
                ['label' => 'main:3'],
            ],
        ])
        ->and($widgetData->html)->toBe('<section>featured:main:3</section>');
});

it('adds sanitized widget presentation data without exposing authoring metadata', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'test-hero',
        label: 'Hero',
        description: 'Hero widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.hero',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
            new BlockVariantData(BlockVariantKey::from('split-media'), 'vendor-package::widgets.variants.split_media'),
        ],
    ));

    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create([
        'key' => 'test-hero',
        'meta' => [
            'widget_variant' => 'default',
            'widget_settings' => [
                'signed_url' => 'https://example.test/admin/signed',
            ],
            'admin_schema' => ['secret' => true],
        ],
    ]);

    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [[
                'widget_key' => $widget->key,
                'occurrence' => 1,
                'meta' => [
                    'widget_variant' => 'split-media',
                    'widget_settings' => [
                        'spacing' => 'tight',
                        'anchor_id' => 'Hero Section',
                        'signed_url' => 'https://example.test/admin/signed',
                    ],
                    'admin_schema' => ['secret' => true],
                ],
            ]]],
        ],
    ]);

    app()->singleton('test.widget-settings-spy-contributor', fn (): PublicWidgetPayloadContributor => new class implements PublicWidgetPayloadContributor
    {
        public function priority(): int
        {
            return 20;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'seenSettings' => $widget->meta['widget_settings'] ?? [],
            ];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return null;
        }
    });
    app()->tag(['test.widget-settings-spy-contributor'], PublicWidgetPayloadContributor::TAG);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);
    $payload = $graph->containers[0]->widgets[0]->data;

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
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('widget_settings')
        ->and($payload['seenSettings'])->toBe([
            'spacing' => 'tight',
            'anchor_id' => 'hero-section',
        ]);
});

it('sanitizes stored widget meta before public contributors see it', function (): void {
    resolve(BlockRegistry::class)->register(new BlockDefinitionData(
        key: 'stored-meta',
        label: 'Stored meta',
        description: 'Stored meta widget.',
        category: 'marketing',
        view: 'vendor-package::widgets.stored-meta',
        variants: [
            new BlockVariantData(BlockVariantKey::from('default'), 'vendor-package::widgets.variants.default'),
        ],
    ));

    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create([
        'key' => 'stored-meta',
        'meta' => [
            'widget_variant' => 'signed_url',
            'widget_key' => 'admin_schema',
            'widget_settings' => [
                'spacing' => 'spacious',
                'anchor_id' => 'signed editor url',
                'background' => 'https://example.test/admin/signed',
                'show_cta' => ['admin_schema' => true],
                'cards_per_row' => '2',
                'signed_url' => 'https://example.test/admin/signed',
            ],
            'minimum_items' => '2',
            'show_current_page' => '1',
            'show_home' => '0',
            'show_parent' => true,
            'admin_schema' => ['secret' => true],
        ],
    ]);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [[
                'widget_key' => $widget->key,
                'occurrence' => 1,
            ]]],
        ],
    ]);

    app()->singleton('test.stored-meta-spy-contributor', fn (): PublicWidgetPayloadContributor => new class implements PublicWidgetPayloadContributor
    {
        public function priority(): int
        {
            return 20;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'seenMeta' => $widget->meta,
            ];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return null;
        }
    });
    app()->tag(['test.stored-meta-spy-contributor'], PublicWidgetPayloadContributor::TAG);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);
    $payload = $graph->containers[0]->widgets[0]->data;

    expect($payload['seenMeta'])->toBe([
        'show_home' => false,
        'show_parent' => true,
        'show_current_page' => true,
        'minimum_items' => 2,
        'widget_settings' => [
            'spacing' => 'spacious',
            'cards_per_row' => 2,
        ],
    ])
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('signed_url')
        ->and(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('admin_schema');
});

it('scopes default widget assets to the matching occurrence when building public layout data', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create(['key' => 'featured']);
    $firstAsset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'First asset']);
    $secondAsset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Second asset']);

    WidgetAsset::factory()
        ->widget($widget)
        ->asset($firstAsset)
        ->occurrence(1)
        ->create();
    WidgetAsset::factory()
        ->widget($widget)
        ->asset($secondAsset)
        ->occurrence(2)
        ->create();

    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [
                ['widget_key' => $widget->key, 'occurrence' => 1],
                ['widget_key' => $widget->key, 'occurrence' => 2],
            ]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.layout-builder-asset-contributor', fn (): PublicWidgetPayloadContributor => new class implements PublicWidgetPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'asset_ids' => $widget->assets
                    ->map(fn (WidgetAsset $widgetAsset): mixed => $widgetAsset->asset?->getKey())
                    ->values()
                    ->all(),
            ];
        }

        public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return null;
        }
    });

    app()->tag(['test.layout-builder-asset-contributor'], PublicWidgetPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);

    expect($graph->containers[0]->widgets[0]->data['asset_ids'])->toBe([$firstAsset->getKey()])
        ->and($graph->containers[0]->widgets[1]->data['asset_ids'])->toBe([$secondAsset->getKey()]);
});

it('reuses scoped preloaded widget assets when building public layout data', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create(['key' => 'featured']);
    $asset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Preloaded asset']);

    WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->occurrence(1)
        ->create();

    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => [
                ['widget_key' => $widget->key, 'occurrence' => 1],
            ]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    resolve(LayoutLoader::class)->preloadLayoutWidgets($layout, $language, $page, ['main']);

    $widgetAssetQueries = 0;

    DB::listen(function (QueryExecuted $query) use (&$widgetAssetQueries): void {
        if (str_contains($query->sql, 'widget_assets')) {
            $widgetAssetQueries++;
        }
    });

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, ['main']);

    expect($graph->containers[0]->widgets)->toHaveCount(1)
        ->and($widgetAssetQueries)->toBe(0);
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

    resolve(LayoutLoader::class)->preloadLayoutWidgets($layout, $language, null, ['main']);

    expect($layout->relationLoaded('media'))->toBeTrue()
        ->and($layout->media->first()?->getKey())->toBe($media->getKey());
});
