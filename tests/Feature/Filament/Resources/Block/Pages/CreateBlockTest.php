<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\CreateWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(CreatesAdminUser::class)
    ->group('block');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

test('can delete and recreate a block with the same key and translation data', function (): void {
    $language = Language::factory()->english()->create();
    $type = Blueprint::factory()->create([
        'name' => 'Related Pages',
        'key' => 'related-pages-block-type',
        'type' => 'widget',
        'group' => 'asset',
        'meta' => [
            'component' => 'capell-blog::block.page.related',
        ],
        'admin' => [
            'type_configurator' => 'Widget',
            'configurator' => 'Results',
            'layout_block_configurator' => 'Results',
            'icon' => 'heroicon-o-list-bullet',
        ],
        'status' => true,
        'default' => true,
    ]);

    $block = Widget::factory()->create([
        'name' => 'Related Pages',
        'key' => 'related-pages',
        'blueprint_id' => $type->id,
        'status' => true,
    ]);

    $originalTranslation = Translation::factory()
        ->translatable($block)
        ->language($language)
        ->create([
            'title' => 'Related Pages',
            'content' => '<p>Original related page block.</p>',
        ]);

    Livewire::test(ListWidgets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->callTableAction('delete', $block)
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(0);

    assertSoftDeleted($block, ['id' => $block->id]);

    Livewire::test(CreateWidget::class)
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
                    'content' => '<p>Recreated related page block.</p>',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $recreatedBlock = Widget::query()
        ->where('key', 'related-pages')
        ->whereNull('deleted_at')
        ->first();

    expect($recreatedBlock)
        ->not->toBeNull()
        ->and($recreatedBlock->id)->not->toBe($block->id)
        ->and($recreatedBlock->blueprint_id)->toBe($type->id)
        ->and($recreatedBlock->type->admin)->toMatchArray([
            'type_configurator' => 'Widget',
            'configurator' => 'Results',
            'layout_block_configurator' => 'Results',
        ]);

    $recreatedTranslations = Translation::query()
        ->where('translatable_type', $recreatedBlock->getMorphClass())
        ->where('translatable_id', $recreatedBlock->id)
        ->get();

    expect($recreatedTranslations)
        ->toHaveCount(1)
        ->first()->id->not->toBe($originalTranslation->id);

    assertDatabaseHas(Translation::class, [
        'translatable_type' => $recreatedBlock->getMorphClass(),
        'translatable_id' => $recreatedBlock->id,
        'language_id' => $language->id,
        'title' => 'Related Pages',
        'content' => '<p>Recreated related page block.</p>',
    ]);
});
