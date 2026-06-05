<?php

declare(strict_types=1);

use Capell\Core\Contracts\BladeComponentResolverInterface;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Data\PublicLayoutGraphData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Capell\LayoutBuilder\Tests\Fixtures\View\Components\PackageAlert;
use Illuminate\Support\Facades\DB;

/**
 * Stress the public layout graph build with a large, complicated layout — many containers,
 * each holding many widgets. The build must:
 *   - stay within a bounded query budget (widget/translation/asset loading is batched, not N+1),
 *   - not scale its query count with the widget count, and
 *   - never leak authoring metadata (admin_schema / signed_url / widget_settings) into the
 *     public payload, even at scale.
 */
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

it('builds and renders a multi-container layout graph within declared performance budgets without leaking authoring metadata', function (): void {
    // Keep the wall-clock render budget tied to the manifest; the large stress case below
    // separately proves query count does not scale with hundreds of widgets.
    $fixture = seedBigLayout(prefix: 'big', containerCount: 6, widgetsPerContainer: 3);

    [$graph, $queryCount] = measurePublicLayoutBudget(
        fn (): PublicLayoutGraphData => BuildPublicLayoutGraphAction::run(
            $fixture['layout'],
            $fixture['page'],
            $fixture['language'],
        ),
    );
    storePublicLayoutWidgets($fixture);

    view('capell-layout-builder::components.layout.area', ['layout' => $fixture['layout']])->render();

    [$html, $renderQueryCount, $renderMilliseconds] = measurePublicLayoutBudget(
        fn (): string => view('capell-layout-builder::components.layout.area', ['layout' => $fixture['layout']])->render(),
    );

    $renderedWidgetCount = array_sum(array_map(
        static fn (PublicLayoutContainerData $container): int => count($container->widgets),
        $graph->containers,
    ));
    $serialized = json_encode($graph, JSON_THROW_ON_ERROR);

    expect($graph)->toBeInstanceOf(PublicLayoutGraphData::class)
        ->and($graph->containers)->toHaveCount($fixture['containerCount'])
        ->and($renderedWidgetCount)->toBe($fixture['totalWidgets'])
        ->and($queryCount)->toBeLessThanOrEqual(layoutBuilderAdminQueryBudget())
        ->and($queryCount)->toBeLessThan(15)
        ->and($renderQueryCount)->toBe(0)
        ->and($renderMilliseconds)->toBeLessThanOrEqual(layoutBuilderFrontendRenderBudgetMilliseconds())
        ->and($html)->toContain('Package alert')
        ->and($serialized)->not->toContain('admin_schema')
        ->and($serialized)->not->toContain('signed_url')
        ->and($serialized)->not->toContain('widget_settings');
})->group('layout-builder', 'stress');

it('does not scale public layout graph queries with widget count', function (): void {
    $small = seedBigLayout(prefix: 'small', containerCount: 1, widgetsPerContainer: 1);
    $smallQueries = countLayoutGraphQueries($small);

    $large = seedBigLayout(prefix: 'large', containerCount: 6, widgetsPerContainer: 40);
    $largeQueries = countLayoutGraphQueries($large);

    // Batched loading means a 240-widget layout costs no more than a handful of extra queries
    // over a single-widget layout — query count is driven by relation kinds, not widget volume.
    expect($largeQueries)->toBeLessThanOrEqual($smallQueries + 3);
})->group('layout-builder', 'stress');

/**
 * @param  array{containerCount?: int, language: Language, layout: Layout, page: Page, site?: Site, totalWidgets?: int}  $fixture
 */
function countLayoutGraphQueries(array $fixture): int
{
    $queryCount = 0;
    $listener = function () use (&$queryCount): void {
        $queryCount++;
    };

    DB::listen($listener);
    BuildPublicLayoutGraphAction::run($fixture['layout'], $fixture['page'], $fixture['language']);

    return $queryCount;
}

