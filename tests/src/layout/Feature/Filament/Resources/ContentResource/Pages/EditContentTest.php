<?php

declare(strict_types=1);

use Capell\Admin\Filament\Actions\DeleteAction;
use Capell\Core\Models\Site;
use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Models\Content;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('content');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('can save', function (): void {
    $content = Content::factory()->create();
    $newData = Content::factory()
        ->site(Site::factory()->create())
        ->parent(Content::factory()->create())
        ->make();

    livewire(EditContent::class, [
        'record' => $content->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'name' => $content->name,
            'type_id' => $content->type->getKey(),
            'parent_id' => $content->parent?->id,
            'site_id' => $content->site?->getKey(),
        ])
        ->assertFormFieldExists('image')
        ->fillForm([
            'name' => $newData->name,
            'parent_id' => $newData->parent->id,
            'site_id' => $newData->site->getKey(),
        ])
        ->assertSchemaStateSet([
            'name' => $newData->name,
            'parent_id' => $newData->parent->id,
            'site_id' => $newData->site->getKey(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($content->refresh())
        ->name->toBe($newData->name)
        ->type_id->toBe($content->type->getKey())
        ->parent_id->toBe($newData->parent->id)
        ->site_id->toBe($newData->site->getKey())
        ->meta->image->toBeNull();
});

test('validates edit content', function (): void {
    $content = Content::factory()->create();

    livewire(EditContent::class, [
        'record' => $content->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => null,
        ])
        ->call('save')
        ->assertHasFormErrors(['name' => 'required']);
});

it('can delete', function (): void {
    $content = Content::factory()->create();

    livewire(EditContent::class, [
        'record' => $content->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction(DeleteAction::class)
        ->assertHasNoFormErrors();

    assertSoftDeleted($content, ['id' => $content->id]);
});

todo('test create action');
