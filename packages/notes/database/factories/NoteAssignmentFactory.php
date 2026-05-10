<?php

declare(strict_types=1);

namespace Capell\Notes\Database\Factories;

use Capell\Notes\Models\Note;
use Capell\Notes\Models\NoteAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * @extends Factory<NoteAssignment>
 */
class NoteAssignmentFactory extends Factory
{
    protected $model = NoteAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = $this->userModel();

        return [
            'note_id' => Note::factory(),
            'assignee_type' => (new $userModel)->getMorphClass(),
            'assignee_id' => $this->userFactory(),
            'assigned_by_type' => (new $userModel)->getMorphClass(),
            'assigned_by_id' => $this->userFactory(),
            'completed_at' => null,
        ];
    }

    public function completed(): self
    {
        return $this->state([
            'completed_at' => now(),
        ]);
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        $userModel = config('auth.providers.users.model');

        throw_if(! is_string($userModel) || ! is_subclass_of($userModel, Model::class), RuntimeException::class, 'The configured auth user provider model must be an Eloquent model.');

        return $userModel;
    }

    /**
     * @return Factory<Model>
     */
    private function userFactory(): Factory
    {
        $factory = forward_static_call([$this->userModel(), 'factory']);

        throw_unless($factory instanceof Factory, RuntimeException::class, 'The configured auth user provider model must expose an Eloquent factory.');

        return $factory;
    }
}
