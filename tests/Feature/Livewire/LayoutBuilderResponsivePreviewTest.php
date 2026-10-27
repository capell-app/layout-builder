<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\RenderedHtml;
use Livewire\Livewire;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('persists responsive container overrides separately from base colspan from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['colspan' => 12]],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', LayoutBreakpoint::Mobile->value)
        ->call('resizeContainer', 'main', 6)
        ->assertSet('containers.main.meta.colspan', 12)
        ->assertSet('containers.main.meta.responsive.mobile.colspan', 6);

    responsivePreviewLayoutBuilder($component)->saveLayout();

    expect($layout->fresh()->containers['main']['meta']['responsive']['mobile']['colspan'])->toBe(6);
});

function responsivePreviewLayoutBuilder(mixed $component): LayoutBuilder
{
    if (! is_object($component) || ! method_exists($component, 'instance')) {
        throw new RuntimeException('Expected a Livewire test component.');
    }

    $instance = $component->instance();

    if (! $instance instanceof LayoutBuilder) {
        throw new RuntimeException('Expected a layout builder component.');
    }

    return $instance;
}

it('keeps the layout builder workspace and shadow preview dark with the admin theme', function (): void {
    // Required hooks and values protect the visual contract independently of formatting.
    $visualEditorBlade = file_get_contents(dirname(__DIR__, 3) . '/resources/views/livewire/filament/layout-builder/visual-editor.blade.php')
        . file_get_contents(dirname(__DIR__, 3) . '/resources/views/livewire/filament/layout-builder/visual-editor-markup.blade.php');
    $adminStyles = (string) file_get_contents(dirname(__DIR__, 3) . '/resources/css/layout-builder/admin/capell-layout-filament.css');

    expect($adminStyles)
        ->toMatch('/\.dark\s+\.layout-builder-visual-canvas\s*\{\s*background\s*:\s*rgb\(\s*15\s*,\s*23\s*,\s*42\s*\);\s*\}/')
        ->toMatch('/\.dark\s+\.layout-builder-shadow-preview\s*\{\s*background\s*:\s*rgb\(\s*17\s*,\s*24\s*,\s*39\s*\);/')
        ->toContain('.layout-builder-preview-context')
        ->toMatch('/@container\s*\(\s*max\-width\s*:\s*72rem\s*\)\s*/')
        ->toMatch('/min\-width\s*:\s*5\.75rem/')
        ->toMatch('/\.layout-builder-breakpoint-button\s*\{\s*min-width: 5\.75rem;\s*width: auto;\s*height: var\(--layout-builder-hit-target\);\s*padding-inline: 0\.625rem;\s*\}/')
        ->toMatch('/padding\-bottom\s*:\s*4\.5rem/');

    expect($visualEditorBlade)
        ->toMatch('/\s*:\s*host\-context\s*\(\s*\.dark\s*\)\s*\.clb\-preview\-page\s*\{\s*background\s*:\s*\#111827\s*;\s*color\s*:\s*\#f8fafc\s*;\s*\}\s*/')
        ->toMatch('/\s*:\s*host\-context\s*\(\s*\.dark\s*\)\s*\.clb\-preview\-container\s*\{\s*background\s*:\s*\#111827\s*;\s*/')
        ->toMatch('/\s*:\s*host\-context\s*\(\s*\.dark\s*\)\s*\.clb\-preview\-widget\s*\{\s*background\s*:\s*\#1e293b\s*;\s*/')
        ->toMatch('/\s*:\s*host\-context\s*\(\s*\.dark\s*\)\s*a\s*\{\s*color\s*:\s*\#7dd3fc\s*;\s*text\-decoration\-color\s*:\s*rgba\s*\(\s*125\s*,\s*211\s*,\s*252\s*,\s*\.56\s*\)\s*;\s*\}\s*/')
        ->toMatch('/\s*:\s*host\-context\s*\(\s*\.dark\s*\)\s*\.clb\-preview\-empty\s*\{\s*background\s*:\s*rgba\s*\(\s*255\s*,\s*255\s*,\s*255\s*,\s*\.06\s*\)\s*;\s*color\s*:\s*\#cbd5e1\s*;\s*\}\s*/');

    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');
    $layout = Layout::factory()->create(['containers' => ['main' => ['widgets' => []]]]);
    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout]);
    $component->call('insertContainerAtPosition', 0)->assertHasNoErrors();
    $document = new RenderedHtml($component->html());

    expect($document->count('//*[contains(concat(" ", normalize-space(@class), " "), " layout-builder-editor-status-unsaved ")]/following::*[contains(concat(" ", normalize-space(@class), " "), " layout-builder-visual-actions ")]'))->toBe(1);

});

