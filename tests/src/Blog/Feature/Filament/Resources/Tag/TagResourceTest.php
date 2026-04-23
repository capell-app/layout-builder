<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('tag');

test('admin can see tags', function (): void {
    test()->actingAsAdmin();

    Language::factory()->create();

    get(TagResource::getUrl())
        ->assertOk();
});

test('cannot see tags', function (): void {
    test()->actingAsUser();

    get(TagResource::getUrl())
        ->assertForbidden();
});
