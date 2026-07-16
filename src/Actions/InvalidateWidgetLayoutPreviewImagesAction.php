<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static int run(array<int, string|null> $widgetKeys)
 */
class InvalidateWidgetLayoutPreviewImagesAction
{
    use AsFake;
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

        Layout::query()->cursor()->each(function (Layout $layout) use ($widgetKeys, &$invalidated): void {
            if ($widgetKeys->intersect($layout->widgets)->isEmpty()) {
                return;
            }

            if (InvalidateLayoutPreviewImageAction::run($layout, force: true)) {
                $invalidated++;
            }
        });

        return $invalidated;
    }
}
