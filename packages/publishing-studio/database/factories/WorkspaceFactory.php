<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Database\Factories;

use Capell\PublishingStudio\Enums\WorkspaceKindEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition(): array
    {
        $name = fake()->sentence(3);

        return [
            'name' => $name,
            'description' => fake()->optional()->sentence(),
            'color' => fake()->optional()->hexColor(),
            'status' => WorkspaceStatusEnum::Open,
            'kind' => WorkspaceKindEnum::Manual,
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => WorkspaceStatusEnum::Open]);
    }

    public function approved(): static
    {
        return $this->state(['status' => WorkspaceStatusEnum::Approved]);
    }

    public function inReview(): static
    {
        return $this->state(['status' => WorkspaceStatusEnum::InReview]);
    }

    public function scheduled(DateTimeInterface|string $publishAt): static
    {
        return $this->state([
            'status' => WorkspaceStatusEnum::Scheduled,
            'publish_at' => $publishAt instanceof DateTimeInterface ? $publishAt : CarbonImmutable::parse($publishAt),
        ]);
    }

    public function published(): static
    {
        return $this->state([
            'status' => WorkspaceStatusEnum::Published,
            'published_at' => CarbonImmutable::now(),
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(['status' => WorkspaceStatusEnum::Abandoned]);
    }
}
