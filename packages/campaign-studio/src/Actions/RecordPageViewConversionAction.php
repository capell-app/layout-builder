<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Enums\ConversionGoalType;
use Capell\CampaignStudio\Models\CampaignConversion;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Capell\CampaignStudio\Models\CampaignLandingPage;
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
