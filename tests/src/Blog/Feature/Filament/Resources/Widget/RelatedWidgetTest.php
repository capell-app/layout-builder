<?php

declare(strict_types=1);

use Capell\Blog\Support\Creator\BlogCreator;
use Capell\Core\Models\Type;
use Capell\Layout\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\Layout\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can edit related widget', function (): void {
    $typeCreator = new BlogCreator;
    $widget = $typeCreator->relatedArticlesWidget();

    $newData = Widget::factory()->make();

    Type::factory()->page()->state(['key' => 'home'])->create();

    livewire(EditWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->assertSchemaStateSet([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('key')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($widget->refresh())
        ->name->toBe($newData->name)
        ->key->toBe($newData->key);
});
