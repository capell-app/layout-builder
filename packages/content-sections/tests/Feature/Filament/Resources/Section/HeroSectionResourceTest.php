<?php

declare(strict_types=1);

namespace Capell\ContentSections\Tests\Feature\Filament\Resources\Section;

use Capell\ContentSections\Actions\CreateHeroContentTypeAction;
use Capell\ContentSections\Enums\SectionConfiguratorEnum;
use Capell\ContentSections\Filament\Resources\Sections\Pages\EditSection;
use Capell\ContentSections\Models\Section;
use Capell\Core\Models\Type;
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
    $section = Section::factory()->type($type)
        ->state([
            'name' => 'Hero Content',
        ])
        ->create();

    livewire(EditSection::class, [
        'record' => $section->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => 'Updated Hero Content',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $section->refresh();

    expect($section)
        ->toBeInstanceOf(Section::class)
        ->name->toBe('Updated Hero Content')
        ->type->scoped(
            fn (Expectation $type): HigherOrderExpectation => $type->toBeInstanceOf(Type::class)
                ->key->toBe('hero')
                ->admin->scoped(fn (Expectation $admin): HigherOrderExpectation => $admin->configurator->toBe(SectionConfiguratorEnum::Hero->name)),
        );
});

it('validates edit hero content', function (): void {
    $type = CreateHeroContentTypeAction::run();
    $section = Section::factory()->type($type)
        ->state([
            'name' => 'Hero Content',
        ])
        ->create();

    livewire(EditSection::class, [
        'record' => $section->getRouteKey(),
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
