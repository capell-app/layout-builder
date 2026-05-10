<?php

declare(strict_types=1);

namespace Capell\Notes\Actions;

use Capell\Notes\Data\CreateNoteData;
use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Models\Note;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

class CreateNoteAction
{
    use AsObject;

    public function handle(CreateNoteData $data): Note
    {
        return DB::transaction(function () use ($data): Note {
            $note = Note::query()->create([
                'subject_type' => $data->subject->getMorphClass(),
                'subject_id' => $data->subject->getKey(),
                'author_type' => $data->author->getMorphClass(),
                'author_id' => $data->author->getKey(),
                'body' => $data->body,
                'status' => NoteStatus::Open,
                'visibility' => $data->visibility,
                'resolved_at' => null,
            ]);

            AssignNoteUsersAction::run($note, $data->assignees, assignedBy: $data->author);
            MentionNoteUsersAction::run($note, $data->mentions, mentionedBy: $data->author);

            return $note->load([
                'assignments.assignee',
                'assignments.assignedBy',
                'author',
                'mentions.mentioned',
                'mentions.mentionedBy',
                'subject',
            ]);
        });
    }
}
