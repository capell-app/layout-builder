<?php

declare(strict_types=1);

use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Settings\AdminSettings;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\PublishingStudio\Enums\ReviewDecisionEnum;
use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;
use Capell\PublishingStudio\Extenders\PublishingStudioUserSchemaExtender;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\PreviewLinksRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\VersionsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceApprovalsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceFieldCommentsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceReviewAssignmentsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspacesRelationManager;
use Capell\PublishingStudio\Filament\Settings\PublishingStudioSettingsSchema;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Capell\PublishingStudio\Models\WorkspaceFieldComment;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Capell\PublishingStudio\Settings\PublishingStudioSettings;
use Capell\Tests\Fixtures\Models\User;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

if (! class_exists('Capell\Admin\Actions\Users\ShouldLoadUserResourceBridgeAction')) {
    eval(<<<'PHP'
        namespace Capell\Admin\Actions\Users;

        use Capell\Admin\Settings\AdminSettings;
        use Lorisleiva\Actions\Concerns\AsAction;

        final class ShouldLoadUserResourceBridgeAction
        {
            use AsAction;

            public function handle(string $adminSettingName, bool $packageEnabled): bool
            {
                return $packageEnabled && (bool) resolve(AdminSettings::class)->{$adminSettingName};
            }
        }
        PHP);
}

function seedPublishingStudioBridgeSetting(string $settingKey, mixed $value): void
{
    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = resolve(SettingsMigrator::class);

    if ($settingsMigrator->exists($settingKey)) {
        $settingsMigrator->update($settingKey, fn (): mixed => $value);

        return;
    }

    $settingsMigrator->add($settingKey, $value);
}

function seedPublishingStudioBridgeSettings(bool $adminEnabled = true, bool $packageEnabled = true, bool $contentOwnershipEnabled = true): void
{
    seedPublishingStudioBridgeSetting('admin.enable_content_ownership_user_bridge', $contentOwnershipEnabled);
    seedPublishingStudioBridgeSetting('admin.enable_publishing_studio_user_bridge', $adminEnabled);
    seedPublishingStudioBridgeSetting('publishing_studio.enable_user_resource_bridge', $packageEnabled);

    app()->forgetInstance(AdminSettings::class);
    app()->forgetInstance(PublishingStudioSettings::class);
}

function runPublishingStudioSettingsMigration(): void
{
    /** @var SettingsMigration $migration */
    $migration = require dirname(__DIR__, 2) . '/database/settings/add_publishing_studio_settings.php';

    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = resolve(SettingsMigrator::class);

    if (method_exists($migration, 'setMigrationAssistant')) {
        $migration->setMigrationAssistant($settingsMigrator);
    }

    if (! property_exists($migration, 'migration')) {
        $migration->migration = $settingsMigrator;
    }

    $migration->up();
}

function createPublishingStudioBridgeWorkspaceFor(User $user, string $slug): Workspace
{
    /** @var Workspace $workspace */
    $workspace = Workspace::factory()->create([
        'name' => str($slug)->headline()->toString(),
        'slug' => $slug,
    ]);

    $workspace->forceFill([
        'created_by' => $user->getKey(),
        'updated_by' => $user->getKey(),
    ])->save();

    return $workspace;
}

it('registers and hydrates publishing studio settings', function (): void {
    $registry = resolve(SettingsSchemaRegistry::class);
    $components = PublishingStudioSettingsSchema::make(Schema::make());

    expect(resolve(PublishingStudioSettings::class)->enable_user_resource_bridge)->toBeTrue()
        ->and($registry->getSettingsClass('publishing_studio'))->toBe(PublishingStudioSettings::class)
        ->and($registry->getSchema('publishing_studio', 'PublishingStudioSettingsSchema'))->toBe(PublishingStudioSettingsSchema::class)
        ->and($components[0])->toBeInstanceOf(Toggle::class)
        ->and($components[0]->getName())->toBe('enable_user_resource_bridge');
});

it('keeps the publishing studio settings migration idempotent', function (): void {
    runPublishingStudioSettingsMigration();
    runPublishingStudioSettingsMigration();

    expect(resolve(SettingsMigrator::class)->exists('publishing_studio.enable_user_resource_bridge'))->toBeTrue()
        ->and(resolve(PublishingStudioSettings::class)->enable_user_resource_bridge)->toBeTrue();
});