/**
 * @template TValue
 *
 * @param  Closure(): TValue  $callback
 * @return array{0: TValue, 1: int, 2: float}
 */
function measurePublicLayoutBudget(Closure $callback): array
{
    $queryCount = 0;

    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $startedAt = hrtime(true);
    $result = $callback();
    $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;

    return [$result, $queryCount, $elapsedMilliseconds];
}

/**
 * @param  array{language: Language, layout: Layout, page: Page}  $fixture
 */
function storePublicLayoutWidgets(array $fixture): void
{
    CapellLayoutManager::clearContainerWidgets();

    $layout = $fixture['layout'];
    $language = $fixture['language'];
    $page = $fixture['page'];
    $containers = $layout->getAttribute('containers');
    $containers = is_array($containers) ? $containers : [];
    $loader = resolve(LayoutLoader::class);

    $loader->preloadLayoutWidgets($layout, $language, $page);

    foreach ($containers as $containerKey => $container) {
        foreach (LayoutWidgetData::fromContainer(is_array($container) ? $container : []) as $widgetData) {
            $widgetKey = LayoutWidgetData::key($widgetData);
            if ($widgetKey === null) {
                continue;
            }

            $occurrence = LayoutWidgetData::occurrence($widgetData);
            $widget = $loader->getLayoutWidget($layout, $widgetKey, $language, $page, (string) $containerKey, $occurrence);

            if ($widget instanceof Widget) {
                CapellLayoutManager::storeContainerWidget((string) $containerKey, $widgetKey, $widget, $occurrence);
            }
        }
    }
}

function layoutBuilderFrontendRenderBudgetMilliseconds(): float
{
    return (float) data_get(layoutBuilderManifest(), 'performance.frontendRenderBudgetMs', 20);
}

function layoutBuilderAdminQueryBudget(): int
{
    return (int) data_get(layoutBuilderManifest(), 'performance.adminQueryBudget', 50);
}

/**
 * @return array<string, mixed>
 */
function layoutBuilderManifest(): array
{
    return json_decode(
        (string) file_get_contents(dirname(__DIR__, 2) . '/capell.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/**
 * Build a complicated layout: $containerCount containers, each holding $widgetsPerContainer
 * unique widgets whose stored meta carries authoring-only secrets that must be sanitized out.
 *
 * @return array{
 *     containerCount: int,
 *     language: Language,
 *     layout: Layout,
 *     page: Page,
 *     site: Site,
 *     totalWidgets: int,
 * }
 */
function seedBigLayout(string $prefix, int $containerCount, int $widgetsPerContainer): array
{
    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);

    $containers = [];
    $totalWidgets = 0;

    foreach (range(1, $containerCount) as $containerIndex) {
        $widgetReferences = [];

        foreach (range(1, $widgetsPerContainer) as $widgetIndex) {
            $key = sprintf('%s-widget-%d-%d', $prefix, $containerIndex, $widgetIndex);

            Widget::factory()->create([
                'key' => $key,
                'meta' => [
                    'widget_variant' => 'default',
                    'widget_settings' => [
                        'spacing' => 'tight',
                        'signed_url' => 'https://example.test/admin/signed',
                    ],
                    'admin_schema' => ['secret' => true],
                ],
            ]);

            $widgetReferences[] = ['widget_key' => $key, 'occurrence' => 1];
            $totalWidgets++;
        }

        $containers[sprintf('%s-container-%d', $prefix, $containerIndex)] = [
            'label' => sprintf('Container %d', $containerIndex),
            'widgets' => $widgetReferences,
        ];
    }

    $layout = Layout::factory()->site($site)->create([
        'key' => $prefix . '-big-stress-layout',
        'containers' => $containers,
    ]);

    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();

    return [
        'containerCount' => $containerCount,
        'language' => $language,
        'layout' => $layout,
        'page' => $page,
        'site' => $site,
        'totalWidgets' => $totalWidgets,
    ];
}
