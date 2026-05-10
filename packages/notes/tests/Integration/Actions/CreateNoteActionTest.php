<?php

declare(strict_types=1);

use Capell\Notes\Actions\CreateNoteAction;
use Capell\Notes\Data\CreateNoteData;
use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Enums\NoteVisibility;
use Capell\Notes\Tests\NotesTestCase;
use Capell\Tests\Fixtures\Models\User;

require_once dirname(__DIR__, 2) . '/NotesTestCase.php';

uses(NotesTestCase::class);

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
