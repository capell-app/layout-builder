<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutMutationHistoryData;
use Lorisleiva\Actions\Concerns\AsAction;

final class PushLayoutMutationSnapshotAction
{
    use AsAction;

    public const int MAX_HISTORY_DEPTH = 20;

    /**
     * @param  array<int, array<string, mixed>>  $undoSnapshots
     */
    public function handle(LayoutBuilderStateData $currentState, array $undoSnapshots): LayoutMutationHistoryData
    {
        $undoSnapshots = array_slice($undoSnapshots, -self::MAX_HISTORY_DEPTH + 1);

        return new LayoutMutationHistoryData(
            undoSnapshots: [
                ...$undoSnapshots,
                $currentState->toLivewirePayload(),
            ],
            redoSnapshots: [],
        );
    }
}
