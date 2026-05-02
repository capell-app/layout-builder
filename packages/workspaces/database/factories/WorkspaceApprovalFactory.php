<?php

declare(strict_types=1);

namespace Capell\Workspaces\Database\Factories;

use Capell\Workspaces\Enums\WorkspaceApprovalActionEnum;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Models\WorkspaceApproval;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<WorkspaceApproval>
 */
class WorkspaceApprovalFactory extends Factory
{
    protected $model = WorkspaceApproval::class;

    public function definition(): array
    {
        return [
            'workspace_id' => WorkspaceFactory::new(),
            'level' => 1,
            'action' => WorkspaceApprovalActionEnum::Submitted,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function workspace(Workspace $workspace): static
    {
        return $this->state(['workspace_id' => $workspace->id]);
    }

    public function actionable(Model $user): static
    {
        return $this->state([
            'actionable_id' => $user->getKey(),
            'actionable_type' => $user->getMorphClass(),
        ]);
    }

    public function submitted(): static
    {
        return $this->state(['action' => WorkspaceApprovalActionEnum::Submitted]);
    }

    public function approved(): static
    {
        return $this->state(['action' => WorkspaceApprovalActionEnum::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(['action' => WorkspaceApprovalActionEnum::Rejected]);
    }

    public function level(int $level): static
    {
        return $this->state(['level' => $level]);
    }
}