it('can resize the active responsive preview without waiting for breakpoint rerender from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['colspan' => 12]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('resizeContainer', 'main', 6, LayoutBreakpoint::Mobile->value)
        ->assertSet('containers.main.meta.colspan', 12)
        ->assertSet('containers.main.meta.responsive.mobile.colspan', 6);
});

it('renders responsive preview switching as an alpine interaction from the package namespace', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');

    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'widgets' => [],
            'meta' => [
                'colspan' => 8,
                'responsive' => [
                    'mobile' => ['colspan' => 12],
                    'tablet' => ['colspan' => 6],
                ],
            ],
        ],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertElementExists('.layout-builder-visual-toolbar')
        ->assertElementExists('.layout-builder-command-group')
        ->assertElementExists('.layout-builder-command-save')
        ->assertElementExists('[data-layout-builder-surface="breakpoint-controls"]')
        ->assertElementExists('[data-layout-builder-surface="editor-mode"]')
        ->assertElementExists('[data-layout-builder-surface="breakpoint-controls"][role="group"]')
        ->assertElementExists('[data-layout-builder-action="preview-tablet"]')
        ->assertElementExists('[x-bind\\:data-inspector-open]')
        ->assertElementExists('.layout-builder-history-actions')
        ->assertElementExists('[data-layout-builder-surface="history-actions"]')
        ->assertElementExists('[x-ref="treeToggle"]')
        ->assertElementExists('.layout-builder-tree-collapse-button')
        ->assertElementExists('.layout-builder-inspector-panel')
        ->assertElementExists('#layout-builder-inspector-title')
        ->assertElementExists('.layout-builder-inspector-actions-grid')
        ->assertElementExists('[x-show="selectedPreviewAction()"]')
        ->assertElementExists('[data-layout-builder-surface="inspector-backdrop"]')
        ->assertElementExists('[data-layout-builder-surface="structure-drawer"]')
        ->assertElementExists('.layout-builder-tree-header-actions')
        ->assertElementExists('.layout-builder-tree-search-clear')
        ->assertElementExists('.layout-builder-tree-search-meta')
        ->assertElementExists('.layout-builder-tree-search-empty')
        ->assertElementExists('[data-match-frontend-container-layout="true"]')
        ->assertElementExists('[x-bind\\:data-active-breakpoint]')
        ->assertElementExists('[x-bind\\:data-layout-builder-breakpoint]')
        ->assertElementExists('[data-layout-builder-surface="preview-canvas"]')
        ->assertElementExists('[x-ref="previewCanvas"]')
        ->assertElementExists('.layout-builder-canvas-scroll')
        ->assertElementExists('[x-bind\\:aria-pressed]')
        ->assertDontSeeHtml('requestPreviewRefresh')
        ->assertDontSeeHtml('layout-builder-preview-status-row')
        ->assertDontSeeHtml('layout-builder-preview-command-label')
        ->assertDontSeeHtml('capell-layout-builder::generic.preview');

    expect(new RenderedHtml($component->html())->count('//*[starts-with(@*[name()="wire:click"], "setActiveBreakpoint")]'))->toBe(0);

    $outcomes = responsiveEditorOutcomes($component->__get('effects'), <<<'JS_WRAP'
    (async () => {
    const editor = window.capellLayoutBuilderVisualEditor({breakpointWidths: {desktop: '100%', tablet: '768px', mobile: '390px'}});
    const properties = {};
    const host = {dataset: {}, style: {}};
    let focused = null;
    const trigger = new HTMLElement();
    trigger.isConnected = true;
    trigger.focus = () => { focused = 'trigger'; };
    editor.$refs = {previewHost: host, previewCanvas: {style: {setProperty(name, value) { properties[name] = value; }}}};
    editor.$el = {offsetWidth: 1440, querySelectorAll() { return []; }, querySelector() { return null; }};
    editor.$nextTick = callback => callback();
    const breakpoints = [];
    for (const breakpoint of ['desktop', 'tablet', 'mobile']) {
        editor.setActiveBreakpointPreview(breakpoint);
        breakpoints.push({breakpoint: host.dataset.activeBreakpoint, width: host.style.maxWidth, minimum: host.style.minWidth, stack: editor.shouldStackContainersForActiveBreakpoint(), canvasWidth: properties['--layout-builder-preview-max-width']});
    }
    editor.selectPreviewNode('widget:main:0', trigger);
    const selected = editor.selectedNode;
    editor.handleEscape();
    const selectionCleared = editor.selectedNode === null;
    editor.selectedNode = 'widget:main:0';
    editor.$el.offsetWidth = 1000;
    editor.syncPanelLayout();
    const compact = {enabled: editor.compactPanels, selectionCleared: editor.selectedNode === null};
    editor.openTree(trigger);
    const open = editor.treeOpen;
    editor.handleEscape();
    editor.$refs.previewHost = null;
    const widgets = ['Original'];
    const mountedActions = [];
    const triggerState = {};
    const actionTrigger = new HTMLElement();
    actionTrigger.setAttribute = (name, value) => { triggerState[name] = value; };
    actionTrigger.removeAttribute = name => { delete triggerState[name]; };
    actionTrigger.classList = {toggle() {}};
    actionTrigger.toggleAttribute = (name, enabled) => { triggerState[name] = enabled; };
    editor.$wire = {
        async $call(action, containerKey, widgetIndex) {
            if (action === 'duplicateWidget') widgets.push(widgets[widgetIndex]);
            if (action === 'removeWidget') widgets.splice(widgetIndex, 1);
        },
        async mountAction(name, args) { mountedActions.push({name, args}); },
    };
    await editor.runPreviewAction('duplicateWidget', {containerKey: 'main', widgetIndex: 0}, {}, actionTrigger);
    const duplicated = [...widgets];
    await editor.runPreviewAction('removeWidget', {containerKey: 'main', widgetIndex: 0}, {}, actionTrigger);
    await editor.runPreviewAction('addWidget', {containerKey: 'main', position: 1}, {type: 'page'}, actionTrigger);
    console.log(JSON.stringify({breakpoints, selected, selectionCleared, compact, open, closed: !editor.treeOpen, focused, duplicated, remaining: widgets, action: mountedActions[1], duplicateAction: mountedActions[0], loading: editor.actionLoading, busy: triggerState['aria-busy'], disabled: triggerState.disabled}));
    })().catch(error => { console.error(error); process.exitCode = 1; });
    JS_WRAP);
    expect($outcomes)->toBe([
        'breakpoints' => [
            ['breakpoint' => 'desktop', 'width' => '100%', 'minimum' => '0', 'stack' => false, 'canvasWidth' => '100%'],
            ['breakpoint' => 'tablet', 'width' => '768px', 'minimum' => '0', 'stack' => true, 'canvasWidth' => '768px'],
            ['breakpoint' => 'mobile', 'width' => '390px', 'minimum' => '0', 'stack' => true, 'canvasWidth' => '390px'],
        ],
        'selected' => 'widget:main:0', 'selectionCleared' => true,
        'compact' => ['enabled' => true, 'selectionCleared' => true],
        'open' => true, 'closed' => true, 'focused' => 'trigger',
        'duplicated' => ['Original'], 'remaining' => [],
        'action' => ['name' => 'addWidget', 'args' => ['containerKey' => 'main', 'position' => 1, 'type' => 'page']],
        'duplicateAction' => ['name' => 'duplicateWidget', 'args' => ['containerKey' => 'main', 'widgetIndex' => 0]],
        'loading' => false, 'busy' => 'false', 'disabled' => false,
    ]);
});

