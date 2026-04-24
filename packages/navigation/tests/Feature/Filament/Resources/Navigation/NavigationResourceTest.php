<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('navigation');

test('admin can see navigations', function (): void {
    test()->actingAsAdmin();

    get(NavigationResource::getUrl())
        ->assertOk();
});

test('cannot see navigations', function (): void {
    test()->actingAsUser();

    get(NavigationResource::getUrl())
        ->assertForbidden();
});

test('admin can see create navigation', function (): void {
    test()->actingAsAdmin();

    get(NavigationResource::getUrl('create'))->assertOk();
});

test('admin can see edit navigation', function (): void {
    test()->actingAsAdmin();

    $language = Language::factory()->create();
    get(NavigationResource::getUrl('edit', ['record' => Navigation::factory()->language($language)->create()]))->assertOk();
});
