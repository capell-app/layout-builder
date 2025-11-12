<?php

declare(strict_types=1);

use Capell\Blog\Filament\Resources\Tags\TagResource;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('tag');

test('admin can see tags', function (): void {
    test()->actingAsAdmin();

    get(TagResource::getUrl())
        ->assertOk();
})->todo();

test('cannot see tags', function (): void {
    test()->actingAsUser();

    get(TagResource::getUrl())
        ->assertForbidden();
});
