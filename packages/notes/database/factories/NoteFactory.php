<?php

declare(strict_types=1);

namespace Capell\Notes\Database\Factories;

use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Enums\NoteVisibility;
use Capell\Notes\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = $this->userModel();

        return [
            'author_type' => (new $userModel)->getMorphClass(),
            'author_id' => $this->userFactory(),
            'body' => $this->faker->paragraph(),
            'status' => NoteStatus::Open,
            'visibility' => NoteVisibility::RecordEditors,
            'resolved_at' => null,
            'archived_at' => null,
        ];
    }

    public function private(): self
    {
        return $this->state([
            'visibility' => NoteVisibility::Private,
        ]);
    }

    public function resolved(): self
    {
        return $this->state([
            'status' => NoteStatus::Resolved,
            'resolved_at' => now(),
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
