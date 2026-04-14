<?php

declare(strict_types=1);

use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Pages\SettingsPage;
use Capell\Assistant\Filament\Settings\AssistantSettingsSchema;
use Capell\Assistant\Settings\AssistantSettings;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Schemas\Schema;

use function Pest\Laravel\get;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('assistant', 'settings');

test('registers assistant settings schema in registry', function (): void {
    $registry = resolve(SettingsSchemaRegistry::class);

    expect($registry->hasGroup('assistant'))->toBeTrue()
        ->and($registry->getSettingsClass('assistant'))->toBe(AssistantSettings::class)
        ->and($registry->getSchemas('assistant'))->toHaveKey('AssistantSettingsSchema');
});

test('assistant settings schema implements hasschema contract', function (): void {
    $interfaces = class_implements(AssistantSettingsSchema::class);

    expect($interfaces)->toContain(HasSchema::class);
});

test('includes assistant settings schema in settings page', function (): void {
    Permission::create(['name' => 'View:SettingsPage', 'guard_name' => 'web']);
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:SettingsPage');

    get(SettingsPage::getUrl())
        ->assertSuccessful();
});

test('assistant settings schema returns form components', function (): void {
    $schema = Mockery::mock(Schema::class);
    $components = AssistantSettingsSchema::make($schema);

    expect($components)->toBeArray();
});
