<?php

declare(strict_types=1);

namespace Capell\Notes\Actions;

use Capell\Notes\Models\Note;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

class MentionNoteUsersAction
{
    use AsObject;

    /**
     * @param  list<Model>  $mentions
     */
    public function handle(Note $note, array $mentions, ?Model $mentionedBy = null): void
    {
        DB::transaction(function () use ($note, $mentions, $mentionedBy): void {
            foreach ($mentions as $mentioned) {
                $note->mentions()->updateOrCreate(
                    [
                        'mentioned_type' => $mentioned->getMorphClass(),
                        'mentioned_id' => $mentioned->getKey(),
                    ],
                    [
                        'mentioned_by_type' => $mentionedBy?->getMorphClass(),
                        'mentioned_by_id' => $mentionedBy?->getKey(),
                    ],
                );
            }
        });
    }
}
