<?php

declare(strict_types=1);

use Capell\Admin\Actions\Users\ShouldLoadUserResourceBridgeAction;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Widgets\Dashboard\MyWorkQueueWidget;
use Capell\Admin\Filament\Widgets\Dashboard\RecentlyPublishedWidget;
use Capell\Admin\Settings\AdminSettings;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Admin\Support\Extensions\ExtensionPageRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\PublishingStudio\Bridges\PublishingStudioAdminBridge;
use Capell\PublishingStudio\Enums\ReviewDecisionEnum;
use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;
use Capell\PublishingStudio\Extenders\PublishingStudioUserSchemaExtender;
use Capell\PublishingStudio\Filament\Pages\ActivityTrailPage;
use Capell\PublishingStudio\Filament\Pages\ImportPagesPage;
use Capell\PublishingStudio\Filament\Pages\ScheduledPublishingPage;
use Capell\PublishingStudio\Filament\Pages\StaleDraftsPage;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\PreviewLinksRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\VersionsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceApprovalsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceFieldCommentsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspaceReviewAssignmentsRelationManager;
use Capell\PublishingStudio\Filament\Resources\Users\RelationManagers\WorkspacesRelationManager;
use Capell\PublishingStudio\Filament\Settings\PublishingStudioSettingsSchema;
use Capell\PublishingStudio\Filament\Widgets\WorkspaceActivityWidgetAbstract;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Models\WorkspaceApproval;
use Capell\PublishingStudio\Models\WorkspaceFieldComment;
use Capell\PublishingStudio\Models\WorkspaceReviewAssignment;
use Capell\PublishingStudio\Providers\AdminServiceProvider;
use Capell\PublishingStudio\Providers\PublishingStudioServiceProvider;
use Capell\PublishingStudio\Settings\PublishingStudioSettings;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\LegacyAdminBridgeFallbackHost;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

if (! class_exists(ShouldLoadUserResourceBridgeAction::class)) {
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

function invokePublishingStudioProviderMethod(object $provider, string $method): void
{
    $reflection = new ReflectionMethod($provider, $method);
    $reflection->invoke($provider);
}

function resetPublishingStudioAdminBridgeState(): void
{
    app()->forgetInstance(CapellAdminManager::class);
    app()->forgetInstance(ExtensionPageRegistry::class);
    CapellAdmin::clearResolvedInstance(CapellAdminManager::class);
    CapellAdmin::clearAdminSurfaceContributions();
}

function runPublishingStudioSettingsMigration(): void
{
    /** @var SettingsMigration $settingsMigration */
    $settingsMigration = require dirname(__DIR__, 2) . '/database/settings/add_publishing_studio_settings.php';

    /** @var SettingsMigrator $settingsMigrator */
    $settingsMigrator = resolve(SettingsMigrator::class);

    if (method_exists($settingsMigration, 'setMigrationAssistant')) {
        $settingsMigration->setMigrationAssistant($settingsMigrator);
    }

    $settingsMigration->up();
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

it('registers the current publishing studio admin bridge surface', function (): void {
    resetPublishingStudioAdminBridgeState();

    (new PublishingStudioAdminBridge)->register(
        new AdminBridgeRegistrar,
        AdminBridgeContextData::forPackage(PublishingStudioServiceProvider::$packageName),
    );

    $surfaceRegistry = CapellAdmin::getAdminSurfaceRegistry();
    $extensionPages = resolve(ExtensionPageRegistry::class)->entries();

    expect($surfaceRegistry->schemaExtendersForTag(UserSchemaExtender::TAG))->toContain(PublishingStudioUserSchemaExtender::class)
        ->and(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))->toContain(MyWorkQueueWidget::class)
        ->and(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))->toContain(RecentlyPublishedWidget::class)
        ->and(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))->toContain(WorkspaceActivityWidgetAbstract::class)
        ->and($surfaceRegistry->resources())->toContain(WorkspaceResource::class)
        ->and($surfaceRegistry->resources())->toContain(PreviewLinkResource::class)
        ->and($extensionPages)->toContain([
            'packageName' => PublishingStudioServiceProvider::$packageName,
            'page' => ActivityTrailPage::class,
        ])
        ->and($extensionPages)->toContain([
            'packageName' => PublishingStudioServiceProvider::$packageName,
            'page' => ImportPagesPage::class,
        ])
        ->and($extensionPages)->toContain([
            'packageName' => PublishingStudioServiceProvider::$packageName,
            'page' => ScheduledPublishingPage::class,
        ])
        ->and($extensionPages)->toContain([
            'packageName' => PublishingStudioServiceProvider::$packageName,
            'page' => StaleDraftsPage::class,
        ]);
});

it('keeps the legacy admin fallback when the bridge host is unavailable', function (): void {
    $host = new LegacyAdminBridgeFallbackHost;
    CapellAdmin::swap($host);

    try {
        invokePublishingStudioProviderMethod(new AdminServiceProvider(app()), 'registerFilamentExtensions');

        $taggedExtenders = collect(app()->tagged(UserSchemaExtender::TAG))
            ->map(fn (object $extender): string => $extender::class);
        $registeredSurfaceClasses = collect($host->surfaceContributions)->pluck('class');

        expect($taggedExtenders)->toContain(PublishingStudioUserSchemaExtender::class)
            ->and(array_keys($host->dashboardWidgets))->toContain(MyWorkQueueWidget::class)
            ->and(array_keys($host->dashboardWidgets))->toContain(RecentlyPublishedWidget::class)
            ->and(array_keys($host->dashboardWidgets))->toContain(WorkspaceActivityWidgetAbstract::class)
            ->and($host->dashboardWidgets[MyWorkQueueWidget::class])->toContain(DashboardEnum::Main)
            ->and($registeredSurfaceClasses)->toContain(WorkspaceResource::class)
            ->and($registeredSurfaceClasses)->toContain(PreviewLinkResource::class)
            ->and($host->extensionPages[PublishingStudioServiceProvider::$packageName] ?? [])->toContain(ActivityTrailPage::class)
            ->and($host->extensionPages[PublishingStudioServiceProvider::$packageName] ?? [])->toContain(ImportPagesPage::class)
            ->and($host->extensionPages[PublishingStudioServiceProvider::$packageName] ?? [])->toContain(ScheduledPublishingPage::class)
            ->and($host->extensionPages[PublishingStudioServiceProvider::$packageName] ?? [])->toContain(StaleDraftsPage::class);
    } finally {
        app()->forgetInstance(CapellAdminManager::class);
        CapellAdmin::clearResolvedInstance(CapellAdminManager::class);
    }
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
