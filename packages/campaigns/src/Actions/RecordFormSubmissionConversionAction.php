<?php

declare(strict_types=1);

namespace Capell\Campaigns\Actions;

use Capell\Campaigns\Enums\ConversionGoalType;
use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordFormSubmissionConversionAction
{
    use AsAction;

    public function handle(string $formTarget, ?Model $visit = null, ?Model $event = null): ?CampaignConversion
    {
        $goal = CampaignConversionGoal::query()
            ->where('type', ConversionGoalType::FormSubmission)
            ->where('target', $formTarget)
            ->where('is_active', true)
            ->first();

        if (! $goal instanceof CampaignConversionGoal) {
            return null;
        }

        return RecordCampaignConversionAction::run($goal, $visit, $event);
    }
}
