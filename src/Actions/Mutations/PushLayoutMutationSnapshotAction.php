<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutMutationHistoryData;
use Lorisleiva\Actions\Concerns\AsAction;

final class PushLayoutMutationSnapshotAction
{
    use AsAction;

    /**
     * @param  array<int, array<string, mixed>>  $undoSnapshots
     */
    public function handle(LayoutBuilderStateData $currentState, array $undoSnapshots): LayoutMutationHistoryData
    {
        return new LayoutMutationHistoryData(
            undoSnapshots: [
                ...$undoSnapshots,
                $currentState->toLivewirePayload(),
            ],
            redoSnapshots: [],
        );
    }
}
