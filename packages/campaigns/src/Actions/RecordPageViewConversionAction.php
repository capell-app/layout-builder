<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Enums\ConversionGoalType;
use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignLandingPage;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordPageViewConversionAction
{
    use AsAction;

    public function handle(CampaignLandingPage $landingPage, ?Model $visit = null, ?Model $event = null): ?CampaignConversion
    {
        $goal = $landingPage->primaryGoal;

        if (! $goal instanceof CampaignConversionGoal || $goal->type !== ConversionGoalType::PageView) {
            return null;
        }

        return RecordCampaignConversionAction::run($goal, $visit, $event, $landingPage);
    }
}
