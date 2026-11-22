<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Bridges\AiOrchestrator;

use Capell\AIOrchestrator\Data\AIOrchestratorRunData;
use Capell\LayoutBuilder\Actions\PreviewLayoutPlanAction;
use Capell\LayoutBuilder\Data\LayoutPlanResultData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Runs Layout Builder's layout planning for an AI Orchestrator run.
 *
 * @method static LayoutPlanResultData run(AIOrchestratorRunData $run)
 */
class PreviewLayoutBuilderLayoutPlanAction
{
    use AsFake;
    use AsObject;

    public function handle(AIOrchestratorRunData $run): LayoutPlanResultData
    {
        return PreviewLayoutPlanAction::run($run->prompt, $run->context);
    }
}
