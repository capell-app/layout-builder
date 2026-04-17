<?php

declare(strict_types=1);

use Capell\Core\Models\Type;
use Capell\Hero\Actions\CreateHeroContentTypeAction;
use Capell\Hero\Enums\ContentSchemaEnum;
use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Models\Collection;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Pest\Expectation;
use Pest\Expectations\HigherOrderExpectation;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('content');

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('edits the hero content via Filament', function (): void {
    $type = CreateHeroContentTypeAction::run();
    $content = Collection::factory()->type($type)
        ->state([
            'name' => 'Hero Content',
        ])
        ->create();

    livewire(EditContent::class, [
        'record' => $content->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => 'Updated Hero Content',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $content->refresh();

    expect($content)
        ->toBeInstanceOf(Content::class)
        ->name->toBe('Updated Hero Content')
        ->type->scoped(
            fn (Expectation $type): HigherOrderExpectation => $type->toBeInstanceOf(Type::class)
                ->key->toBe('hero')
                ->admin->scoped(fn (Expectation $admin): HigherOrderExpectation => $admin->schema->toBe(ContentSchemaEnum::Hero->name)),
        );
});

it('validates edit hero content', function (): void {
    $type = CreateHeroContentTypeAction::run();
    $content = Collection::factory()->type($type)
        ->state([
            'name' => 'Hero Content',
        ])
        ->create();

    livewire(EditContent::class, [
        'record' => $content->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => '',
        ])
        ->call('save')
        ->assertHasAllFormErrors([
            'name' => 'required',
        ]);
});
