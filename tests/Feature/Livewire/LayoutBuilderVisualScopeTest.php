<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Tests\Support\RenderedHtml;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

it('renders the visual editor inside a block ancestor that owns the Alpine factory scope after leaving the content editor', function (): void {
    test()->actingAsAdmin();

    $layout = Layout::factory()->create(['containers' => ['main' => ['widgets' => []]]]);
    $component = Livewire::test(LayoutBuilder::class, ['layout' => $layout])
        ->assertSet('editorMode', 'content_first');

    $initial = new RenderedHtml($component->html());

    expect($initial->count('//*[@data-layout-builder-surface="content-editor"]'))->toBe(1)
        ->and($initial->count('//*[@data-layout-builder-surface="visual-editor"]'))->toBe(0);

    $component->call('showAdvancedLayout')
        ->assertSet('editorMode', 'layout_first');

    $rendered = new RenderedHtml($component->html());
    $surface = '//*[@data-layout-builder-surface="visual-editor"]';
    $factoryScope = '//*[@*[name()="x-data" and contains(., "window.capellLayoutBuilderVisualEditor(")]]';
    $section = $rendered->element($surface);
    $scope = $rendered->element($surface . '/ancestor::*[@*[name()="x-data" and contains(., "window.capellLayoutBuilderVisualEditor(")]]');

    expect($rendered->count($surface))->toBe(1)
        ->and($rendered->count($factoryScope))->toBe(1)
        ->and($rendered->count('//*[@data-layout-builder-surface="content-editor"]'))->toBe(0)
        ->and($section->tagName)->toBe('section')
        ->and($section->hasAttribute('x-data'))->toBeFalse()
        ->and($section->getAttribute('x-bind:data-tree-collapsed'))->toBe("treeCollapsed ? 'true' : 'false'")
        ->and($section->getAttribute('x-bind:data-inspector-open'))->toBe("selectedPreviewAction() ? 'true' : 'false'")
        ->and($section->getAttribute('x-on:keydown.escape.window.prevent'))->toBe('handleEscape()')
        ->and($section->getAttribute('x-on:keydown.window'))->toBe('handleGlobalShortcut($event)')
        ->and($section->getAttribute('class'))->toContain('layout-builder-visual-editor')
        ->and($scope->tagName)->toBe('div')
        ->and($scope->hasAttribute('style'))->toBeFalse()
        ->and($scope->hasAttribute('class'))->toBeFalse();
});
