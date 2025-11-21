<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Countries\CountryResource;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('country');

test('admin can see countries', function (): void {
    test()->actingAsAdmin();

    get(CountryResource::getUrl())
        ->assertOk();
});

test('cannot see countries', function (): void {
    test()->actingAsUser();

    get(CountryResource::getUrl())
        ->assertForbidden();
});
