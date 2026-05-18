<?php

declare(strict_types=1);

use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\RenderableTypeEnum;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\Frontend\Data\MainContentRenderHookData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Tests\Fixtures\View\Components\PackageAlert;
use Illuminate\Support\Facades\Blade;

beforeEach(function (): void {
    CapellLayoutManager::clearContainerElements();
    Blade::component(PackageAlert::class, 'capell::element.default');
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(
        key: 'capell.element.default',
        type: RenderableTypeEnum::Element,
        blade: 'capell::element.default',
    ));
});

afterEach(function (): void {
    CapellLayoutManager::clearContainerElements();
});

it('registers the shared main content render hook', function (): void {
    /** @var RenderHookRegistry $registry */
    $registry = resolve(RenderHookRegistry::class);

    expect($registry->get(RenderHookLocation::MainContent))->not->toBeEmpty();
});

it('returns no output when no layout containers are available', function (): void {
    /** @var RenderHookRegistry $registry */
    $registry = resolve(RenderHookRegistry::class);

    $output = $registry->renderAll(
        RenderHookLocation::MainContent,
        new MainContentRenderHookData(
            layout: (object) ['containers' => null],
            page: null,
        ),
        scenario: 'frontend-main-layout',
        target: 'capell::layout.main',
    );

    expect($output)->toBe('');
});

it('renders stored layout containers through the shared hook and updates render state', function (): void {
    /** @var RenderHookRegistry $registry */
    $registry = resolve(RenderHookRegistry::class);

    $pageContentElement = new Element([
        'key' => 'page-content',
        'meta' => [],
    ]);
    $slotElement = new Element([
        'key' => 'slot',
        'meta' => ['type' => 'slot'],
    ]);

    CapellLayoutManager::storeContainerElement('main', 'page-content', $pageContentElement);
    CapellLayoutManager::storeContainerElement('sidebar', 'slot', $slotElement);

    $context = new MainContentRenderHookData(
        layout: (object) [
            'containers' => [
                'main' => [
                    'elements' => [
                        ['element_key' => 'page-content', 'occurrence' => 1],
                    ],
                    'meta' => ['colspan' => 8, 'container' => 'full'],
                ],
                'sidebar' => [
                    'elements' => [
                        ['element_key' => 'slot', 'occurrence' => 1],
                    ],
                    'meta' => ['colspan' => 4, 'container' => 'full'],
                ],
            ],
        ],
        page: null,
        pageSlot: '<p>Deferred slot</p>',
    );

    $output = $registry->renderAll(
        RenderHookLocation::MainContent,
        $context,
        scenario: 'frontend-main-layout',
        target: 'capell::layout.main',
    );

    expect($output)->toContain('id="layout-container-main"')
        ->and($output)->toContain('id="layout-container-sidebar"')
        ->and($context->pageContentElementRendered)->toBeTrue()
        ->and($context->slotRendered)->toBeTrue();
});

it('owns the main content layout rendering views', function (): void {
    $basePath = dirname(__DIR__, 2);
    $registrar = file_get_contents($basePath . '/src/Support/LayoutBuilderCoreRegistrar.php');
    $mainContent = file_get_contents($basePath . '/resources/views/components/layout/main-content.blade.php');
    $area = file_get_contents($basePath . '/resources/views/components/layout/area.blade.php');

    expect($registrar)->toContain('RegisterMainContentLayoutHook')
        ->and($mainContent)->toContain('ElementIsSlotAction')
        ->and($mainContent)->toContain('ResolveLayoutAreaContainersAction::run')
        ->and($mainContent)->toContain('LayoutAreaRegistry::MAIN')
        ->and($mainContent)->toContain('CapellLayoutManager::getStoredContainerElement')
        ->and($mainContent)->toContain('capell-layout-builder::components.layout.container')
        ->and($area)->toContain('ResolveLayoutAreaContainersAction::run')
        ->and($area)->toContain('CapellLayoutManager::getStoredContainerElement')
        ->and($area)->not->toContain('::query()')
        ->and($area)->not->toContain('DB::')
        ->and($area)->not->toContain('signed');
});
