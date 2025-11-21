<?php

declare(strict_types=1);

use Capell\Blog\Filament\Resources\Tags\Pages\EditTag;
use Capell\Blog\Models\Tag;
use Capell\Core\Models\Language;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('tag');

beforeEach(function (): void {
    Language::factory()->default()->create();

    test()->actingAsAdmin();
});

it('can retrieve data', function (): void {
    $tag = Tag::factory()->create();

    livewire(EditTag::class, [
        'record' => $tag->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'name' => $tag->name,
            'slug' => $tag->slug,
        ]);
});

it('can save', function (): void {
    $tag = Tag::factory()->create();
    $newData = Tag::factory()->make();

    livewire(EditTag::class, [
        'record' => $tag->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'slug' => $newData->slug,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tag->refresh())
        ->name->toBe($newData->name)
        ->slug->toBe($newData->slug);
});

test('validates edit tag', function (): void {
    $tag = Tag::factory()->create();

    livewire(EditTag::class, [
        'record' => $tag->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => null,
            'slug' => null,
        ])
        ->call('save')
        ->assertHasFormErrors([
            'name' => 'required',
            'slug' => 'required',
        ]);
});

it('can delete', function (): void {
    $tag = Tag::factory()->create();

    livewire(EditTag::class, [
        'record' => $tag->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction(DeleteAction::class)
        ->assertHasNoFormErrors();

    assertDatabaseMissing($tag->getTable(), [
        'id' => $tag->id,
    ]);
});
