<?php

declare(strict_types=1);

use Capell\Admin\Actions\Users\ShouldLoadUserResourceBridgeAction;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Settings\AdminSettings;
use Capell\AgentBridge\Extenders\AgentBridgeUserSchemaExtender;
use Capell\AgentBridge\Filament\Resources\Users\RelationManagers\AgentBridgeAuditEntriesRelationManager;
use Capell\AgentBridge\Filament\Resources\Users\RelationManagers\AgentBridgeConfirmationsRelationManager;
use Capell\AgentBridge\Filament\Resources\Users\RelationManagers\AgentBridgeTokensRelationManager;
use Capell\AgentBridge\Filament\Settings\AgentBridgeSettingsSchema;
use Capell\AgentBridge\Models\CapellAgentBridgeAuditEntry;
use Capell\AgentBridge\Models\CapellAgentBridgeConfirmation;
use Capell\AgentBridge\Models\CapellAgentBridgeToken;
use Capell\AgentBridge\Settings\AgentBridgeSettings;
use Capell\AgentBridge\Tests\Fixtures\User;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

function createAgentBridgeUser(string $email): User
{
    return User::query()->create([
        'name' => 'Agent Bridge User',
        'email' => $email,
        'password' => 'secret',
    ]);
}

function seedAgentBridgeSettings(): void
{
    $settingsMigration = require dirname(__DIR__, 2) . '/database/settings/add_agent_bridge_settings.php';
    $settingsMigration->up();
}

function seedAdminSettings(): void
{
    $settingsMigration = require dirname(__DIR__, 5) . '/capell-4/packages/admin/database/settings/add_admin_settings.php';
    $settingsMigration->up();
}

function updateSetting(string $settingKey, mixed $value): void
{
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = resolve(SettingsMigrator::class);

    if ($settingsMigrator->exists($settingKey)) {
        $settingsMigrator->update($settingKey, fn (): mixed => $value);

        return;
    }

    $settingsMigrator->add($settingKey, $value);
}

function createAgentBridgeTokenFor(Model $user, string $name, string $plainTextToken, ?DateTimeInterface $expiresAt = null): CapellAgentBridgeToken
{
    $token = new CapellAgentBridgeToken([
        'name' => $name,
        'token_hash' => CapellAgentBridgeToken::hashPlainTextToken($plainTextToken),
        'scopes' => ['capell.pages.read'],
        'expires_at' => $expiresAt,
    ]);

    $token->user()->associate($user);
    $token->save();

    return $token;
}

it('hydrates agent bridge settings and registers the settings schema', function (): void {
    seedAgentBridgeSettings();

    expect(resolve(AgentBridgeSettings::class)->enable_user_resource_bridge)->toBeTrue();

    /** @var SettingsSchemaRegistry $registry */
    $registry = resolve(SettingsSchemaRegistry::class);

    expect($registry->getSettingsClass('agent_bridge'))->toBe(AgentBridgeSettings::class)
        ->and($registry->getSchemas('agent_bridge'))->toContain(AgentBridgeSettingsSchema::class);
});

it('exposes a translated settings toggle', function (): void {
    $components = AgentBridgeSettingsSchema::make(app(Filament\Schemas\Schema::class));

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Toggle::class)
        ->and($components[0]->getName())->toBe('enable_user_resource_bridge');
});

it('runs the agent bridge settings migration idempotently', function (): void {
    seedAgentBridgeSettings();
    seedAgentBridgeSettings();

    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = resolve(SettingsMigrator::class);

    expect($settingsMigrator->exists('agent_bridge.enable_user_resource_bridge'))->toBeTrue()
        ->and(Schema::hasTable('settings'))->toBeTrue();
});

it('gates the bridge behind both admin and package settings', function (): void {
    seedAdminSettings();
    seedAgentBridgeSettings();

    $extender = new AgentBridgeUserSchemaExtender;
    $context = UserSchemaContextData::forCreate();

    expect($extender->supports($context))->toBeTrue();

    updateSetting('admin.enable_agent_bridge_user_bridge', false);
    app()->forgetInstance(AdminSettings::class);

    expect($extender->supports($context))->toBeFalse();

    updateSetting('admin.enable_agent_bridge_user_bridge', true);
    updateSetting('agent_bridge.enable_user_resource_bridge', false);
    app()->forgetInstance(AdminSettings::class);
    app()->forgetInstance(AgentBridgeSettings::class);

    expect($extender->supports($context))->toBeFalse();
});