it('groups the visual preview into main sidebar and custom area regions from the package namespace', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['area' => 'main', 'name' => 'Main', 'colspan' => 8]],
        'sidebar' => ['widgets' => [], 'meta' => ['area' => 'sidebar', 'name' => 'Sidebar', 'colspan' => 4]],
        'latest' => ['widgets' => [], 'meta' => ['area' => 'latest', 'name' => 'Latest', 'colspan' => 12]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSeeHtml('clb-preview-content-layout-with-sidebar')
        ->assertSeeHtml('data-clb-preview-area="main"')
        ->assertSeeHtml('data-clb-preview-area="sidebar"')
        ->assertSeeHtml('data-clb-preview-area="latest"')
        ->assertSeeHtml('data-clb-preview-container-list')
        ->assertSeeHtml('data-clb-preview-container-position="0"')
        ->assertSeeHtml('data-clb-preview-container-position="1"')
        ->assertSeeHtml('data-clb-preview-container-position="2"')
        ->assertSeeHtml('clb-preview-region-main')
        ->assertSeeHtml('clb-preview-region-sidebar')
        ->assertSeeHtml('clb-preview-region-area');
});

it('hydrates inspector metadata for selected containers and widgets from the package namespace', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');

    $widget = Widget::factory()->create(['key' => 'hero', 'name' => 'Hero banner']);
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'widgets' => [
                ['widget_key' => $widget->key, 'occurrence' => 1],
            ],
            'meta' => ['area' => 'main', 'name' => 'Main', 'colspan' => 8],
        ],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSeeHtml('previewContainerActionsPayload')
        ->assertSeeHtml('previewWidgetActionsPayload')
        ->assertSeeHtml('areaLabel')
        ->assertSeeHtml('widgetCountLabel')
        ->assertSeeHtml('colspanLabel')
        ->assertSeeHtml('containerLabel')
        ->assertSeeHtml('assetCountLabel')
        ->assertSeeHtml(__('capell-layout-builder::generic.two_thirds'))
        ->assertSeeHtml(trans_choice('capell-layout-builder::message.layout_tree_widget_count', 1, ['count' => 1]))
        ->assertSeeHtml(trans_choice('capell-layout-builder::message.layout_tree_asset_count', 0, ['count' => 0]));
});

