<?php

declare(strict_types=1);

namespace Capell\Notes\Actions;

use Capell\Notes\Models\Note;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

class AssignNoteUsersAction
{
    use AsObject;

    /**
     * @param  list<Model>  $assignees
     */
    public function handle(Note $note, array $assignees, ?Model $assignedBy = null): void
    {
        DB::transaction(function () use ($note, $assignees, $assignedBy): void {
            foreach ($assignees as $assignee) {
                $note->assignments()->updateOrCreate(
                    [
                        'assignee_type' => $assignee->getMorphClass(),
                        'assignee_id' => $assignee->getKey(),
                    ],
                    [
                        'assigned_by_type' => $assignedBy?->getMorphClass(),
                        'assigned_by_id' => $assignedBy?->getKey(),
                    ],
                );
            }
        });
    }
}
