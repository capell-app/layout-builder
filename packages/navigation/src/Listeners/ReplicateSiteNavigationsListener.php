<?php

declare(strict_types=1);

namespace Capell\Navigation\Listeners;

use Capell\Core\Events\SiteReplicated;
use Capell\Navigation\Actions\ReplicateSiteNavigationsAction;

class ReplicateSiteNavigationsListener
{
    public function handle(SiteReplicated $event): void
    {
        if (($event->formData['copy_navigations'] ?? null) !== true) {
            return;
        }

        ReplicateSiteNavigationsAction::run(
            $event->source,
            $event->replica,
            $event->replacementPages,
        );
    }
}
