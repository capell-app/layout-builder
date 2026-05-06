<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Actions;

use Capell\CampaignStudio\Enums\ConversionGoalType;
use Capell\CampaignStudio\Models\CampaignConversion;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class RecordCtaClickConversionAction
{
    use AsAction;

    public function handle(string $goalKey, ?Model $visit = null, ?Model $event = null): ?CampaignConversion
    {
        $goal = CampaignConversionGoal::query()
            ->where('key', $goalKey)
            ->where('type', ConversionGoalType::CtaClick)
            ->where('is_active', true)
            ->first();

        if (! $goal instanceof CampaignConversionGoal) {
            return null;
        }

        return RecordCampaignConversionAction::run($goal, $visit, $event);
    }
}
