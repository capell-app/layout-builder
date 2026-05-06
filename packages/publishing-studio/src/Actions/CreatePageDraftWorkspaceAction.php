<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions;

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Enums\WorkspaceKindEnum;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class CreatePageDraftWorkspaceAction
{
    use AsAction;

    public function handle(Page $page, AuthenticatedUser $user): Workspace
    {
        $timestamp = now()->format('Y-m-d H:i');
        $name = sprintf('Draft: %s · %s', $page->name, $timestamp);

        $workspace = new Workspace([
            'name' => $name,
            'slug' => Str::slug($name . ' ' . Str::random(6)),
            'status' => WorkspaceStatusEnum::Open->value,
            'kind' => WorkspaceKindEnum::SinglePageDraft->value,
        ]);

        $workspace->created_by = $user->getKey();
        $workspace->updated_by = $user->getKey();
        $workspace->save();

        return $workspace;
    }
}