it('can opt out of frontend container stacking in the admin preview from the package namespace', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');
    config()->set('capell-layout-builder.preview.match_frontend_container_layout', false);

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['colspan' => 12]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertElementExists('[data-match-frontend-container-layout="false"]');
});

it('resets a responsive override to the base colspan fallback from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => [
            'widgets' => [],
            'meta' => [
                'colspan' => 12,
                'responsive' => ['mobile' => ['colspan' => 6]],
            ],
        ],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', 'mobile')
        ->call('resetResponsiveContainerOverride', 'main')
        ->assertSet('containers.main.meta.responsive', []);
});

/** @return array<string, mixed> */
function responsiveEditorOutcomes(mixed $effects, string $scenario): array
{
    $node = (new ExecutableFinder)->find('node');
    if ($node === null || ! is_dir(dirname(__DIR__, 5) . '/node_modules')) {
        TestCase::markTestSkipped('Visual editor interactions require the optional Node tooling.');
    }

    $scripts = data_get($effects, 'scripts', []);
    throw_unless(is_array($scripts), RuntimeException::class, 'Expected the rendered Livewire scripts.');
    $source = '';
    foreach ($scripts as $script) {
        throw_unless(is_string($script), RuntimeException::class, 'Expected a rendered script.');
        preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $script, $matches);
        $source .= implode("\n", $matches[1]);
    }

    $process = new Process([$node, '-e', 'global.window = {requestAnimationFrame: callback => callback(), setTimeout: callback => callback()}; global.document = {activeElement: null}; global.HTMLElement = class {}; ' . $source . "\n" . $scenario], dirname(__DIR__, 5));
    $process->setTimeout(10)->run();
    expect($process->getExitCode())->toBe(0, $process->getErrorOutput());

    $outcomes = [];
    foreach (capell_json_array($process->getOutput()) as $key => $value) {
        throw_unless(is_string($key), RuntimeException::class, 'Expected named interaction outcomes.');
        $outcomes[$key] = $value;
    }

    return $outcomes;
}
