<?php

declare(strict_types=1);

namespace Capell\Assistant\Integrations\Mosaic;

use Capell\Assistant\Data\AssistantRunData;
use Capell\Mosaic\Actions\PreviewLayoutPlanAction;
use Capell\Mosaic\Data\LayoutPlanResultData;
use Lorisleiva\Actions\Concerns\AsObject;

class PreviewMosaicLayoutPlanAction
{
    use AsObject;

    public function handle(AssistantRunData $run): LayoutPlanResultData
    {
        return PreviewLayoutPlanAction::run($run->prompt, $run->context);
    }
}
