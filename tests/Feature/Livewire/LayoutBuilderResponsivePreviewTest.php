<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;
use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('persists responsive container overrides separately from base colspan from the package namespace', function (): void {
    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['colspan' => 12]],
    ]]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->call('setActiveBreakpoint', LayoutBreakpoint::Mobile->value)
        ->call('resizeContainer', 'main', 6)
        ->assertSet('containers.main.meta.colspan', 12)
        ->assertSet('containers.main.meta.responsive.mobile.colspan', 6)
        ->call('saveLayout');

    expect($layout->fresh()->containers['main']['meta']['responsive']['mobile']['colspan'])->toBe(6);
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

    Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSeeHtml('setActiveBreakpointPreview')
        ->assertSeeHtml('activeBreakpointMaxCanvasWidth')
        ->assertSeeHtml('activeBreakpointMinCanvasWidth')
        ->assertSeeHtml('syncPanelLayout')
        ->assertSeeHtml('syncPreviewPayload')
        ->assertSeeHtml('actionLoading')
        ->assertSeeHtml('selectNode')
        ->assertSeeHtml('applyPreviewBreakpoint')
        ->assertSeeHtml('dispatchPreviewAction')
        ->assertSeeHtml('afterLivewirePreviewMutation')
        ->assertSeeHtml('syncSelectedPreviewNode')
        ->assertSeeHtml('selectedPreviewMetaRows')
        ->assertSeeHtml('runSelectedPreviewAction')
        ->assertSeeHtml('markPreviewActionLoading')
        ->assertSeeHtml('callback(event.currentTarget)')
        ->assertSeeHtml('duplicateWidget')
        ->assertSeeHtml('removeWidget')
        ->assertSeeHtml('previewWidgetActionsPayload')
        ->assertSeeHtml('previewContainerActionsPayload')
        ->assertSeeHtml('is-loading')
        ->assertSeeHtml('clb-preview-insert-button.is-loading')
        ->assertSeeHtml('aria-busy')
        ->assertSeeHtml('--layout-builder-preview-max-width')
        ->assertSeeHtml('--layout-builder-preview-min-width')
        ->assertSeeHtml('--clb-preview-tablet-colspan: 6')
        ->assertSeeHtml('--clb-preview-mobile-colspan: 12')
        ->assertElementExists('.layout-builder-visual-toolbar')
        ->assertElementExists('.layout-builder-command-group')
        ->assertElementExists('.layout-builder-command-save')
        ->assertElementExists('.layout-builder-preview-command-label')
        ->assertElementExists('.layout-builder-history-actions')
        ->assertElementExists('.layout-builder-panel-collapse-toggle')
        ->assertElementExists('.layout-builder-inspector-panel')
        ->assertElementExists('.layout-builder-inspector-actions-grid')
        ->assertElementExists('.layout-builder-inspector-empty')
        ->assertElementExists('.layout-builder-tree-header-actions')
        ->assertElementExists('[data-match-frontend-container-layout="true"]')
        ->assertElementExists('[x-bind\\:data-active-breakpoint]')
        ->assertElementExists('[x-ref="previewCanvas"]')
        ->assertSeeHtml('shouldStackContainersForActiveBreakpoint')
        ->assertElementExists('.layout-builder-canvas-scroll')
        ->assertElementExists('[x-bind\\:aria-pressed]')
        ->assertDontSeeHtml('requestPreviewRefresh')
        ->assertDontSeeHtml('layout-builder-preview-status-row')
        ->assertDontSeeHtml('capell-layout-builder::generic.preview')
        ->assertElementExists(fn (AssertElement $body): BaseAssert => $body->doesntContain('[wire\\:click^="setActiveBreakpoint"]'));

    $visualEditorBlade = (string) file_get_contents(dirname(__DIR__, 3) . '/resources/views/livewire/filament/layout-builder/visual-editor.blade.php');

    expect($visualEditorBlade)
        ->toContain('callback(event.currentTarget)')
        ->toContain('(trigger) =>')
        ->toContain('treeCollapsed: true')
        ->toContain('markSelectedTreeNode()')
        ->toContain("selectPreviewNode(node) {\n                this.selectedNode = node")
        ->toContain('selectedPreviewMetaRows()')
        ->toContain('runSelectedPreviewAction(actionName, trigger = null)')
        ->toContain("this.\$wire.\$call(\n                            'duplicateWidget'")
        ->toContain("this.\$wire.\$call('refreshVisualPreview')")
        ->not->toContain('this.$wire.setActiveBreakpoint(this.activeBreakpoint)')
        ->not->toContain("selectPreviewNode(node) {\n                this.selectNode(node, () => this.\$wire.selectPreviewNode(node))");

    $previewActionTriggerPattern = static fn (string $actionName): string => sprintf(
        "/this\\.runPreviewAction\\(\\s*'%s',[\\s\\S]*?\\{\\},\\s*trigger,\\s*\\)/",
        preg_quote($actionName, '/'),
    );

    expect((bool) preg_match($previewActionTriggerPattern('addWidget'), $visualEditorBlade))
        ->toBeTrue();
    expect((bool) preg_match($previewActionTriggerPattern('addContainer'), $visualEditorBlade))
        ->toBeTrue();
});

it('groups the visual preview into main sidebar and custom area regions from the package namespace', function (): void {
    config()->set('capell-layout-builder.editor_mode.default', 'layout_first');

    $layout = Layout::factory()->create(['containers' => [
        'main' => ['widgets' => [], 'meta' => ['area' => 'main', 'name' => 'Main', 'colspan' => 8]],
        'sidebar' => ['widgets' => [], 'meta' => ['area' => 'sidebar', 'name' => 'Sidebar', 'colspan' => 4]],
        'latest' => ['widgets' => [], 'meta' => ['area' => 'latest', 'name' => 'Latest', 'colspan' => 12]],
    ]]);

    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSeeHtml('clb-preview-content-layout-with-sidebar')
        ->assertSeeHtml('data-clb-preview-area="main"')
        ->assertSeeHtml('data-clb-preview-area="sidebar"')
        ->assertSeeHtml('data-clb-preview-area="latest"')
        ->assertSeeHtml('data-clb-preview-container-list')
        ->assertSeeHtml('data-clb-preview-container-position="0"')
        ->assertSeeHtml('data-clb-preview-container-position="1"')
        ->assertSeeHtml('data-clb-preview-container-position="2"');

    expect($component->get('visualPreviewHtml'))
        ->toContain('clb-preview-region-main')
        ->toContain('clb-preview-region-sidebar')
        ->toContain('clb-preview-region-area');
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
        ->assertSeeHtml(__('capell-layout-builder::message.container_colspan_value', ['columns' => 8]))
        ->assertSeeHtml(trans_choice('capell-layout-builder::message.layout_tree_widget_count', 1, ['count' => 1]))
        ->assertSeeHtml(trans_choice('capell-layout-builder::message.layout_tree_asset_count', 0, ['count' => 0]));
});

it('can opt out of frontend container stacking in the admin preview from the package namespace', function (): void {
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
