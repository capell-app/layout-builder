<?php

declare(strict_types=1);

namespace Capell\Notes\Actions;

use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Models\Note;
use Lorisleiva\Actions\Concerns\AsObject;

class ResolveNoteAction
{
    use AsObject;

    public function handle(Note $note): Note
    {
        $note->forceFill([
            'status' => NoteStatus::Resolved,
            'resolved_at' => now(),
        ])->save();

        return $note;
    }
}
