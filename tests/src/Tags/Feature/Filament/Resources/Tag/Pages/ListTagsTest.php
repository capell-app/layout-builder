<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Tags\Filament\Resources\Tags\Pages\ListTags;
use Capell\Tags\Models\Tag;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelMissing;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('tag');

beforeEach(function (): void {
    Language::factory()->default()->create();

    test()->actingAsAdmin();
});

test('can list tags', function (): void {
    $tags = Tag::factory()->count(5)->create();

    livewire(ListTags::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($tags);
});

test('can search tags', function (): void {
    $tags = Tag::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Language(%d)', $sequence->index)])
        ->count(3)
        ->create();

    $name = $tags->random()->name;

    livewire(ListTags::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords($tags->where('name', $name))
        ->assertCanNotSeeTableRecords($tags->where('name', '!=', $name));
});

test('can sort tags', function (): void {
    $tags = Tag::factory()->count(5)->create();

    livewire(ListTags::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->sortTable('name')
        ->assertCanSeeTableRecords($tags->sortBy('name'), inOrder: true);
});

test('can replicate tag', function (): void {
    $tag = Tag::factory()->create();

    $name = $tag->name . ' (copy)';
    $slug = str($name)->slug();

    livewire(ListTags::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(
            TestAction::make(ReplicateAction::class)->table($tag),
            data: [
                'name' => $name,
                'slug' => $slug,
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(2);

    assertDatabaseHas('tags', [
        'name' => json_encode(['en' => $name]),
        'slug' => json_encode(['en' => $slug]),
    ]);
});

test('can delete tag', function (): void {
    $tag = Tag::factory()->create();

    livewire(ListTags::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(TestAction::make(DeleteAction::class)->table($tag))
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    expect(fn () => $tag->refresh())->toThrow(ModelNotFoundException::class);
});

test('can group delete tags', function (): void {
    $tags = Tag::factory()->count(5)->create();

    livewire(ListTags::class)
        ->assertSuccessful()
        ->selectTableRecords($tags)
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors();

    foreach ($tags as $tag) {
        assertModelMissing($tag);
    }
});
