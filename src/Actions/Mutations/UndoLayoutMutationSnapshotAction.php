<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutMutationHistoryData;
use Capell\LayoutBuilder\Data\LayoutMutationNavigationData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class UndoLayoutMutationSnapshotAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<int, array<string, mixed>>  $undoSnapshots
     * @param  array<int, array<string, mixed>>  $redoSnapshots
     */
    public function handle(LayoutBuilderStateData $currentState, array $undoSnapshots, array $redoSnapshots): LayoutMutationNavigationData
    {
        if ($undoSnapshots === []) {
            return new LayoutMutationNavigationData(
                state: null,
                history: new LayoutMutationHistoryData($undoSnapshots, $redoSnapshots),
            );
        }

        $snapshot = array_pop($undoSnapshots);

        return new LayoutMutationNavigationData(
            state: LayoutBuilderStateData::fromSnapshot($snapshot),
            history: new LayoutMutationHistoryData(
                undoSnapshots: $undoSnapshots,
                redoSnapshots: [
                    ...$redoSnapshots,
                    $currentState->toLivewirePayload(),
                ],
            ),
        );
    }
}
