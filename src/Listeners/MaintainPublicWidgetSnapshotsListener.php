<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Listeners;

use Capell\Core\Events\PageDeleted;
use Capell\Core\Events\PageSaved;
use Capell\Core\EventSourcing\Enums\PageWorkflowStatus;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\PageWorkflowState;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RebuildPublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RevokePublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Throwable;

final readonly class MaintainPublicWidgetSnapshotsListener
{
    public function __construct(
        private RebuildPublicWidgetSnapshotsAction $rebuilder,
        private RevokePublicWidgetSnapshotsAction $revoker,
    ) {}

    public function handleSaved(PageSaved $event): void
    {
        if (! CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName)) {
            return;
        }

        try {
            $page = $event->page;
            if (! $page instanceof Model || $this->isNotPublic($page)) {
                RevokePublicWidgetSnapshotsAction::run($page);

                return;
            }

            $site = Site::query()->find($page->getAttribute('site_id'));
            if (! $site instanceof Site) {
                return;
            }
            $layoutIdentifier = $page->getAttribute('layout_id');
            $layout = is_int($layoutIdentifier) ? Layout::query()->find($layoutIdentifier) : null;
            if (! $layout instanceof Layout) {
                $layout = null;
            }
            $theme = $site->theme()->first();

            foreach ($page->translations()->get() as $translation) {
                $language = Language::query()->find($translation->getAttribute('language_id'));
                if (! $language instanceof Language) {
                    continue;
                }

                $page->setRelation('translation', $translation);
                RebuildPublicWidgetSnapshotsAction::run(new FrontendRenderContextData($page, $site, $language, $layout, $theme));
            }
        } catch (Throwable $throwable) {
            // Snapshot generation is auxiliary. Publishing ordinary public HTML
            // must remain available if it fails.
            report($throwable);
        }
    }

    public function handleDeleted(PageDeleted $event): void
    {
        if (! CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName)) {
            return;
        }

        try {
            RevokePublicWidgetSnapshotsAction::run($event->page);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    private function isNotPublic(Model $page): bool
    {
        $pageUuid = $page->getAttribute('uuid');
        if (is_string($pageUuid) && $pageUuid !== '') {
            $workflow = PageWorkflowState::query()->where('page_uuid', $pageUuid)->first();
            if ($workflow instanceof PageWorkflowState && $workflow->status !== PageWorkflowStatus::Published) {
                return true;
            }
        }

        return (method_exists($page, 'isPending') && $page->isPending())
            || (method_exists($page, 'isExpired') && $page->isExpired())
            || $page->getAttribute('deleted_at') !== null;
    }
}
