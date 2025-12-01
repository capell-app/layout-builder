<?php

declare(strict_types=1);

use Capell\Core\Models\Type;
use Capell\Hero\Actions\CreateHeroContentTypeAction;
use Capell\Hero\Enums\ContentSchemaEnum;
use Capell\Layout\Filament\Resources\Contents\Pages\CreateContent;
use Capell\Layout\Filament\Resources\Contents\Pages\EditContent;
use Capell\Layout\Models\Content;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;
use Pest\Expectation;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('content');

beforeEach(function (): void {
    test()->actingAsAdmin();

    $this->type = CreateHeroContentTypeAction::run();
});

it('create hero content', function (): void {
    livewire(CreateContent::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => 'Hero Content',
            'admin' => [
                'schema' => ContentSchemaEnum::Hero->name,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $content = Content::with('type')->where('name', 'Hero Content')->first();

    expect($content)
        ->toBeInstanceOf(Content::class)
        ->name->toBe('Hero Content')
        ->type->scoped(
            fn (Expectation $type) => $type->toBeInstanceOf(Type::class)
                ->key->toBe('hero')
                ->admin->scoped(
                    fn ($admin) => $admin->schema->toBe(ContentSchemaEnum::Hero->name),
                ),
        );
});

it('edits the hero content via Filament', function (): void {
    $content = Content::factory()->type($this->type)
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
            fn (Expectation $type) => $type->toBeInstanceOf(Type::class)
                ->key->toBe('hero')
                ->admin->scoped(fn ($admin) => $admin->schema->toBe(ContentSchemaEnum::Hero->name)),
        );
});

it('validates edit hero content', function (): void {
    $content = Content::factory()->type($this->type)
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
