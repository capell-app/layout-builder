<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Layout\Database\Factories\ContentTypeFactory;
use Capell\Layout\Filament\Resources\ContentResource\Pages\CreateContent;
use Capell\Layout\Models\Content;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('content');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('required fields are required', function (): void {
    (new ContentTypeFactory())->create();

    livewire(CreateContent::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => '',
        ])
        ->call('create')
        ->assertHasAllFormErrors([
            'name' => 'required',
        ]);
});

it('can create', function (string $type): void {
    $newData = Content::factory()->make();

    if ($type === 'with deleted site') {
        Site::factory()->deleted()->create();
    }

    livewire(CreateContent::class)
        ->assertSuccessful()
        ->set('data.translations', [])
        ->fillForm([
            'type_id' => $newData->type->getKey(),
            'name' => $newData->name,
        ])
        ->assertFormSet([
            'name' => $newData->name,
            'type_id' => $newData->type->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Content::class, [
        'name' => $newData->name,
    ]);
})
    ->with(['default', 'with deleted site']);

it('can create with translations', function (string $mode): void {
    Site::factory()->create();

    $type = (new ContentTypeFactory())->default()->create();

    $newData = Content::factory()
        ->type($type)
        ->parent(Content::factory()->create())
        ->make();

    if ($mode === 'with deleted site') {
        Site::factory()->deleted()->create();
    }

    $languages = Language::factory()->count(3)->create();

    livewire(CreateContent::class)
        ->assertSuccessful()
        ->fillForm([
            'type_id' => $type->getKey(),
            'name' => $newData->name,
            'parent_uuid' => $newData->parent->uuid,
        ])
        ->set(
            'data.translations',
            $languages->map(
                fn (Language $language): array => [
                    'language_id' => $language->getKey(),
                    'title' => $newData->name.' - '.$language->name,
                    'content' => 'Test content',
                ]
            )->toArray()
        )
        ->assertFormSet([
            'name' => $newData->name,
            'type_id' => $type->getKey(),
            'parent_uuid' => $newData->parent->uuid,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Content::class, [
        'name' => $newData->name,
        'parent_uuid' => $newData->parent->uuid,
        'type_id' => $type->getKey(),
    ]);

    $languages->each(
        fn (Language $language) => assertDatabaseHas(Translation::class, [
            'language_id' => $language->getKey(),
            'title' => $newData->name.' - '.$language->name,
            'content' => 'Test content',
            'translatable_type' => 'content',
        ])
    );
})
    ->with(['default', 'with deleted site']);

test('can search parent results', function (): void {
    $parent = Content::factory()->withTranslations()->create();

    livewire(CreateContent::class)
        ->call('getFormSelectSearchResults', 'data.parent_id', $parent->name)
        ->assertSuccessful();
});
