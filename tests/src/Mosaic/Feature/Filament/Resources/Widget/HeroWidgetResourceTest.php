<?php

declare(strict_types=1);

namespace Capell\Tests\Mosaic\Hero\Feature\Filament;

use Capell\Core\Models\Type;
use Capell\Mosaic\Actions\CreateHeroWidgetAction;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Enums\WidgetComponentEnum;
use Capell\Mosaic\Enums\WidgetTypeEnum;
use Capell\Mosaic\Models\Filament\Resources\Widgets\Pages\CreateWidget;
use Capell\Mosaic\Models\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\Mosaic\Models\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Pest\Expectation;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('creates a hero widget using the action', function (): void {
    $widget = CreateHeroWidgetAction::run();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->key->toBe('hero')
        ->meta->scoped(fn (Expectation $meta) => $meta->component->toBe(WidgetComponentEnum::Hero->value));
});

it('create hero widget', function (): void {
    $type = Type::query()->firstOrCreate([
        'key' => WidgetTypeEnum::Assets,
        'type' => LayoutTypeEnum::Widget,
    ], [
        'name' => 'Assets Widget',
    ]);

    livewire(CreateWidget::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => 'Hero Widget',
            'key' => 'hero',
            'type_id' => $type->id,
            'meta' => [
                'component' => WidgetComponentEnum::Hero,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $widget = Widget::query()->where('key', 'hero')->first();

    expect($widget)
        ->toBeInstanceOf(Widget::class)
        ->name->toBe('Hero Widget')
        ->key->toBe('hero')
        ->meta->scoped(fn (Expectation $meta) => $meta->component->toBe(WidgetComponentEnum::Hero->value));
});

it('edits the hero widget via Filament', function (): void {
    $widget = CreateHeroWidgetAction::run();
    $newName = 'Updated Hero Widget';

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => $newName,
            'key' => $widget->key,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($widget->refresh())
        ->name->toBe($newName)
        ->meta->scoped(fn (Expectation $meta) => $meta->component->toBe(WidgetComponentEnum::Hero->value));
});

it('validates edit hero widget', function (): void {
    $widget = CreateHeroWidgetAction::run();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => '',
            'key' => '',
        ])
        ->call('save')
        ->assertHasAllFormErrors([
            'name' => 'required',
            'key' => 'required',
        ]);
});
