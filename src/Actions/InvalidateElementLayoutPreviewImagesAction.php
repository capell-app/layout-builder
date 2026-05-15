<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static int run(array $elementKeys)
 */
class InvalidateElementLayoutPreviewImagesAction
{
    use AsObject;

    /**
     * @param  array<int, string|null>  $elementKeys
     */
    public function handle(array $elementKeys): int
    {
        $elementKeys = collect($elementKeys)
            ->filter(fn (?string $elementKey): bool => is_string($elementKey) && $elementKey !== '')
            ->unique()
            ->values();

        if ($elementKeys->isEmpty()) {
            return 0;
        }

        $invalidated = 0;

        $this->layoutQuery($elementKeys)->each(function (Layout $layout) use (&$invalidated): void {
            if (InvalidateLayoutPreviewImageAction::run($layout, force: true)) {
                $invalidated++;
            }
        });

        return $invalidated;
    }

    /**
     * @param  Collection<int, string>  $elementKeys
     * @return Builder<Layout>
     */
    private function layoutQuery(Collection $elementKeys): Builder
    {
        return Layout::query()
            ->where(function (Builder $query) use ($elementKeys): void {
                foreach ($elementKeys as $elementKey) {
                    $query->orWhereJsonContains('elements', $elementKey);
                }
            });
    }
}
