<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\AIOrchestrator;

use Capell\AIOrchestrator\Data\AIOrchestratorRunData;
use Capell\LayoutBuilder\Actions\PreviewLayoutPlanAction;
use Capell\LayoutBuilder\Data\LayoutPlanResultData;
use Lorisleiva\Actions\Concerns\AsObject;

class PreviewLayoutBuilderLayoutPlanAction
{
    use AsObject;

    public function handle(AIOrchestratorRunData $run): LayoutPlanResultData
    {
        return PreviewLayoutPlanAction::run($run->prompt, $run->context);
    }
}
