<?php

declare(strict_types=1);

namespace Capell\Notes\Database\Factories;

use Capell\Notes\Models\Note;
use Capell\Notes\Models\NoteMention;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * @extends Factory<NoteMention>
 */
class NoteMentionFactory extends Factory
{
    protected $model = NoteMention::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = $this->userModel();

        return [
            'note_id' => Note::factory(),
            'mentioned_type' => (new $userModel)->getMorphClass(),
            'mentioned_id' => $this->userFactory(),
            'mentioned_by_type' => (new $userModel)->getMorphClass(),
            'mentioned_by_id' => $this->userFactory(),
            'read_at' => null,
        ];
    }

    public function read(): self
    {
        return $this->state([
            'read_at' => now(),
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
