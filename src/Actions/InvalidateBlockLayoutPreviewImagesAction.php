<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static int run(array<int, string|null> $widgetKeys)
 */
class InvalidateBlockLayoutPreviewImagesAction
{
    use AsObject;

    /**
     * @param  array<int, string|null>  $widgetKeys
     */
    public function handle(array $widgetKeys): int
    {
        $widgetKeys = collect($widgetKeys)
            ->filter(fn (?string $widgetKey): bool => is_string($widgetKey) && $widgetKey !== '')
            ->unique()
            ->values();

        if ($widgetKeys->isEmpty()) {
            return 0;
        }

        $invalidated = 0;

        $this->layoutQuery($widgetKeys)->each(function (Layout $layout) use (&$invalidated): void {
            if (InvalidateLayoutPreviewImageAction::run($layout, force: true)) {
                $invalidated++;
            }
        });

        return $invalidated;
    }

    /**
     * @param  Collection<int, non-empty-string>  $widgetKeys
     * @return Builder<Layout>
     */
    private function layoutQuery(Collection $widgetKeys): Builder
    {
        return Layout::query()
            ->where(function (Builder $query) use ($widgetKeys): void {
                foreach ($widgetKeys as $widgetKey) {
                    $query->orWhereJsonContains('widgets', $widgetKey);
                }
            });
    }
}
