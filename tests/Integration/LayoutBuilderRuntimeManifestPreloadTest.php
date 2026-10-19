<?php

declare(strict_types=1);

use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\RenderableTypeEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBuilderRuntimeManifestContributor;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Livewire;

use function PHPUnit\Framework\assertInstanceOf;

beforeEach(function (): void {
    Livewire::component('runtime-preload-widget', LayoutBuilderRuntimePreloadWidget::class);
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(
        key: 'runtime-preload-widget',
        type: RenderableTypeEnum::LayoutWidget,
        blade: 'capell::widget.default',
        livewire: 'runtime-preload-widget',
    ));
});

it('reuses fully preloaded widgets and their blueprint runtime flags without queries', function (bool $livewire): void {
    $blueprint = Blueprint::factory()->type(LayoutTypeEnum::Widget->value)->create(['is_livewire' => $livewire]);
    $widget = Widget::factory()->create([
        'blueprint_id' => $blueprint->getKey(),
        'component' => 'runtime-preload-widget',
        'is_livewire' => null,
        'meta' => null,
    ]);
    $layout = Layout::factory()->create([
        'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key]]]],
    ]);

    resolve(LayoutLoader::class)->preloadLayoutWidgets($layout, Language::factory()->create(), null);
    $widgets = $layout->getRelation('layoutWidgets');
    assertInstanceOf(Collection::class, $widgets);
    expect($widgets)->toHaveCount(1)
        ->and($widgets->first())->toBeInstanceOf(Widget::class);

    [$manifest, $queryCount] = layoutBuilderContributeWithQueryCount($layout);

    expect($queryCount)->toBe(0)
        ->and($layout->getRelation('layoutWidgets'))->toBe($widgets)
        ->and($manifest->usesAlpine)->toBeTrue()
        ->and($manifest->usesLivewire)->toBe($livewire)
        ->and($manifest->usesIslands)->toBe($livewire)
        ->and($manifest->modules['layout-builder'])->toBeTrue();
})->with(['blade blueprint' => false, 'livewire blueprint' => true]);

it('refreshes preloaded widgets when the same layout composition changes', function (string $initial, bool $scoped): void {
    $language = Language::factory()->create();
    $first = Widget::factory()->create(['key' => 'before-composition-change']);
    $second = Widget::factory()->create(['key' => 'after-composition-change']);
    $layout = Layout::factory()->create([
        'containers' => $initial === 'empty' ? null : ['main' => ['widgets' => [['widget_key' => $first->key, 'occurrence' => 1]]]],
    ]);
    $loader = resolve(LayoutLoader::class);
    $containerKeys = $scoped ? ['main'] : null;
    $loader->preloadLayoutWidgets($layout, $language, null, $containerKeys);
    $layout->update(['containers' => ['main' => ['widgets' => [['widget_key' => $second->key, 'occurrence' => 2]]]]]);

    $resolved = $loader->getLayoutWidget($layout, $second->key, $language, null, 'main', 2, $containerKeys);
    expect($resolved?->getKey())->toBe($second->getKey())
        ->and($loader->getLayoutWidget($layout, $first->key, $language, null, 'main', 1, $containerKeys))->toBeNull();
    $freshLayout = $layout->fresh();
    assertInstanceOf(Layout::class, $freshLayout);
    $connection = DB::connection();
    $wasLogging = $connection->logging();
    $connection->flushQueryLog();
    $connection->enableQueryLog();
    try {
        $reused = $loader->getLayoutWidget($freshLayout, $second->key, $language, null, 'main', 2, $containerKeys);
        expect($reused?->getKey())->toBe($second->getKey())
            ->and($connection->getQueryLog())->toBeEmpty();
    } finally {
        if (! $wasLogging) {
            $connection->disableQueryLog();
        }

        $connection->flushQueryLog();
    }
})->with([['empty', false], ['replacement', false], ['empty', true], ['replacement', true]]);

it('retains the widget query when the preload is unavailable or incomplete', function (string $preload): void {
    $blade = Widget::factory()->create(['is_livewire' => false]);
    $livewire = Widget::factory()->create(['is_livewire' => true, 'component' => 'runtime-preload-widget']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['widgets' => [['widget_key' => $blade->key]]],
            'aside' => ['widgets' => [['widget_key' => $livewire->key]]],
        ],
    ]);

    if ($preload !== 'unloaded') {
        if ($preload === 'unavailable widget') {
            $livewire->update(['status' => false]);
        }

        resolve(LayoutLoader::class)->preloadLayoutWidgets(
            $layout,
            Language::factory()->create(),
            null,
            $preload === 'partial container' ? ['main'] : null,
        );

        if ($preload === 'unhydrated blueprint') {
            $widgets = $layout->getRelation('layoutWidgets');
            assertInstanceOf(Collection::class, $widgets);
            foreach ($widgets as $widget) {
                assertInstanceOf(Widget::class, $widget);
                $widget->unsetRelation('blueprint');
            }
        }
    }

    [$manifest, $queryCount] = layoutBuilderContributeWithQueryCount($layout);

    expect($queryCount)->toBe(2)
        ->and($manifest->usesAlpine)->toBeTrue()
        ->and($manifest->usesLivewire)->toBe($preload !== 'unavailable widget')
        ->and($manifest->usesIslands)->toBe($preload !== 'unavailable widget')
        ->and($manifest->modules['layout-builder'])->toBeTrue();
})->with(['unloaded', 'partial container', 'unhydrated blueprint', 'unavailable widget']);

/**
 * @return array{FrontendRuntimeManifestData, int}
 */
function layoutBuilderContributeWithQueryCount(Layout $layout): array
{
    $context = Mockery::mock(FrontendContextReader::class);
    $context->shouldReceive('layout')->once()->andReturn($layout);
    $manifest = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);
    $connection = DB::connection();
    $wasLogging = $connection->logging();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    try {
        resolve(LayoutBuilderRuntimeManifestContributor::class)->contribute($context, $manifest);
        $queryCount = count($connection->getQueryLog());
    } finally {
        if (! $wasLogging) {
            $connection->disableQueryLog();
        }

        $connection->flushQueryLog();
    }

    return [$manifest, $queryCount];
}

final class LayoutBuilderRuntimePreloadWidget extends Component {}
