<?php

declare(strict_types=1);

use Capell\Notes\Actions\CreateNoteAction;
use Capell\Notes\Data\CreateNoteData;
use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Enums\NoteVisibility;
use Capell\Notes\Models\Note;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Validation\ValidationException;

require_once dirname(__DIR__, 2) . '/NotesTestCase.php';

it('creates a note attached to a record with assignments and mentions', function (): void {
    $subject = User::factory()->create();
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $mentioned = User::factory()->create();

    $note = CreateNoteAction::run(new CreateNoteData(
        subject: $subject,
        author: $author,
        body: 'Update this content before campaign launch.',
        visibility: NoteVisibility::RecordEditors,
        assignees: [$assignee],
        mentions: [$mentioned],
    ));

    expect($note->refresh()->subject->is($subject))->toBeTrue()
        ->and($note->author->is($author))->toBeTrue()
        ->and($note->body)->toBe('Update this content before campaign launch.')
        ->and($note->status)->toBe(NoteStatus::Open)
        ->and($note->visibility)->toBe(NoteVisibility::RecordEditors)
        ->and($note->resolved_at)->toBeNull()
        ->and($note->assignments)->toHaveCount(1)
        ->and($note->assignments->first()->assignee->is($assignee))->toBeTrue()
        ->and($note->assignments->first()->assignedBy->is($author))->toBeTrue()
        ->and($note->mentions)->toHaveCount(1)
        ->and($note->mentions->first()->mentioned->is($mentioned))->toBeTrue()
        ->and($note->mentions->first()->mentionedBy->is($author))->toBeTrue();
});

it('rejects blank note bodies', function (): void {
    $subject = User::factory()->create();
    $author = User::factory()->create();

    expect(fn (): mixed => CreateNoteAction::run(new CreateNoteData(
        subject: $subject,
        author: $author,
        body: '  ',
    )))->toThrow(ValidationException::class);
});

it('rolls back the note when assignment creation fails', function (): void {
    $subject = User::factory()->create();
    $author = User::factory()->create();
    $failingParticipant = new class extends User
    {
        public function getMorphClass(): string
        {
            throw new RuntimeException('Participant failed');
        }
    };

    expect(fn (): mixed => CreateNoteAction::run(new CreateNoteData(
        subject: $subject,
        author: $author,
        body: 'Assign this note.',
        assignees: [User::factory()->create(), $failingParticipant],
    )))->toThrow(RuntimeException::class, 'Participant failed');

    expect(Note::query()->count())->toBe(0);
});

it('rolls back the note when mention creation fails', function (): void {
    $subject = User::factory()->create();
    $author = User::factory()->create();
    $failingParticipant = new class extends User
    {
        public function getMorphClass(): string
        {
            throw new RuntimeException('Participant failed');
        }
    };

    expect(fn (): mixed => CreateNoteAction::run(new CreateNoteData(
        subject: $subject,
        author: $author,
        body: 'Mention someone on this note.',
        mentions: [User::factory()->create(), $failingParticipant],
    )))->toThrow(RuntimeException::class, 'Participant failed');

    expect(Note::query()->count())->toBe(0);
});
