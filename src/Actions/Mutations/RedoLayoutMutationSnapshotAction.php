<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Mutations;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutMutationHistoryData;
use Capell\LayoutBuilder\Data\LayoutMutationNavigationData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static LayoutMutationNavigationData run(LayoutBuilderStateData $currentState, array<int, array<string, mixed>> $undoSnapshots, array<int, array<string, mixed>> $redoSnapshots) */
final class RedoLayoutMutationSnapshotAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<int, array<string, mixed>>  $undoSnapshots
     * @param  array<int, array<string, mixed>>  $redoSnapshots
     */
    public function handle(LayoutBuilderStateData $currentState, array $undoSnapshots, array $redoSnapshots): LayoutMutationNavigationData
    {
        if ($redoSnapshots === []) {
            return new LayoutMutationNavigationData(
                state: null,
                history: new LayoutMutationHistoryData($undoSnapshots, $redoSnapshots),
            );
        }

        $snapshot = array_pop($redoSnapshots);

        return new LayoutMutationNavigationData(
            state: LayoutBuilderStateData::fromSnapshot($snapshot),
            history: new LayoutMutationHistoryData(
                undoSnapshots: [
                    ...$undoSnapshots,
                    $currentState->toLivewirePayload(),
                ],
                redoSnapshots: $redoSnapshots,
            ),
        );
    }
}
