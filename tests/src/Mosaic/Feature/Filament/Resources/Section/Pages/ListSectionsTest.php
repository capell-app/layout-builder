<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Filament\Resources\Sections\Pages\ListSections;
use Capell\Mosaic\Models\Section;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
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
    $contents = Section::factory()->count(5)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($contents);
});

test('can search contents', function (): void {
    $contents = Section::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Language(%d)', $sequence->index)])
        ->count(3)
        ->create();

    $name = $contents->random()->name;

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCountTableRecords($contents->where('name', $name)->count())
        ->assertCanSeeTableRecords($contents->where('name', $name))
        ->assertCanNotSeeTableRecords($contents->where('name', '!=', $name));
});

test('can sort contents', function (): void {
    $contents = Section::factory()->count(5)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->sortTable('name')
        ->assertCanSeeTableRecords($contents->sortBy('name'), inOrder: true);
});

test('can replicate contents', function (): void {
    $content = Section::factory()->create();

    livewire(ListSections::class)
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
    $content = Section::factory()->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callAction(TestAction::make(DeleteAction::class)->table($content))
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($content, ['id' => $content->id]);
});

test('can group delete contents', function (): void {
    $contents = Section::factory()->count(5)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->selectTableRecords($contents->pluck('id')->toArray())
        ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->assertHasNoFormErrors();

    foreach ($contents as $content) {
        assertSoftDeleted($content, ['id' => $content->id]);
    }
});

test('can select all records', function (): void {
    livewire(ListSections::class)
        ->assertSuccessful()
        ->call('getAllSelectableTableRecordKeys')
        ->assertSuccessful();
});

test('can create content', function (): void {
    Type::factory()->type(LayoutTypeEnum::Section)->create();

    $newData = Section::factory()->make();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->callAction('create', [
            'name' => $newData->name,
        ])
        ->assertHasNoFormErrors();

    assertDatabaseHas(Section::class, [
        'name' => $newData->name,
    ]);
});

test('can filter by parent', function (): void {
    $parent = Section::factory()->create();
    $children = Section::factory()->count(3)->parent($parent)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->filterTable('filter', ['parent_id' => $parent->id])
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($children);
});

test('can filter by type', function (): void {
    $type = Type::factory()->type(LayoutTypeEnum::Section)->create();
    $contents = Section::factory()->count(3)->type($type)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->filterTable('type_id', $type->id)
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($contents);
});

test('can filter by site', function (): void {
    $site = Site::factory()->create();
    $contents = Section::factory()->count(3)->site($site)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->filterTable('site_id', $site->id)
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($contents);
});

test('can filter by language', function (): void {
    $language = Language::factory()->create();
    Section::factory()->create();
    $contents = Section::factory()->count(3)->withTranslations($language)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->filterTable('filter', ['language_id' => $language->id])
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($contents);
});

test('can filter by publish status', function (string $status, int $expectedCount): void {
    $publishedContents = Section::factory()->count(2)->published()->create();
    $pendingContents = Section::factory()->count(3)->pending()->create();
    $expiredContents = Section::factory()->count(4)->expired()->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(9)
        ->filterTable('publish_status', $status)
        ->assertCountTableRecords($expectedCount)
        ->assertCanSeeTableRecords(match ($status) {
            'published' => $publishedContents,
            'unpublished' => $pendingContents,
            'expired' => $expiredContents,
        })
        ->assertCanNotSeeTableRecords(match ($status) {
            'published' => [...$pendingContents->all(), ...$expiredContents->all()],
            'unpublished' => [...$publishedContents->all(), ...$expiredContents->all()],
            'expired' => [...$publishedContents->all(), ...$pendingContents->all()],
        });
})
    ->with([
        'published' => ['published', 2],
        'unpublished' => ['unpublished', 3],
        'expired' => ['expired', 4],
    ]);
