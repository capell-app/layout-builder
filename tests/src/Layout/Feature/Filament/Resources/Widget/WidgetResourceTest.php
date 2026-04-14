<?php

declare(strict_types=1);

use Capell\Layout\Filament\Resources\Widgets\WidgetResource;
use Capell\Layout\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('widget');

test('admin can see widgets', function (): void {
    test()->actingAsAdmin();

    get(WidgetResource::getUrl())
        ->assertOk();
});

test('cannot see widgets', function (): void {
    test()->actingAsUser();

    get(WidgetResource::getUrl())
        ->assertForbidden();
});

test('admin can see create widget', function (): void {
    test()->actingAsAdmin();

    get(WidgetResource::getUrl('create'))->assertOk();
});

test('admin can load edit widget', function (): void {
    test()->actingAsAdmin();

    get(WidgetResource::getUrl('edit', ['record' => Widget::factory()->create()]))->assertOk();
});
