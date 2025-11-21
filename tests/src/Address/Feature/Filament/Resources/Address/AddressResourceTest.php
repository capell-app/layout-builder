<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Addresses\AddressResource;
use Capell\Tests\Fixtures\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('address');

test('admin can see addresses', function (): void {
    test()->actingAsAdmin();

    get(AddressResource::getUrl())
        ->assertOk();
});

test('cannot see addresses', function (): void {
    test()->actingAsUser();

    get(AddressResource::getUrl())
        ->assertForbidden();
});
