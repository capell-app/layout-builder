<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('saves the selected container as a layout preset from the builder', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('saveLayoutPreset', 'main', 'Reusable hero');

    $preset = LayoutPreset::query()->where('name', 'Reusable hero')->first();
    $preset = capell_test_instance($preset, LayoutPreset::class);

    $snapshot = capell_test_array($preset->snapshot);
    $containers = capell_test_array($snapshot['containers'] ?? null);
    $mainContainer = capell_test_array($containers['main'] ?? null);
    $mainWidgets = $mainContainer['widgets'] ?? [];

    throw_unless(is_array($mainWidgets), RuntimeException::class, 'Expected preset widgets.');

    expect($preset->site_id)->toBe($site->getKey())
        ->and($containers)->toHaveKey('main')
        ->and($mainWidgets[0]['widget_key'] ?? null)->toBe('hero');
});

it('inserts a layout preset into the builder state', function (): void {
    $site = Site::factory()->create();
    $widget = Widget::factory()->create(['key' => 'feature']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);
    LayoutPreset::factory()->for($site, 'site')->create([
        'name' => 'Feature widget',
        'key' => 'feature-widget',
        'snapshot' => [
            'containers' => [
                'preset' => [
                    'widgets' => [
                        ['widget_key' => $widget->key, 'occurrence' => 1],
                    ],
                ],
            ],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('insertLayoutPreset', 'Feature widget', 'main')
        ->assertSet('containers.preset-copy.widgets.0.widget_key', 'feature');
});

it('renders registered starter layout presets in the builder', function (): void {
    $site = Site::factory()->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->assertSee(__('capell-layout-builder::heading.starter_layouts'))
        ->assertSee('Landing page')
        ->assertSee('Sidebar, main, footer')
        ->assertSee(__('capell-layout-builder::button.apply_starter_layout'));
});

it('applies a registered starter layout preset to the full builder state', function (): void {
    $site = Site::factory()->create();
    Widget::factory()->create(['key' => 'old-widget']);
    Widget::factory()->create(['key' => 'hero']);
    Widget::factory()->create(['key' => 'proof']);
    Widget::factory()->create(['key' => 'features']);
    Widget::factory()->create(['key' => 'cta']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'old-widget', 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('applyStarterLayoutPreset', 'landing')
        ->assertSet('containers.main.widgets.0.widget_key', 'hero')
        ->assertSet('containers.main.widgets.1.widget_key', 'proof')
        ->assertSet('containers.main.widgets.2.widget_key', 'features')
        ->assertSet('containers.main.widgets.3.widget_key', 'cta')
        ->assertSet('assets.main.0', [])
        ->assertSet('selectedRecords.main.3', []);
});
