<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Actions\Workflow;

use Capell\Core\Contracts\Extensions\ContributesWorkflowAttention;
use Capell\Core\Data\Workflow\WorkflowAttentionItemData;
use Capell\PublishingStudio\Filament\Pages\PublishingWorkflowPage;
use Capell\PublishingStudio\Support\WorkspaceSchema;
use Illuminate\Contracts\Auth\Authenticatable;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildPublishingWorkflowAttentionItemsAction implements ContributesWorkflowAttention
{
    use AsAction;

    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }

    /**
     * @return list<WorkflowAttentionItemData>
     */
    public function handle(?Authenticatable $user = null): array
    {
        return $this->attentionItems($user);
    }

    /**
     * @return list<WorkflowAttentionItemData>
     */
    public function attentionItems(?Authenticatable $user = null): array
    {
        if (! WorkspaceSchema::isReady()) {
            return [];
        }

        $count = app(BuildPublishingWorkflowCommandCenterAction::class)->attentionCount($user);

        return [
            new WorkflowAttentionItemData(
                packageName: 'capell-app/publishing-studio',
                label: (string) __('capell-publishing-studio::workflow.dashboard.label'),
                severity: $count > 0 ? 'warning' : 'info',
                owner: (string) __('capell-publishing-studio::workflow.owner'),
                nextActionLabel: (string) __('capell-publishing-studio::workflow.dashboard.action'),
                url: PublishingWorkflowPage::getUrl(),
                permission: 'View:PublishingWorkflowPage',
                count: $count,
            ),
        ];
    }
}
