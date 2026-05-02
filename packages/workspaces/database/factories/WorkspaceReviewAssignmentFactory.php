<?php

declare(strict_types=1);

namespace Capell\Workspaces\Database\Factories;

use Capell\Workspaces\Models\WorkspaceReviewAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceReviewAssignment>
 */
class WorkspaceReviewAssignmentFactory extends Factory
{
    protected $model = WorkspaceReviewAssignment::class;

    public function definition(): array
    {
        return [
            'workspace_id' => WorkspaceFactory::new(),
            'required_for' => 'publish',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
