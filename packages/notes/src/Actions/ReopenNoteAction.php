<?php

declare(strict_types=1);

namespace Capell\Notes\Actions;

use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Models\Note;
use Lorisleiva\Actions\Concerns\AsObject;

class ReopenNoteAction
{
    use AsObject;

    public function handle(Note $note): Note
    {
        $note->forceFill([
            'status' => NoteStatus::Open,
            'resolved_at' => null,
        ])->save();

        return $note;
    }
}
