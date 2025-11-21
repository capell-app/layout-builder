<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Core\Models\Type;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Resources\Contents\Pages\ListContents;
use Capell\Layout\Models\Content;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
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
        ->callAction(
            TestAction::make(ReplicateAction::class)->table($content),
            data: [
                'name' => $content->name . ' (copy)',
            ],
        )
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(2);

    assertDatabaseHas('contents', [
        'name' => $content->name . ' (copy)',
    ]);
});

test('can delete content', function (): void {
    $content = Content::factory()->create();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(TestAction::make(DeleteAction::class)->table($content))
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($content, ['id' => $content->id]);
});

test('can group delete contents', function (): void {
    $contents = Content::factory()->count(5)->create();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->selectTableRecords($contents->pluck('id')->toArray())
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors();

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
    Type::factory()->type(LayoutTypeEnum::Content)->create();

    $newData = Content::factory()->make();

    livewire(ListContents::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => $newData->name,
        ])
        ->assertHasNoFormErrors();

    assertDatabaseHas(Content::class, [
        'name' => $newData->name,
    ]);
});
