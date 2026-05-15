<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Filament\Resources\Elements\Pages\CreateElement;
use Capell\LayoutBuilder\Filament\Resources\Elements\Pages\ListElements;
use Capell\LayoutBuilder\Models\Element;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(CreatesAdminUser::class)
    ->group('element');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can delete and recreate a element with the same key and translation data', function (): void {
    $language = Language::factory()->english()->create();
    $type = Blueprint::factory()->create([
        'name' => 'Related Pages',
        'key' => 'related-pages-element-type',
        'type' => 'element',
        'group' => 'asset',
        'meta' => [
            'component' => 'capell-blog::element.page.related',
        ],
        'admin' => [
            'type_configurator' => 'Element',
            'configurator' => 'Results',
            'layout_element_configurator' => 'Results',
            'icon' => 'heroicon-o-list-bullet',
        ],
        'status' => true,
        'default' => true,
    ]);

    $element = Element::factory()->create([
        'name' => 'Related Pages',
        'key' => 'related-pages',
        'blueprint_id' => $type->id,
        'status' => true,
    ]);

    $originalTranslation = Translation::factory()
        ->translatable($element)
        ->language($language)
        ->create([
            'title' => 'Related Pages',
            'content' => '<p>Original related page element.</p>',
        ]);

    Livewire::test(ListElements::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callTableAction('delete', $element)
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($element, ['id' => $element->id]);

    Livewire::test(CreateElement::class)
        ->assertSuccessful()
        ->set('data.translations', [])
        ->fillForm([
            'name' => 'Related Pages',
            'key' => 'related-pages',
            'blueprint_id' => $type->id,
            'status' => true,
            'translations' => [
                (string) Str::uuid() => [
                    'language_id' => $language->id,
                    'title' => 'Related Pages',
                    'content' => '<p>Recreated related page element.</p>',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $recreatedElement = Element::query()
        ->where('key', 'related-pages')
        ->whereNull('deleted_at')
        ->first();

    expect($recreatedElement)
        ->not->toBeNull()
        ->and($recreatedElement->id)->not->toBe($element->id)
        ->and($recreatedElement->blueprint_id)->toBe($type->id)
        ->and($recreatedElement->type->admin)->toMatchArray([
            'type_configurator' => 'Element',
            'configurator' => 'Results',
            'layout_element_configurator' => 'Results',
        ]);

    $recreatedTranslations = Translation::query()
        ->where('translatable_type', $recreatedElement->getMorphClass())
        ->where('translatable_id', $recreatedElement->id)
        ->get();

    expect($recreatedTranslations)
        ->toHaveCount(1)
        ->first()->id->not->toBe($originalTranslation->id);

    assertDatabaseHas(Translation::class, [
        'translatable_type' => $recreatedElement->getMorphClass(),
        'translatable_id' => $recreatedElement->id,
        'language_id' => $language->id,
        'title' => 'Related Pages',
        'content' => '<p>Recreated related page element.</p>',
    ]);
});
