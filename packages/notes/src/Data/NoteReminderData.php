<?php

declare(strict_types=1);

namespace Capell\Notes\Data;

use Capell\Notes\Enums\NoteReminderRecurrence;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class NoteReminderData extends Data
{
    public function __construct(
        public readonly ?CarbonImmutable $dueAt,
        public readonly NoteReminderRecurrence $recurrence = NoteReminderRecurrence::None,
        public readonly string $timezone = 'UTC',
    ) {}
}
