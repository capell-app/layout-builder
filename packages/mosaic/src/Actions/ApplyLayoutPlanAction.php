<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Capell\Mosaic\Data\LayoutPlanData;
use Capell\Mosaic\Data\LayoutPlanResultData;
use Lorisleiva\Actions\Concerns\AsObject;

class ApplyLayoutPlanAction
{
    use AsObject;

    public function handle(LayoutPlanData $plan): LayoutPlanResultData
    {
        return new LayoutPlanResultData(plan: $plan);
    }
}
