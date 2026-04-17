<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Layout\Database\Factories\ContentTypeFactory;
use Capell\Layout\Models\Collection;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('content');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('required fields are required', function (): void {
    (new ContentTypeFactory)->create();

    livewire(CreateCollection::class)
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

    livewire(CreateCollection::class)
        ->assertSuccessful()
        ->fillForm([
            'type_id' => $newData->type->getKey(),
            'name' => $newData->name,
        ])
        ->assertSchemaStateSet([
            'name' => $newData->name,
            'type_id' => $newData->type->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Collection::class, [
        'name' => $newData->name,
    ]);
})
    ->with(['default', 'with deleted site']);

test('create with translations', function (string $mode): void {
    $languages = Language::factory()->count(3)->create(['name' => "Cote d'Ivoire"]);
    $site = Site::factory()->state(['language_id' => $languages->first()->id])->withTranslations($languages)->create();

    $type = (new ContentTypeFactory)->default()->create();

    $newData = Content::factory()
        ->type($type)
        ->parent(Content::factory()->create())
        ->make();

    if ($mode === 'with deleted site') {
        Site::factory()->deleted()->create();
    }

    livewire(CreateCollection::class)
        ->assertSuccessful()
        ->set('data.translations', [])
        ->fillForm([
            'type_id' => $type->getKey(),
            'name' => $newData->name,
            'parent_id' => $newData->parent?->id,
            'translations' => $site->languages
                ->mapWithKeys(
                    fn (Language $language): array => [
                        (string) Str::uuid() => [
                            'language_id' => $language->getKey(),
                            'title' => $newData->name . ' - ' . $language->name,
                            'content' => $newData->name . ' - ' . $language->name,
                        ],
                    ],
                )
                ->all(),
        ])
        ->assertSchemaStateSet([
            'name' => $newData->name,
            'type_id' => $type->getKey(),
            'parent_id' => $newData->parent?->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Collection::class, [
        'name' => $newData->name,
        'parent_id' => $newData->parent?->id,
        'type_id' => $type->getKey(),
    ]);

    $site->languages->each(
        fn (Language $language) => assertDatabaseHas(Translation::class, [
            'language_id' => $language->getKey(),
            'title' => $newData->name . ' - ' . $language->name,
            'content' => '<p>' . htmlspecialchars($newData->name . ' - ' . $language->name, ENT_QUOTES, 'UTF-8') . '</p>',
            'translatable_type' => 'content',
        ]),
    );
})
    ->with(['default', 'with deleted site']);

test('can search parent results', function (): void {
    $parent = Content::factory()->withTranslations()->create();

    $livewire = livewire(CreateCollection::class);
    $instance = $livewire->instance();
    $schema = $instance->getSchema($instance->getDefaultTestingSchemaName());
    $component = $schema->getComponent('parent_id');

    $livewire->call('callSchemaComponentMethod', $component->getKey(), $parent->name)
        ->assertSuccessful();
});
