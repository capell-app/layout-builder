<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Publishing;

use Capell\ThemeStudio\Admin\Contracts\ThemeDraftPublisher;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\Workspaces\Enums\WorkspaceKindEnum;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

class WorkspaceThemeDraftPublisher implements ThemeDraftPublisher
{
    public function publish(ThemeStudioSettings $settings): ThemeStudioSettings
    {
        $user = Auth::user();

        throw_unless($user instanceof AuthenticatedUser, RuntimeException::class, 'A signed-in user is required to submit Theme Studio drafts for Workspaces approval.');

        $workspace = $this->workspaceFor($settings);

        if ($workspace->status === WorkspaceStatusEnum::Open) {
            $workspace->submitForApproval(
                $user,
                sprintf(
                    'Theme Studio draft submitted for approval: %s / %s.',
                    $settings->draftTheme,
                    $settings->draftPreset,
                ),
            );
        }

        $settings->draftWorkspaceId = (int) $workspace->getKey();
        $settings->save();

        return $settings;
    }

    public function requiresApproval(): bool
    {
        return true;
    }

    private function workspaceFor(ThemeStudioSettings $settings): Workspace
    {
        if ($settings->draftWorkspaceId !== null) {
            $existing = Workspace::query()->find($settings->draftWorkspaceId);

            if ($existing instanceof Workspace && $existing->status->isInApprovalPipeline()) {
                return $existing;
            }
        }

        $draftName = sprintf('Theme Studio: %s / %s', $settings->draftTheme, $settings->draftPreset);

        return Workspace::query()->create([
            'name' => $draftName,
            'slug' => Str::slug($draftName . ' ' . Str::random(6)),
            'description' => 'Theme Studio draft approval generated from the commercial Theme Studio admin.',
            'status' => WorkspaceStatusEnum::Open,
            'kind' => WorkspaceKindEnum::Manual,
        ]);
    }
}
