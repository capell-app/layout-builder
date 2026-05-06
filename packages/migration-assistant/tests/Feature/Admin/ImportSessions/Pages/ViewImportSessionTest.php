<?php

declare(strict_types=1);

use Capell\MigrationAssistant\Actions\InstallMigrationAssistantPermissionsAction;
use Capell\MigrationAssistant\Enums\ImportSessionKind;
use Capell\MigrationAssistant\Enums\ImportSessionStatus;
use Capell\MigrationAssistant\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\MigrationAssistant\Filament\Resources\ImportSessions\Pages\ViewImportSession;
use Capell\MigrationAssistant\Jobs\ExecuteImportPlanJob;
use Capell\MigrationAssistant\Models\ImportSession;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)->group('import-session-resource');

beforeEach(function (): void {
    if (! class_exists(ImportSession::class)) {
        test()->markTestSkipped('capell-app/migration-assistant is not installed in this checkout.');
    }

    Permission::findOrCreate('View:ImportSessionResource', 'web');
    InstallMigrationAssistantPermissionsAction::run();
    Storage::fake('local');
    Queue::fake();

    $adminUser = test()->createUserWithRole('super_admin');
    $adminUser->givePermissionTo([
        'View:ImportSessionResource',
        InstallMigrationAssistantPermissionsAction::PERMISSION_IMPORT_SESSION_VIEW,
        InstallMigrationAssistantPermissionsAction::PERMISSION_IMPORT_SESSION_CANCEL,
        InstallMigrationAssistantPermissionsAction::PERMISSION_IMPORT_SESSION_RETRY,
    ]);
    test()->actingAs($adminUser);
});

function makeImportSession(array $overrides = []): ImportSession
{
    return ImportSession::query()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'user_id' => auth()->id(),
        'kind' => ImportSessionKind::PageImport,
        'status' => ImportSessionStatus::Queued,
        'source_filename' => 'package.zip',
        'source_package_path' => 'migration-assistant/imports/package-' . Str::random(6) . '.zip',
    ], $overrides));
}

it('shows the download archive action when the source archive exists on disk', function (): void {
    $session = makeImportSession();
    Storage::disk('local')->put((string) $session->source_package_path, 'zip-bytes');

    Livewire::test(ViewImportSession::class, ['record' => $session->getRouteKey()])
        ->assertActionVisible('downloadArchive');
});

it('uses the installed import-session view permission for global admin resource access', function (): void {
    $user = test()->createUserWithRole('super_admin');
    $user->givePermissionTo(InstallMigrationAssistantPermissionsAction::PERMISSION_IMPORT_SESSION_VIEW);

    test()->actingAs($user);

    expect(ImportSessionResource::canViewAny())->toBeTrue();
});

it('denies import session resource access to non-global users with the view permission', function (): void {
    $user = test()->createUserWithPermission(InstallMigrationAssistantPermissionsAction::PERMISSION_IMPORT_SESSION_VIEW);
    test()->actingAs($user);

    expect(ImportSessionResource::canViewAny())->toBeFalse();
});

it('hides the download archive action when the source archive is missing', function (): void {
    $session = makeImportSession();
    // File not present on disk.

    Livewire::test(ViewImportSession::class, ['record' => $session->getRouteKey()])
        ->assertActionHidden('downloadArchive');
});

it('hides the cancel action for terminal sessions', function (): void {
    $session = makeImportSession(['status' => ImportSessionStatus::Completed]);

    Livewire::test(ViewImportSession::class, ['record' => $session->getRouteKey()])
        ->assertActionHidden('cancelSession');
});

it('shows cancel for queued sessions and flips status to abandoned on confirm', function (): void {
    $session = makeImportSession(['status' => ImportSessionStatus::Queued]);

    Livewire::test(ViewImportSession::class, ['record' => $session->getRouteKey()])
        ->assertActionVisible('cancelSession')
        ->callAction('cancelSession');

    expect($session->refresh()->status)->toBe(ImportSessionStatus::Abandoned);
});

it('hides the retry action unless the session is failed', function (): void {
    $session = makeImportSession(['status' => ImportSessionStatus::Queued]);

    Livewire::test(ViewImportSession::class, ['record' => $session->getRouteKey()])
        ->assertActionHidden('retrySession');
});

it('hides the retry action when a failed session is missing its decisions', function (): void {
    $session = makeImportSession([
        'status' => ImportSessionStatus::Failed,
        'failure_reason' => 'boom',
        'resolution_map' => null,
        'page_decisions' => null,
        'relation_decisions' => null,
    ]);
    Storage::disk('local')->put((string) $session->source_package_path, 'zip-bytes');

    Livewire::test(ViewImportSession::class, ['record' => $session->getRouteKey()])
        ->assertActionHidden('retrySession');
});

it('retries a failed session, clearing the failure reason and dispatching the execute job', function (): void {
    $session = makeImportSession([
        'status' => ImportSessionStatus::Failed,
        'failure_reason' => 'previous failure',
        'resolution_map' => ['resolved' => [], 'unresolved' => []],
        'page_decisions' => ['uuid-1' => ['action' => 'create']],
        'relation_decisions' => ['site:1' => ['action' => 'use_existing']],
    ]);
    Storage::disk('local')->put((string) $session->source_package_path, 'zip-bytes');

    Livewire::test(ViewImportSession::class, ['record' => $session->getRouteKey()])
        ->assertActionVisible('retrySession')
        ->callAction('retrySession');

    $session->refresh();

    expect($session->status)->toBe(ImportSessionStatus::Queued)
        ->and($session->failure_reason)->toBeNull();

    Queue::assertPushed(ExecuteImportPlanJob::class, 1);
});
