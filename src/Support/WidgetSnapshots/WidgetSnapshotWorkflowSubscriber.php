<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetSnapshots;

use Capell\Core\Contracts\EventSubscriber;
use Capell\Core\Enums\ListenerEnum;
use Capell\Core\EventSourcing\Events\PageArchived;
use Capell\Core\EventSourcing\Events\PageUnpublished;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\RevokePublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final readonly class WidgetSnapshotWorkflowSubscriber implements EventSubscriber
{
    public function __construct(private RevokePublicWidgetSnapshotsAction $revoker) {}

    public function handle(string $event, object $context): void
    {
        if (! CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName)) {
            return;
        }

        if (! in_array($event, [ListenerEnum::PageUnpublished->value, ListenerEnum::PageArchived->value], true)
            || ! $context instanceof PageUnpublished && ! $context instanceof PageArchived) {
            return;
        }

        $aggregateUuid = $this->aggregateUuid($context);
        if ($aggregateUuid === null) {
            return;
        }

        $page = Page::query()->where('uuid', $aggregateUuid)->first();
        if ($page instanceof Page) {
            RevokePublicWidgetSnapshotsAction::run($page);
        }
    }

    private function aggregateUuid(ShouldBeStored $event): ?string
    {
        return $event->aggregateRootUuid();
    }
}
