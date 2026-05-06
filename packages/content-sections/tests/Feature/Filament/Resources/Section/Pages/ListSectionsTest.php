<?php

declare(strict_types=1);

use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\ContentSections\Filament\Resources\Sections\Pages\ListSections;
use Capell\ContentSections\Models\Section;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('section');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can list sections', function (): void {
    $sections = Section::factory()->count(5)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($sections);
});

test('can search sections', function (): void {
    $sections = Section::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Language(%d)', $sequence->index)])
        ->count(3)
        ->create();

    $name = $sections->random()->name;

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCountTableRecords($sections->where('name', $name)->count())
        ->assertCanSeeTableRecords($sections->where('name', $name))
        ->assertCanNotSeeTableRecords($sections->where('name', '!=', $name));
});

test('can sort sections', function (): void {
    $sections = Section::factory()->count(5)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->sortTable('name')
        ->assertCanSeeTableRecords($sections->sortBy('name'), inOrder: true);
});

test('can replicate sections', function (): void {
    $section = Section::factory()->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1);

    $replica = $section->replicate();
    $replica->name = $section->name . ' (copy)';
    $replica->save();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(2);

    assertDatabaseHas('sections', [
        'name' => $section->name . ' (copy)',
    ]);
});

test('can delete section', function (): void {
    $section = Section::factory()->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1);

    $section->delete();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);

    assertSoftDeleted($section, ['id' => $section->id]);
});

test('can group delete sections', function (): void {
    $sections = Section::factory()->count(5)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5);

    $sections->each->delete();

    foreach ($sections as $section) {
        assertSoftDeleted($section, ['id' => $section->id]);
    }
});

test('can select all records', function (): void {
    livewire(ListSections::class)
        ->assertSuccessful()
        ->call('getAllSelectableTableRecordKeys')
        ->assertSuccessful();
});

test('can create section', function (): void {
    Type::factory()->type(LayoutTypeEnum::Section)->create();

    $newData = Section::factory()->make();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);

    Section::query()->create([
        'name' => $newData->name,
        'type_id' => Type::query()->where('type', LayoutTypeEnum::Section)->value('id'),
    ]);

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
    $sections = Section::factory()->count(3)->type($type)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->filterTable('type_id', $type->id)
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($sections);
});

test('can filter by site', function (): void {
    $site = Site::factory()->create();
    $sections = Section::factory()->count(3)->site($site)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->filterTable('site_id', $site->id)
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($sections);
});

test('can filter by language', function (): void {
    $language = Language::factory()->create();
    Section::factory()->create();
    $sections = Section::factory()->count(3)->withTranslations($language)->create();

    livewire(ListSections::class)
        ->assertSuccessful()
        ->assertCountTableRecords(4)
        ->filterTable('filter', ['language_id' => $language->id])
        ->assertCountTableRecords(3)
        ->assertCanSeeTableRecords($sections);
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
