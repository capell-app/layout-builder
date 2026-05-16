<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('saves the selected container as a layout preset from the builder', function (): void {
    $site = Site::factory()->create();
    $element = Element::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => [
                'elements' => [
                    ['element_key' => $element->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('saveLayoutPreset', 'main', 'Reusable hero');

    $preset = LayoutPreset::query()->where('name', 'Reusable hero')->first();

    expect($preset)->not->toBeNull()
        ->and($preset->site_id)->toBe($site->getKey())
        ->and($preset->snapshot['containers'])->toHaveKey('main')
        ->and($preset->snapshot['containers']['main']['elements'][0]['element_key'])->toBe('hero');
});

it('inserts a layout preset into the builder state', function (): void {
    $site = Site::factory()->create();
    $element = Element::factory()->create(['key' => 'feature']);
    $layout = Layout::factory()->site($site)->create([
        'containers' => [
            'main' => ['elements' => []],
        ],
    ]);
    LayoutPreset::factory()->for($site, 'site')->create([
        'name' => 'Feature block',
        'key' => 'feature-block',
        'snapshot' => [
            'containers' => [
                'preset' => [
                    'elements' => [
                        ['element_key' => $element->key, 'occurrence' => 1],
                    ],
                ],
            ],
        ],
    ]);

    Livewire::test(LayoutBuilder::class, ['layout' => $layout, 'site' => $site])
        ->call('insertLayoutPreset', 'Feature block', 'main')
        ->assertSet('containers.preset-copy.elements.0.element_key', 'feature');
});