it('adds content workflow sidebar summaries and relation managers when both bridge settings allow it', function (): void {
    seedPublishingStudioBridgeSettings();

    $user = User::factory()->create();
    $workspace = createPublishingStudioBridgeWorkspaceFor($user, 'bridge-workspace');

    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $workspace->getKey(),
        'reviewer_type' => $user->getMorphClass(),
        'reviewer_id' => $user->getKey(),
        'required_for' => 'publish',
        'decision' => ReviewDecisionEnum::Approved,
    ]);
    WorkspaceApproval::query()->create([
        'workspace_id' => $workspace->getKey(),
        'actionable_type' => $user->getMorphClass(),
        'actionable_id' => $user->getKey(),
        'level' => 1,
        'action' => WorkspaceApprovalActionEnum::ChangesRequested,
    ]);
    WorkspaceFieldComment::query()->create([
        'workspace_id' => $workspace->getKey(),
        'entity_type' => Workspace::class,
        'entity_uuid' => $workspace->uuid,
        'field_path' => 'name',
        'author_type' => $user->getMorphClass(),
        'author_id' => $user->getKey(),
        'body' => 'Needs a clearer title.',
    ]);
    PreviewLink::query()->create([
        'workspace_id' => $workspace->getKey(),
        'token' => PreviewLink::generateToken(),
        'issued_by_type' => $user->getMorphClass(),
        'issued_by_id' => $user->getKey(),
        'issued_at' => now(),
        'expires_at' => now()->addDay(),
    ]);
    Version::query()->create([
        'uuid' => (string) str()->uuid(),
        'number' => 101,
        'manifest' => [],
        'published_by_type' => $user->getMorphClass(),
        'published_by_id' => $user->getKey(),
        'published_at' => now(),
    ]);

    $extender = resolve(PublishingStudioUserSchemaExtender::class);
    $context = UserSchemaContextData::forEdit($user, [], 'default');

    $sidebarComponents = $extender->extendSidebarComponents(Schema::make(), $context);
    $relationManagers = $extender->extendRelationManagers($user, [], $context);

    expect($extender->supports($context))->toBeTrue()
        ->and($sidebarComponents)->toHaveCount(1)
        ->and($sidebarComponents[0])->toBeInstanceOf(Section::class)
        ->and($relationManagers)->toContain(WorkspacesRelationManager::class)
        ->and($relationManagers)->toContain(WorkspaceReviewAssignmentsRelationManager::class)
        ->and($relationManagers)->toContain(WorkspaceApprovalsRelationManager::class)
        ->and($relationManagers)->toContain(WorkspaceFieldCommentsRelationManager::class)
        ->and($relationManagers)->toContain(PreviewLinksRelationManager::class)
        ->and($relationManagers)->toContain(VersionsRelationManager::class);

    expect(WorkspacesRelationManager::scopedQueryForUser($user)->count())->toBe(1)
        ->and(WorkspaceReviewAssignmentsRelationManager::scopedQueryForUser($user)->count())->toBe(1)
        ->and(WorkspaceApprovalsRelationManager::scopedQueryForUser($user)->count())->toBe(1)
        ->and(WorkspaceFieldCommentsRelationManager::scopedQueryForUser($user)->count())->toBe(1)
        ->and(PreviewLinksRelationManager::scopedQueryForUser($user)->count())->toBe(1)
        ->and(VersionsRelationManager::scopedQueryForUser($user)->count())->toBe(1);
});

it('does not support the bridge when admin disables publishing studio user bridge', function (): void {
    seedPublishingStudioBridgeSettings(adminEnabled: false);

    $user = User::factory()->create();
    $context = UserSchemaContextData::forEdit($user, [], 'default');

    expect(resolve(PublishingStudioUserSchemaExtender::class)->supports($context))->toBeFalse();
});

it('does not support the bridge when admin disables content ownership user bridge', function (): void {
    seedPublishingStudioBridgeSettings(contentOwnershipEnabled: false);

    $user = User::factory()->create();
    $context = UserSchemaContextData::forEdit($user, [], 'default');

    expect(resolve(PublishingStudioUserSchemaExtender::class)->supports($context))->toBeFalse();
});

it('does not support the bridge when publishing studio disables its user bridge', function (): void {
    seedPublishingStudioBridgeSettings(packageEnabled: false);

    $user = User::factory()->create();
    $context = UserSchemaContextData::forEdit($user, [], 'default');

    expect(resolve(PublishingStudioUserSchemaExtender::class)->supports($context))->toBeFalse();
});

it('does not support the bridge when publishing studio is registered but not installed', function (): void {
    seedPublishingStudioBridgeSettings();
    CapellCore::forcePackageInstalled('capell-app/publishing-studio', false);

    $user = User::factory()->create();
    $context = UserSchemaContextData::forEdit($user, [], 'default');

    expect(resolve(PublishingStudioUserSchemaExtender::class)->supports($context))->toBeFalse();
});

