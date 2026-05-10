<?php

declare(strict_types=1);

use Capell\Notes\Enums\NoteReminderRecurrence;
use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Enums\NoteVisibility;
use Capell\Notes\Models\Note;
use Capell\Notes\Models\NoteAssignment;
use Capell\Notes\Models\NoteMention;
use Capell\Notes\Models\NoteReminder;
use Capell\Notes\Tests\NotesTestCase;
use Capell\Tests\Fixtures\Models\User;
use Carbon\CarbonImmutable;

require_once dirname(__DIR__) . '/NotesTestCase.php';

uses(NotesTestCase::class);

it('casts note enums and immutable dates', function (): void {
    $note = Note::factory()->create([
        'status' => NoteStatus::Resolved,
        'visibility' => NoteVisibility::Private,
        'resolved_at' => now(),
        'archived_at' => now(),
    ]);

    expect($note->refresh()->status)->toBe(NoteStatus::Resolved)
        ->and($note->visibility)->toBe(NoteVisibility::Private)
        ->and($note->resolved_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($note->archived_at)->toBeInstanceOf(CarbonImmutable::class);
});

it('creates note relationships through factories', function (): void {
    $subject = User::factory()->create();
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $assignedBy = User::factory()->create();
    $mentioned = User::factory()->create();
    $mentionedBy = User::factory()->create();

    $note = Note::factory()->create([
        'subject_type' => $subject->getMorphClass(),
        'subject_id' => $subject->getKey(),
        'author_type' => $author->getMorphClass(),
        'author_id' => $author->getKey(),
    ]);

    $assignment = NoteAssignment::factory()->completed()->create([
        'note_id' => $note->id,
        'assignee_type' => $assignee->getMorphClass(),
        'assignee_id' => $assignee->getKey(),
        'assigned_by_type' => $assignedBy->getMorphClass(),
        'assigned_by_id' => $assignedBy->getKey(),
    ]);

    $mention = NoteMention::factory()->read()->create([
        'note_id' => $note->id,
        'mentioned_type' => $mentioned->getMorphClass(),
        'mentioned_id' => $mentioned->getKey(),
        'mentioned_by_type' => $mentionedBy->getMorphClass(),
        'mentioned_by_id' => $mentionedBy->getKey(),
    ]);

    $reminder = NoteReminder::factory()
        ->recurring(NoteReminderRecurrence::Weekly)
        ->create(['note_id' => $note->id]);

    expect($note->refresh()->subject->is($subject))->toBeTrue()
        ->and($note->author->is($author))->toBeTrue()
        ->and($note->assignments)->toHaveCount(1)
        ->and($note->mentions)->toHaveCount(1)
        ->and($note->reminder->is($reminder))->toBeTrue()
        ->and($assignment->refresh()->note->is($note))->toBeTrue()
        ->and($assignment->assignee->is($assignee))->toBeTrue()
        ->and($assignment->assignedBy->is($assignedBy))->toBeTrue()
        ->and($assignment->completed_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($mention->refresh()->note->is($note))->toBeTrue()
        ->and($mention->mentioned->is($mentioned))->toBeTrue()
        ->and($mention->mentionedBy->is($mentionedBy))->toBeTrue()
        ->and($mention->read_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($reminder->refresh()->note->is($note))->toBeTrue()
        ->and($reminder->recurrence)->toBe(NoteReminderRecurrence::Weekly)
        ->and($reminder->due_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($reminder->next_due_at)->toBeInstanceOf(CarbonImmutable::class);
});
