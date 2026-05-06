<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Exceptions\EmbargoActiveException;
use Capell\PublishingStudio\Exceptions\ReleaseWindowClosedException;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Publishes every workspace whose `publish_at` has elapsed. PublishingStudio that
 * fall outside the release window are left in the Scheduled state so the
 * next tick can retry; unrecoverable failures are reported and the workspace
 * is left in Scheduled for manual intervention.
 */
class PublishScheduledPublishingStudioJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(Publisher $publisher): void
    {
        Workspace::query()
            ->where('status', WorkspaceStatusEnum::Scheduled->value)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->oldest('publish_at')
            ->each(function (Workspace $workspace) use ($publisher): void {
                try {
                    $publisher->publish($workspace);
                } catch (EmbargoActiveException) {
                    // Leave Scheduled — next tick will retry once the embargo has passed.
                } catch (ReleaseWindowClosedException) {
                    // Leave Scheduled — next tick will retry once the window opens.
                } catch (Throwable $failure) {
                    report($failure);
                }
            });
    }
}
