<?php

declare(strict_types=1);

use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Settings\AdminSettings;
use Capell\LoginAudit\Extenders\LoginAuditUserSchemaExtender;
use Capell\LoginAudit\Filament\Resources\Users\RelationManagers\LoginAuditsRelationManager;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\LoginAudit\Settings\LoginAuditSettings;
use Capell\Tests\Fixtures\Models\User;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

final class LoginAuditBridgeTestUser extends User
{
    use AuthenticationLoggable;

    protected $table = 'users';
}

function seedBridgeSetting(string $settingKey, mixed $value): void
{
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = resolve(SettingsMigrator::class);

    if ($settingsMigrator->exists($settingKey)) {
        $settingsMigrator->update($settingKey, fn (): mixed => $value);

        return;
    }

    $settingsMigrator->add($settingKey, $value);
}

function seedLoginAuditBridgeSettings(bool $adminEnabled = true, bool $packageEnabled = true, bool $securityAccessEnabled = true): void
{
    seedBridgeSetting('admin.enable_security_access_user_bridge', $securityAccessEnabled);
    seedBridgeSetting('admin.enable_login_audit_user_bridge', $adminEnabled);
    seedBridgeSetting('login_audit.show_login_audits', true);
    seedBridgeSetting('login_audit.retention_days', 90);
    seedBridgeSetting('login_audit.track_user_ip_addresses', true);
    seedBridgeSetting('login_audit.enable_user_resource_bridge', $packageEnabled);

    app()->forgetInstance(AdminSettings::class);
    app()->forgetInstance(LoginAuditSettings::class);
}

function makeLoginAuditBridgeUser(): LoginAuditBridgeTestUser
{
    Relation::morphMap(['login-audit-bridge-user' => LoginAuditBridgeTestUser::class]);

    /** @var LoginAuditBridgeTestUser $user */
    $user = new LoginAuditBridgeTestUser;
    $user->forceFill([
        'name' => 'Bridge User',
        'email' => 'bridge-user-' . str()->uuid() . '@example.com',
        'password' => 'password',
    ]);
    $user->save();

    return $user;
}

function rawChildComponents(object $component): array
{
    $reflectionProperty = new ReflectionProperty($component, 'childComponents');
    $childComponents = $reflectionProperty->getValue($component);

    return $childComponents['default'] ?? [];
}

function rawTextContent(Text $component): string
{
    $reflectionProperty = new ReflectionProperty($component, 'content');
    $content = $reflectionProperty->getValue($component);

    return $content instanceof Closure ? $content() : (string) $content;
}

it('adds sidebar summaries and relation manager when both bridge settings are enabled', function (): void {
    seedLoginAuditBridgeSettings();

    $user = makeLoginAuditBridgeUser();

    LoginAudit::factory()->create([
        'authenticatable_type' => $user->getMorphClass(),
        'authenticatable_id' => $user->getKey(),
        'device_id' => 'trusted-laptop',
        'login_at' => now()->subDay(),
        'login_successful' => true,
        'logout_at' => null,
    ]);
    LoginAudit::factory()->create([
        'authenticatable_type' => $user->getMorphClass(),
        'authenticatable_id' => $user->getKey(),
        'login_at' => now()->subHours(2),
        'login_successful' => false,
    ]);

    $extender = resolve(LoginAuditUserSchemaExtender::class);
    $context = UserSchemaContextData::forEdit($user, [], 'default');

    $sidebarComponents = $extender->extendSidebarComponents(Schema::make(), $context);
    $relationManagers = $extender->extendRelationManagers($user, [], $context);

    expect($extender->supports($context))->toBeTrue()
        ->and($sidebarComponents)->toHaveCount(1)
        ->and($sidebarComponents[0])->toBeInstanceOf(Section::class)
        ->and($relationManagers)->toContain(LoginAuditsRelationManager::class);

    /** @var Grid $summaryGrid */
    $summaryGrid = rawChildComponents($sidebarComponents[0])[0];
    $summaryTexts = collect(rawChildComponents($summaryGrid))
        ->map(fn (Text $component): string => rawTextContent($component))
        ->all();

    expect($summaryTexts)
        ->toContain('Recent logins: 1')
        ->toContain('Failed attempts: 1')
        ->toContain('Recent devices: 1')
        ->toContain('Active sessions: 1');
});

it('does not support the bridge when admin disables login audit user bridge', function (): void {
    seedLoginAuditBridgeSettings(adminEnabled: false);

    $extender = resolve(LoginAuditUserSchemaExtender::class);
    $context = UserSchemaContextData::forEdit(makeLoginAuditBridgeUser(), [], 'default');

    expect($extender->supports($context))->toBeFalse();
});

it('does not support the bridge when admin disables security access user bridge', function (): void {
    seedLoginAuditBridgeSettings(securityAccessEnabled: false);

    $extender = resolve(LoginAuditUserSchemaExtender::class);
    $context = UserSchemaContextData::forEdit(makeLoginAuditBridgeUser(), [], 'default');

    expect($extender->supports($context))->toBeFalse();
});

it('does not support the bridge when login audit disables its user bridge', function (): void {
    seedLoginAuditBridgeSettings(packageEnabled: false);

    $extender = resolve(LoginAuditUserSchemaExtender::class);
    $context = UserSchemaContextData::forEdit(makeLoginAuditBridgeUser(), [], 'default');

    expect($extender->supports($context))->toBeFalse();
});

it('does not duplicate the login audits relation manager', function (): void {
    seedLoginAuditBridgeSettings();

    $extender = resolve(LoginAuditUserSchemaExtender::class);
    $user = makeLoginAuditBridgeUser();
    $context = UserSchemaContextData::forEdit($user, [], 'default');

    expect($extender->extendRelationManagers($user, [LoginAuditsRelationManager::class], $context))
        ->toBe([LoginAuditsRelationManager::class]);
});

it('relation manager uses the authentications relationship when available', function (): void {
    if (! DatabaseSchema::hasColumn(config('login-audit.table_name', 'login_audit'), 'authenticatable_id')) {
        $this->markTestSkipped('Login audit table is unavailable.');
    }

    expect(LoginAuditsRelationManager::getRelationshipName())->toBe('authentications');
});
