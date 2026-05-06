<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Data\ConversionAttributionData;
use Capell\CampaignStudio\Enums\ConversionGoalType;
use Capell\CampaignStudio\Models\CampaignConversion;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordFormSubmissionConversionAction
{
    use AsAction;

    public function handle(
        string $formTarget,
        ?Model $visit = null,
        ?Model $event = null,
        ?CampaignLandingPage $landingPage = null,
        ?Model $source = null,
        ?ConversionAttributionData $attribution = null,
    ): ?CampaignConversion {
        $goal = CampaignConversionGoal::query()
            ->where('type', ConversionGoalType::FormSubmission)
            ->where('target', $formTarget)
            ->where('is_active', true)
            ->first();

        if (! $goal instanceof CampaignConversionGoal) {
            return null;
        }

        return RecordCampaignConversionAction::run($goal, $visit, $event, $landingPage, $source, $attribution);
    }
}
