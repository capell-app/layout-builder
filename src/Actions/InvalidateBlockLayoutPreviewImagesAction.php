<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static int run(array $blockKeys)
 */
class InvalidateBlockLayoutPreviewImagesAction
{
    use AsObject;

    /**
     * @param  array<int, string|null>  $blockKeys
     */
    public function handle(array $blockKeys): int
    {
        $blockKeys = collect($blockKeys)
            ->filter(fn (?string $blockKey): bool => is_string($blockKey) && $blockKey !== '')
            ->unique()
            ->values();

        if ($blockKeys->isEmpty()) {
            return 0;
        }

        $invalidated = 0;

        $this->layoutQuery($blockKeys)->each(function (Layout $layout) use (&$invalidated): void {
            if (InvalidateLayoutPreviewImageAction::run($layout, force: true)) {
                $invalidated++;
            }
        });

        return $invalidated;
    }

    /**
     * @param  Collection<int, non-empty-string>  $blockKeys
     * @return Builder<Layout>
     */
    private function layoutQuery(Collection $blockKeys): Builder
    {
        return Layout::query()
            ->where(function (Builder $query) use ($blockKeys): void {
                foreach ($blockKeys as $blockKey) {
                    $query->orWhereJsonContains('blocks', $blockKey);
                }
            });
    }
}
