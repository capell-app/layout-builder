<?php

declare(strict_types=1);

use Capell\Admin\Filament\Pages\SettingsPage;
use Capell\Admin\Filament\Resources\Users\Pages\CreateUser;
use Capell\Admin\Filament\Resources\Users\Pages\EditUser;
use Capell\Core\Database\Factories\UserFactory;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\PasswordPolicy\Actions\MarkUserForPasswordChangeAction;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyUserTableExtender;
use Capell\PasswordPolicy\Filament\Pages\ForcedPasswordChangePage;
use Capell\PasswordPolicy\Filament\Pages\PasswordPolicySettingsPage;
use Capell\PasswordPolicy\Settings\PasswordPolicySettings;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\get;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)->group('password-policy');

beforeEach(function (): void {
    Permission::create(['name' => 'View:SettingsPage', 'guard_name' => 'web']);
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:SettingsPage');
});

it('keeps package settings out of the global settings page', function (): void {
    $registry = resolve(SettingsSchemaRegistry::class);

    if (! method_exists($registry, 'getFirstPartyGroups')) {
        test()->markTestSkipped('This Capell Core checkout does not expose first-party settings groups.');
    }

    expect($registry->getFirstPartyGroups())->not->toContain('password_policy');

    get(SettingsPage::getUrl())
        ->assertSuccessful()
        ->assertSeeText(__('capell-admin::generic.core'))
        ->assertDontSeeHtml('password_expiry_enabled');
});

it('saves password security settings from the package settings page', function (): void {
    Livewire::test(PasswordPolicySettingsPage::class)
        ->assertSuccessful()
        ->fillForm([
            'password_expiry_enabled' => true,
            'password_expiry_days' => 45,
            'force_change_enabled' => true,
            'compromised_password_checks_enabled' => true,
            'password_history_enabled' => true,
            'password_history_count' => 6,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = PasswordPolicySettings::instance();

    expect($settings->password_expiry_enabled)->toBeTrue()
        ->and($settings->password_expiry_days)->toBe(45)
        ->and($settings->force_change_enabled)->toBeTrue()
        ->and($settings->compromised_password_checks_enabled)->toBeTrue()
        ->and($settings->password_history_enabled)->toBeTrue()
        ->and($settings->password_history_count)->toBe(6);
});

it('redirects flagged admin users to the forced password change page', function (): void {
    $settings = PasswordPolicySettings::instance();
    $settings->force_change_enabled = true;
    $settings->save();

    MarkUserForPasswordChangeAction::run(auth()->user());

    get('/admin')
        ->assertRedirect(ForcedPasswordChangePage::getUrl());
});

it('uses the package policy when admin users are created with passwords', function (): void {
    if (! interface_exists('Capell\\Admin\\Contracts\\Extenders\\UserFormExtender')) {
        test()->markTestSkipped('Capell Admin user form extension points are not available in this checkout.');
    }

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Secure User',
            'email' => 'secure-user@example.test',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'roles' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $userModel = config('auth.providers.users.model');
    $user = $userModel::query()
        ->where('email', 'secure-user@example.test')
        ->firstOrFail();

    expect(Hash::check('new-password', (string) $user->getAttribute('password')))->toBeTrue()
        ->and($user->getAttribute('password_changed_at'))->not->toBeNull()
        ->and((bool) $user->getAttribute('must_change_password'))->toBeFalse();
});

it('uses the package history policy when admin users are edited with passwords', function (): void {
    if (! interface_exists('Capell\\Admin\\Contracts\\Extenders\\UserFormExtender')) {
        test()->markTestSkipped('Capell Admin user form extension points are not available in this checkout.');
    }

    $settings = PasswordPolicySettings::instance();
    $settings->password_history_enabled = true;
    $settings->password_history_count = 5;
    $settings->save();

    $user = UserFactory::new()->create([
        'password' => Hash::make('old-password'),
        'password_changed_at' => null,
        'must_change_password' => true,
    ]);

    Livewire::test(EditUser::class, ['record' => $user->getKey()])
        ->fillForm([
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'roles' => [],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect(Hash::check('new-password', (string) $user->getAttribute('password')))->toBeTrue()
        ->and($user->getAttribute('password_changed_at'))->not->toBeNull()
        ->and((bool) $user->getAttribute('must_change_password'))->toBeFalse();

    Livewire::test(EditUser::class, ['record' => $user->getKey()])
        ->fillForm([
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
            'password' => 'old-password',
            'password_confirmation' => 'old-password',
            'roles' => [],
        ])
        ->call('save')
        ->assertHasErrors(['password']);
});

it('hides force-change table actions when force-change enforcement is disabled', function (): void {
    $settings = PasswordPolicySettings::instance();
    $settings->force_change_enabled = false;
    $settings->save();

    $extender = resolve(PasswordPolicyUserTableExtender::class);

    expect($extender->recordActions())->toBe([])
        ->and($extender->toolbarActions())->toBe([]);

    $settings->force_change_enabled = true;
    $settings->save();

    expect($extender->recordActions())->not->toBe([])
        ->and($extender->toolbarActions())->not->toBe([]);
});

it('renders the forced password change page through Filament', function (): void {
    Livewire::test(ForcedPasswordChangePage::class)
        ->assertSuccessful()
        ->assertSee(__('capell-password-policy::password_change.description'));
});
