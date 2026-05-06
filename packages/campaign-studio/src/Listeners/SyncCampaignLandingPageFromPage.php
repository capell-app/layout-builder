<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Listeners;

use Capell\CampaignStudio\Actions\SyncCampaignLandingPageFromPageAction;
use Capell\Core\Events\PageSaved;

final class SyncCampaignLandingPageFromPage
{
    public function handle(PageSaved $event): void
    {
        SyncCampaignLandingPageFromPageAction::run($event->page);
    }
}
