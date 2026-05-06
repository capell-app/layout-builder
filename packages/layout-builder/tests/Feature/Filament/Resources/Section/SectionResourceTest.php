<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Database\Factories\ContentTypeFactory;
use Capell\LayoutBuilder\Filament\Resources\Sections\SectionResource;
use Capell\LayoutBuilder\Models\Section;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('content');

test('admin can see contents', function (): void {
    test()->actingAsAdmin();

    get(SectionResource::getUrl())
        ->assertOk();
});

test('user cannot see contents', function (): void {
    test()->actingAsUser();

    get(SectionResource::getUrl())
        ->assertForbidden();
});

test('admin can see create content', function (): void {
    test()->actingAsAdmin();

    (new ContentTypeFactory)->default()->create();

    get(SectionResource::getUrl('create'))->assertOk();
});

test('admin can load edit content', function (): void {
    test()->actingAsAdmin();

    get(SectionResource::getUrl('edit', ['record' => Section::factory()->create()]))->assertOk();
});
