<?php

declare(strict_types=1);

use Capell\Notes\Actions\AssignNoteUsersAction;
use Capell\Notes\Actions\CompleteNoteAssignmentAction;
use Capell\Notes\Actions\MentionNoteUsersAction;
use Capell\Notes\Actions\ReopenNoteAction;
use Capell\Notes\Actions\ResolveNoteAction;
use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Models\Note;
use Capell\Tests\Fixtures\Models\User;

require_once dirname(__DIR__, 2) . '/NotesTestCase.php';

it('assigning the same user twice does not duplicate assignment', function (): void {
    $note = Note::factory()->create();
    $assignee = User::factory()->create();
    $assignedBy = User::factory()->create();

    AssignNoteUsersAction::run($note, [$assignee], assignedBy: $assignedBy);
    AssignNoteUsersAction::run($note, [$assignee], assignedBy: $assignedBy);

    expect($note->assignments()->whereMorphedTo('assignee', $assignee)->count())->toBe(1)
        ->and($note->assignments()->first()->assignedBy->is($assignedBy))->toBeTrue();
});

it('mentioning the same user twice does not duplicate mention', function (): void {
    $note = Note::factory()->create();
    $mentioned = User::factory()->create();
    $mentionedBy = User::factory()->create();

    MentionNoteUsersAction::run($note, [$mentioned], mentionedBy: $mentionedBy);
    MentionNoteUsersAction::run($note, [$mentioned], mentionedBy: $mentionedBy);

    expect($note->mentions()->whereMorphedTo('mentioned', $mentioned)->count())->toBe(1)
        ->and($note->mentions()->first()->mentionedBy->is($mentionedBy))->toBeTrue();
});

it('rolls back standalone assignment batches when a later assignee fails', function (): void {
    $note = Note::factory()->create();
    $assignee = User::factory()->create();
    $failingParticipant = new class extends User
    {
        public function getMorphClass(): string
        {
            throw new RuntimeException('Participant failed');
        }
    };

    expect(fn (): mixed => AssignNoteUsersAction::run($note, [$assignee, $failingParticipant], assignedBy: null))
        ->toThrow(RuntimeException::class, 'Participant failed');

    expect($note->assignments()->count())->toBe(0);
});

it('rolls back standalone mention batches when a later mention fails', function (): void {
    $note = Note::factory()->create();
    $mentioned = User::factory()->create();
    $failingParticipant = new class extends User
    {
        public function getMorphClass(): string
        {
            throw new RuntimeException('Participant failed');
        }
    };

    expect(fn (): mixed => MentionNoteUsersAction::run($note, [$mentioned, $failingParticipant], mentionedBy: null))
        ->toThrow(RuntimeException::class, 'Participant failed');

    expect($note->mentions()->count())->toBe(0);
});

it('completes only the current assignee assignment', function (): void {
    $note = Note::factory()->create();
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    AssignNoteUsersAction::run($note, [$firstUser, $secondUser], assignedBy: null);

    CompleteNoteAssignmentAction::run($note, $firstUser);

    expect($note->assignments()->whereMorphedTo('assignee', $firstUser)->first()->completed_at)->not->toBeNull()
        ->and($note->assignments()->whereMorphedTo('assignee', $secondUser)->first()->completed_at)->toBeNull();
});

it('reactivates a completed assignment when the user is assigned again', function (): void {
    $note = Note::factory()->create();
    $assignee = User::factory()->create();

    AssignNoteUsersAction::run($note, [$assignee], assignedBy: null);
    CompleteNoteAssignmentAction::run($note, $assignee);

    expect($note->assignments()->whereMorphedTo('assignee', $assignee)->first()->completed_at)->not->toBeNull();

    AssignNoteUsersAction::run($note, [$assignee], assignedBy: null);

    expect($note->assignments()->whereMorphedTo('assignee', $assignee)->first()->completed_at)->toBeNull();
});

it('reactivates a read mention when the user is mentioned again', function (): void {
    $note = Note::factory()->create();
    $mentioned = User::factory()->create();

    MentionNoteUsersAction::run($note, [$mentioned], mentionedBy: null);

    $note->mentions()->whereMorphedTo('mentioned', $mentioned)->update(['read_at' => now()]);

    expect($note->mentions()->whereMorphedTo('mentioned', $mentioned)->first()->read_at)->not->toBeNull();

    MentionNoteUsersAction::run($note, [$mentioned], mentionedBy: null);

    expect($note->mentions()->whereMorphedTo('mentioned', $mentioned)->first()->read_at)->toBeNull();
});

it('resolving and reopening note updates status and timestamps correctly', function (): void {
    $note = Note::factory()->create();

    ResolveNoteAction::run($note);

    expect($note->refresh()->status)->toBe(NoteStatus::Resolved)
        ->and($note->resolved_at)->not->toBeNull();

    ReopenNoteAction::run($note);

    expect($note->refresh()->status)->toBe(NoteStatus::Open)
        ->and($note->resolved_at)->toBeNull();
});
