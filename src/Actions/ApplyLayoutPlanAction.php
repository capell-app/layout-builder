<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutPlanData;
use Capell\LayoutBuilder\Data\LayoutPlanResultData;
use Lorisleiva\Actions\Concerns\AsObject;

class ApplyLayoutPlanAction
{
    use AsObject;

    public function handle(LayoutPlanData $plan): LayoutPlanResultData
    {
        return new LayoutPlanResultData(plan: $plan);
    }
}
