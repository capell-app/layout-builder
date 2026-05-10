<?php

declare(strict_types=1);

namespace Capell\Notes\Database\Factories;

use Capell\Notes\Enums\NoteReminderRecurrence;
use Capell\Notes\Models\Note;
use Capell\Notes\Models\NoteReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoteReminder>
 */
class NoteReminderFactory extends Factory
{
    protected $model = NoteReminder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueAt = $this->faker->dateTimeBetween('now', '+1 month');

        return [
            'note_id' => Note::factory(),
            'due_at' => $dueAt,
            'timezone' => 'UTC',
            'recurrence' => NoteReminderRecurrence::None,
            'next_due_at' => $dueAt,
            'last_notified_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function recurring(NoteReminderRecurrence $recurrence = NoteReminderRecurrence::Weekly): self
    {
        return $this->state([
            'recurrence' => $recurrence,
        ]);
    }
}
