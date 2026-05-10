<?php

declare(strict_types=1);

namespace Capell\Notes\Data;

use Capell\Notes\Enums\NoteVisibility;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

final class CreateNoteData extends Data
{
    /**
     * @param  list<Model>  $assignees
     * @param  list<Model>  $mentions
     */
    public function __construct(
        public readonly Model $subject,
        public readonly Model $author,
        public readonly string $body,
        public readonly NoteVisibility $visibility = NoteVisibility::RecordEditors,
        public readonly array $assignees = [],
        public readonly array $mentions = [],
    ) {}
}
