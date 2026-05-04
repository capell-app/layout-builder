<?php

declare(strict_types=1);

use Capell\Migrator\Enums\ImportSessionKind;
use Capell\Migrator\Enums\ImportSessionStatus;
use Capell\Migrator\Events\ImportCompleted;
use Capell\Migrator\Events\ImportFailed;
use Capell\Migrator\Listeners\SendImportSessionNotifications;
use Capell\Migrator\Models\ImportSession;
use Capell\Migrator\Notifications\ImportCompletedNotification;
use Capell\Migrator\Notifications\ImportFailedNotification;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function (): void {
    if (! class_exists(ImportSession::class)) {
        test()->markTestSkipped('capell-app/migrator is not installed in this checkout.');
    }
});

function makeImportSessionForNotification(?int $userId = null): ImportSession
{
    return ImportSession::query()->create([
        'uuid' => (string) Str::uuid(),
        'user_id' => $userId,
        'kind' => ImportSessionKind::PageImport,
        'status' => ImportSessionStatus::Completed,
        'source_filename' => 'package.zip',
        'source_package_path' => 'migrator/imports/package.zip',
        'result_summary' => ['pages_created' => 5, 'relations_resolved' => 2],
    ]);
}

it('notifies the initiating user on a completed import via mail and database channels', function (): void {
    Notification::fake();

    $initiator = User::factory()->create();
    $session = makeImportSessionForNotification($initiator->getKey());

    (new SendImportSessionNotifications)->handleCompleted(new ImportCompleted($session));

    Notification::assertSentTo(
        $initiator,
        ImportCompletedNotification::class,
        function (ImportCompletedNotification $notification, array $channels): true {
            expect($channels)->toEqualCanonicalizing(['mail', 'database']);

            return true;
        },
    );
});

it('notifies the initiating user on a failed import', function (): void {
    Notification::fake();

    $initiator = User::factory()->create();
    $session = makeImportSessionForNotification($initiator->getKey());

    (new SendImportSessionNotifications)->handleFailed(new ImportFailed($session, 'checksum mismatch'));

    Notification::assertSentTo(
        $initiator,
        ImportFailedNotification::class,
        function (ImportFailedNotification $notification, array $channels): bool {
            $payload = $notification->toArray(new User);
            expect($payload['failure_reason'])->toBe('checksum mismatch');

            return true;
        },
    );
});

it('honours channel preferences and sends only the database channel when mail is disabled', function (): void {
    Notification::fake();

    config()->set('migrator.notifications.channels', ['database']);

    $initiator = User::factory()->create();
    $session = makeImportSessionForNotification($initiator->getKey());

    (new SendImportSessionNotifications)->handleCompleted(new ImportCompleted($session));

    Notification::assertSentTo(
        $initiator,
        ImportCompletedNotification::class,
        function (ImportCompletedNotification $notification, array $channels): bool {
            expect($channels)->toBe(['database']);

            return true;
        },
    );
});

it('sends nothing when migrator notifications are disabled globally', function (): void {
    Notification::fake();

    config()->set('migrator.notifications.enabled', false);

    $initiator = User::factory()->create();
    $session = makeImportSessionForNotification($initiator->getKey());

    (new SendImportSessionNotifications)->handleCompleted(new ImportCompleted($session));
    (new SendImportSessionNotifications)->handleFailed(new ImportFailed($session, 'x'));

    Notification::assertNothingSent();
});

it('skips delivery when the session has no initiating user and no recipient roles', function (): void {
    Notification::fake();

    $session = makeImportSessionForNotification();

    (new SendImportSessionNotifications)->handleCompleted(new ImportCompleted($session));

    Notification::assertNothingSent();
});