it('relation managers only include records connected to the edited user', function (): void {
    seedPublishingStudioBridgeSettings();

    $editedUser = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownedWorkspace = createPublishingStudioBridgeWorkspaceFor($editedUser, 'owned-workspace');
    $otherWorkspace = createPublishingStudioBridgeWorkspaceFor($otherUser, 'other-workspace');

    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $ownedWorkspace->getKey(),
        'reviewer_type' => $editedUser->getMorphClass(),
        'reviewer_id' => $editedUser->getKey(),
        'required_for' => 'publish',
    ]);
    WorkspaceReviewAssignment::query()->create([
        'workspace_id' => $otherWorkspace->getKey(),
        'reviewer_type' => $otherUser->getMorphClass(),
        'reviewer_id' => $otherUser->getKey(),
        'required_for' => 'publish',
    ]);
    WorkspaceApproval::query()->create([
        'workspace_id' => $ownedWorkspace->getKey(),
        'actionable_type' => $editedUser->getMorphClass(),
        'actionable_id' => $editedUser->getKey(),
        'level' => 1,
        'action' => WorkspaceApprovalActionEnum::Approved,
    ]);
    WorkspaceApproval::query()->create([
        'workspace_id' => $otherWorkspace->getKey(),
        'actionable_type' => $otherUser->getMorphClass(),
        'actionable_id' => $otherUser->getKey(),
        'level' => 1,
        'action' => WorkspaceApprovalActionEnum::Approved,
    ]);
    WorkspaceFieldComment::query()->create([
        'workspace_id' => $ownedWorkspace->getKey(),
        'entity_type' => Workspace::class,
        'entity_uuid' => $ownedWorkspace->uuid,
        'field_path' => 'name',
        'author_type' => $editedUser->getMorphClass(),
        'author_id' => $editedUser->getKey(),
        'body' => 'Owned comment.',
    ]);
    WorkspaceFieldComment::query()->create([
        'workspace_id' => $otherWorkspace->getKey(),
        'entity_type' => Workspace::class,
        'entity_uuid' => $otherWorkspace->uuid,
        'field_path' => 'name',
        'author_type' => $otherUser->getMorphClass(),
        'author_id' => $otherUser->getKey(),
        'body' => 'Other comment.',
    ]);
    PreviewLink::query()->create([
        'workspace_id' => $ownedWorkspace->getKey(),
        'token' => PreviewLink::generateToken(),
        'issued_by_type' => $editedUser->getMorphClass(),
        'issued_by_id' => $editedUser->getKey(),
        'issued_at' => now(),
        'expires_at' => now()->addDay(),
    ]);
    PreviewLink::query()->create([
        'workspace_id' => $otherWorkspace->getKey(),
        'token' => PreviewLink::generateToken(),
        'issued_by_type' => $otherUser->getMorphClass(),
        'issued_by_id' => $otherUser->getKey(),
        'issued_at' => now(),
        'expires_at' => now()->addDay(),
    ]);
    Version::query()->create([
        'uuid' => (string) str()->uuid(),
        'number' => 201,
        'manifest' => [],
        'published_by_type' => $editedUser->getMorphClass(),
        'published_by_id' => $editedUser->getKey(),
        'published_at' => now(),
    ]);
    Version::query()->create([
        'uuid' => (string) str()->uuid(),
        'number' => 202,
        'manifest' => [],
        'published_by_type' => $otherUser->getMorphClass(),
        'published_by_id' => $otherUser->getKey(),
        'published_at' => now(),
    ]);

    expect(WorkspacesRelationManager::scopedQueryForUser($editedUser)->pluck('id')->all())->toBe([$ownedWorkspace->getKey()])
        ->and(WorkspaceReviewAssignmentsRelationManager::scopedQueryForUser($editedUser)->pluck('reviewer_id')->all())->toBe([$editedUser->getKey()])
        ->and(WorkspaceApprovalsRelationManager::scopedQueryForUser($editedUser)->pluck('actionable_id')->all())->toBe([$editedUser->getKey()])
        ->and(WorkspaceFieldCommentsRelationManager::scopedQueryForUser($editedUser)->pluck('author_id')->all())->toBe([$editedUser->getKey()])
        ->and(PreviewLinksRelationManager::scopedQueryForUser($editedUser)->pluck('issued_by_id')->all())->toBe([$editedUser->getKey()])
        ->and(VersionsRelationManager::scopedQueryForUser($editedUser)->pluck('published_by_id')->all())->toBe([$editedUser->getKey()]);
});
