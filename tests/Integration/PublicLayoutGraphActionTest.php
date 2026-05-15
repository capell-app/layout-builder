<?php

declare(strict_types=1);

use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadContributor;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutElementData;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
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
                'capell::element.default' => PackageAlert::class,
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

    $mainElement = Element::factory()->create(['key' => 'main-element']);
    $sidebarElement = Element::factory()->create(['key' => 'sidebar-element']);

    TranslationFactory::new()
        ->translatable($mainElement)
        ->language($language)
        ->create([
            'title' => 'Main Element',
            'content' => '<p>Main content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'key' => 'article',
        'elements' => [$mainElement->key, $sidebarElement->key],
        'containers' => [
            'main' => [
                'label' => 'Main',
                'elements' => [
                    ['element_key' => $mainElement->key, 'occurrence' => 1],
                ],
            ],
            'sidebar' => [
                'label' => 'Sidebar',
                'elements' => [
                    ['element_key' => $sidebarElement->key, 'occurrence' => 1],
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
        ->and($graph->containers[0]->elements)->toHaveCount(1)
        ->and($graph->containers[0]->elements[0])->toBeInstanceOf(PublicLayoutElementData::class)
        ->and($graph->containers[0]->elements[0]->key)->toBe('main-element')
        ->and($graph->containers[0]->elements[0]->data['title'])->toBe('Main Element')
        ->and($graph->containers[0]->elements[0]->data['content'])->toBe('<p>Main content</p>');
});

it('lets package tagged contributors extend element payload data and html', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $element = Element::factory()->create(['key' => 'featured']);

    TranslationFactory::new()
        ->translatable($element)
        ->language($language)
        ->create([
            'title' => 'Featured',
            'content' => '<p>Featured content</p>',
        ]);

    $layout = Layout::factory()->site($site)->create([
        'elements' => [$element->key],
        'containers' => [
            'main' => ['elements' => [['element_key' => $element->key, 'occurrence' => 3]]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.layout-builder-payload-contributor', fn (): PublicElementPayloadContributor => new class implements PublicElementPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'source' => 'package',
                'items' => [
                    ['label' => $containerKey . ':' . $occurrence],
                ],
            ];
        }

        public function html(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): string
        {
            return '<section>' . $element->key . ':' . $containerKey . ':' . $occurrence . '</section>';
        }
    });

    app()->tag(['test.layout-builder-payload-contributor'], PublicElementPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, includeHtml: true);
    $elementData = $graph->containers[0]->elements[0];

    expect($elementData->data)
        ->toMatchArray([
            'title' => 'Featured',
            'content' => '<p>Featured content</p>',
            'source' => 'package',
            'items' => [
                ['label' => 'main:3'],
            ],
        ])
        ->and($elementData->html)->toBe('<section>featured:main:3</section>');
});

it('scopes default element assets to the matching occurrence when building public layout data', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $element = Element::factory()->create(['key' => 'featured']);
    $firstAsset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'First asset']);
    $secondAsset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Second asset']);

    ElementAsset::factory()
        ->element($element)
        ->asset($firstAsset)
        ->occurrence(1)
        ->create();
    ElementAsset::factory()
        ->element($element)
        ->asset($secondAsset)
        ->occurrence(2)
        ->create();

    $layout = Layout::factory()->site($site)->create([
        'elements' => [$element->key],
        'containers' => [
            'main' => ['elements' => [
                ['element_key' => $element->key, 'occurrence' => 1],
                ['element_key' => $element->key, 'occurrence' => 2],
            ]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    app()->singleton('test.layout-builder-asset-contributor', fn (): PublicElementPayloadContributor => new class implements PublicElementPayloadContributor
    {
        public function priority(): int
        {
            return 10;
        }

        /**
         * @return array<string, mixed>
         */
        public function data(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): array
        {
            return [
                'asset_ids' => $element->assets
                    ->map(fn (ElementAsset $elementAsset): mixed => $elementAsset->asset?->getKey())
                    ->values()
                    ->all(),
            ];
        }

        public function html(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): ?string
        {
            return null;
        }
    });

    app()->tag(['test.layout-builder-asset-contributor'], PublicElementPayloadContributor::TAG);

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language);

    expect($graph->containers[0]->elements[0]->data['asset_ids'])->toBe([$firstAsset->getKey()])
        ->and($graph->containers[0]->elements[1]->data['asset_ids'])->toBe([$secondAsset->getKey()]);
});

it('reuses scoped preloaded element assets when building public layout data', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $element = Element::factory()->create(['key' => 'featured']);
    $asset = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Preloaded asset']);

    ElementAsset::factory()
        ->element($element)
        ->asset($asset)
        ->occurrence(1)
        ->create();

    $layout = Layout::factory()->site($site)->create([
        'elements' => [$element->key],
        'containers' => [
            'main' => ['elements' => [
                ['element_key' => $element->key, 'occurrence' => 1],
            ]],
        ],
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    resolve(LayoutLoader::class)->preloadLayoutElements($layout, $language, $page, ['main']);

    $elementAssetQueries = 0;

    DB::listen(function (QueryExecuted $query) use (&$elementAssetQueries): void {
        if (str_contains($query->sql, 'layout_element_assets')) {
            $elementAssetQueries++;
        }
    });

    $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, ['main']);

    expect($graph->containers[0]->elements)->toHaveCount(1)
        ->and($elementAssetQueries)->toBe(0);
});
