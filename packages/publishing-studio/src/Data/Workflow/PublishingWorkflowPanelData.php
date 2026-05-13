<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data\Workflow;

use Spatie\LaravelData\Data;

final class PublishingWorkflowPanelData extends Data
{
    /**
     * @param  list<PublishingWorkflowActionData>  $actions
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $description,
        public readonly array $actions,
    ) {}

    public function totalCount(): int
    {
        return array_sum(array_map(
            static fn (PublishingWorkflowActionData $action): int => $action->count,
            $this->actions,
        ));
    }
}