it('tags the user schema extender when the admin bridge host exists', function (): void {
    $taggedExtenders = iterator_to_array(app()->tagged(UserSchemaExtender::TAG));

    expect(class_exists(ShouldLoadUserResourceBridgeAction::class))->toBeTrue()
        ->and(collect($taggedExtenders)->contains(fn (object $extender): bool => $extender instanceof AgentBridgeUserSchemaExtender))->toBeTrue();
});

it('scopes tokens confirmations and audit entries to the edited user', function (): void {
    $editedUser = createAgentBridgeUser('edited@example.test');
    $otherUser = createAgentBridgeUser('other@example.test');
    $editedToken = createAgentBridgeTokenFor($editedUser, 'Edited token', 'edited-token');
    $otherToken = createAgentBridgeTokenFor($otherUser, 'Other token', 'other-token');

    $editedConfirmation = new CapellAgentBridgeConfirmation;
    $editedConfirmation->forceFill([
        'token' => str_repeat('a', 64),
        'agent_bridge_token_id' => $editedToken->getKey(),
        'capability_key' => 'capell.pages.update_draft',
        'scope' => 'capell.pages.write',
        'payload_hash' => str_repeat('b', 64),
        'payload' => ['page' => 1],
        'preview' => ['ok' => true],
        'expires_at' => now()->addMinutes(10),
    ]);
    $editedConfirmation->user()->associate($editedUser);
    $editedConfirmation->save();

    $otherConfirmation = new CapellAgentBridgeConfirmation;
    $otherConfirmation->forceFill([
        'token' => str_repeat('c', 64),
        'agent_bridge_token_id' => $otherToken->getKey(),
        'capability_key' => 'capell.pages.update_draft',
        'scope' => 'capell.pages.write',
        'payload_hash' => str_repeat('d', 64),
        'payload' => ['page' => 2],
        'preview' => ['ok' => true],
        'expires_at' => now()->addMinutes(10),
    ]);
    $otherConfirmation->user()->associate($otherUser);
    $otherConfirmation->save();

    $editedAuditEntry = new CapellAgentBridgeAuditEntry;
    $editedAuditEntry->forceFill([
        'agent_bridge_token_id' => $editedToken->getKey(),
        'event' => 'capell_agent-bridge.pages.updated',
    ]);
    $editedAuditEntry->user()->associate($editedUser);
    $editedAuditEntry->save();

    $otherAuditEntry = new CapellAgentBridgeAuditEntry;
    $otherAuditEntry->forceFill([
        'agent_bridge_token_id' => $otherToken->getKey(),
        'event' => 'capell_agent-bridge.pages.updated',
    ]);
    $otherAuditEntry->user()->associate($otherUser);
    $otherAuditEntry->save();

    expect(AgentBridgeTokensRelationManager::scopedQueryForUser(CapellAgentBridgeToken::query(), $editedUser)->pluck('name')->all())->toBe(['Edited token'])
        ->and(count(AgentBridgeConfirmationsRelationManager::scopedQueryForUser(CapellAgentBridgeConfirmation::query(), $editedUser)->pluck('id')->all()))->toBe(1)
        ->and(count(AgentBridgeAuditEntriesRelationManager::scopedQueryForUser(CapellAgentBridgeAuditEntry::query(), $editedUser)->pluck('id')->all()))->toBe(1);
});

it('summarizes token status without exposing token secrets', function (): void {
    $user = createAgentBridgeUser('safe@example.test');
    $plainTextToken = 'cagent-bridge_plain-secret';
    $token = createAgentBridgeTokenFor($user, 'Safe token', $plainTextToken, now()->addDay());
    $token->forceFill(['last_used_at' => now()->subHour()])->save();

    $summary = (new AgentBridgeUserSchemaExtender)->summarizeUserActivity($user);
    $summaryText = implode(' ', array_map('strval', $summary));

    expect($summary)->toMatchArray([
        'tokens' => 1,
        'active' => 1,
        'expired' => 0,
    ])->and($summaryText)->not->toContain($plainTextToken)
        ->and($summaryText)->not->toContain($token->token_hash);
});
