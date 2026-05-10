<?php

declare(strict_types=1);

use Capell\Notes\Data\NoteReminderData;
use Capell\Notes\Data\UserAttentionCountData;
use Capell\Notes\Enums\NoteReminderRecurrence;
use Capell\Notes\Tests\NotesTestCase;
use Carbon\CarbonImmutable;

require_once dirname(__DIR__, 2) . '/NotesTestCase.php';

uses(NotesTestCase::class);

it('carries reminder scheduling input as typed data', function (): void {
    $dueAt = CarbonImmutable::parse('2026-06-30 09:00:00', 'Europe/London');

    $data = new NoteReminderData(
        dueAt: $dueAt,
        recurrence: NoteReminderRecurrence::Monthly,
        timezone: 'Europe/London',
    );

    expect($data->dueAt)->toBe($dueAt)
        ->and($data->recurrence)->toBe(NoteReminderRecurrence::Monthly)
        ->and($data->timezone)->toBe('Europe/London');
});

it('carries user attention counts as typed data', function (): void {
    $data = new UserAttentionCountData(
        assigned: 2,
        dueToday: 3,
        overdue: 1,
        mentions: 4,
    );

    expect($data->assigned)->toBe(2)
        ->and($data->dueToday)->toBe(3)
        ->and($data->overdue)->toBe(1)
        ->and($data->mentions)->toBe(4)
        ->and($data->total())->toBe(10);
});
