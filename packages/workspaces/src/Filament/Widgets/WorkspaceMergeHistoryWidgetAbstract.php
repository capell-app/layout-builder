<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Widgets;

use Capell\Admin\Concerns\CachesDashboardQuery;
use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Workspaces\Actions\Dashboard\BuildWorkspaceMergeHistoryAction;
use Capell\Workspaces\Data\Dashboard\WorkspaceMergeHistoryData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class WorkspaceMergeHistoryWidgetAbstract extends Widget implements CapellWidgetContract
{
    use CachesDashboardQuery;
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['super_admin'];

    protected static string $settingsKey = 'workspace_merge_history';

    protected string $view = 'capell-workspaces::widgets.workspace-merge-history';

    /** @var int|string|array<mixed> */
    protected int|string|array $columnSpan = 2;

    #[Computed(persist: true, seconds: 300)]
    public function data(): WorkspaceMergeHistoryData
    {
        return $this->cacheQueryResult(
            fn (): WorkspaceMergeHistoryData => BuildWorkspaceMergeHistoryAction::run(),
            'dashboard:workspace-merge-history',
        );
    }
}
