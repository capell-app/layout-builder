<?php

declare(strict_types=1);

use Capell\Layout\Database\Factories\ContentTypeFactory;
use Capell\Layout\Filament\Resources\Collections\ContentResource;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('content');

test('admin can see contents', function (): void {
    test()->actingAsAdmin();

    get(ContentResource::getUrl())
        ->assertOk();
});

test('user cannot see contents', function (): void {
    test()->actingAsUser();

    get(ContentResource::getUrl())
        ->assertForbidden();
});

test('admin can see create content', function (): void {
    test()->actingAsAdmin();

    (new ContentTypeFactory)->default()->create();

    get(ContentResource::getUrl('create'))->assertOk();
});

test('admin can load edit content', function (): void {
    test()->actingAsAdmin();

    get(ContentResource::getUrl('edit', ['record' => Content::factory()->create()]))->assertOk();
});
