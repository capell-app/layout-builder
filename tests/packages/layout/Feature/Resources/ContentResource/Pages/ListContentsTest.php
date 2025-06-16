<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Layout\Filament\Resources\ContentResource\Pages\ListContents;
use Capell\Layout\Models\Content;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('content');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list contents', function (): void {
    $contents = Content::factory()->count(5)->create();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($contents);
});

test('can search contents', function (): void {
    $contents = Content::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Language(%d)', $sequence->index)])
        ->count(3)
        ->create();

    $name = $contents->random()->name;

    livewire(ListContents::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCountTableRecords($contents->where('name', $name)->count())
        ->assertCanSeeTableRecords($contents->where('name', $name))
        ->assertCanNotSeeTableRecords($contents->where('name', '!=', $name));
});

test('can sort contents', function (): void {
    $contents = Content::factory()->count(5)->create();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->sortTable('name')
        ->assertCanSeeTableRecords($contents->sortBy('name'), inOrder: true);
});

test('can replicate contents', function (): void {
    $content = Content::factory()->create();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callTableAction(
            ReplicateAction::class,
            record: $content,
            data: [
                'name' => $content->name.' (copy)',
            ]
        )
        ->assertHasNoTableActionErrors()
        ->assertCountTableRecords(2);

    assertDatabaseHas('contents', [
        'name' => $content->name.' (copy)',
    ]);
});

test('can delete content', function (): void {
    $content = Content::factory()->create();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callTableAction(DeleteAction::class, $content)
        ->assertHasNoTableActionErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($content, ['id' => $content->id]);
});

test('can group delete contents', function (): void {
    $contents = Content::factory()->count(5)->create();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->callTableBulkAction(DeleteBulkAction::class, $contents)
        ->assertHasNoTableActionErrors();

    foreach ($contents as $content) {
        assertSoftDeleted($content, ['id' => $content->id]);
    }
});

test('can select all records', function (): void {
    livewire(ListContents::class)
        ->assertSuccessful()
        ->call('getAllSelectableTableRecordKeys')
        ->assertSuccessful();
});

test('can create content', function (): void {
    $newData = Content::factory()->make();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => $newData->name,
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas(Content::class, [
        'name' => $newData->name,
    ]);
});
