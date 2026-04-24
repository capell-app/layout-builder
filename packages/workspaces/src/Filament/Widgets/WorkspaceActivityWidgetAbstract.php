<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Workspaces\Actions\Dashboard\BuildWorkspaceActivityAction;
use Capell\Workspaces\Data\Dashboard\WorkspaceActivityData;
use Capell\Workspaces\Data\Dashboard\WorkspaceMergeData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;
use Spatie\LaravelData\DataCollection;

final class WorkspaceActivityWidgetAbstract extends Widget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'workspace_activity';

    protected string $view = 'capell-workspaces::widgets.workspace-activity';

    #[Computed(persist: true, seconds: 60)]
    public function data(): WorkspaceActivityData
    {
        $user = auth()->user();

        if ($user === null) {
            return new WorkspaceActivityData(
                pendingApprovalsCount: 0,
                stuckCount: 0,
                recentMerges: WorkspaceMergeData::collect([], DataCollection::class),
            );
        }

        return BuildWorkspaceActivityAction::run($user);
    }
}
