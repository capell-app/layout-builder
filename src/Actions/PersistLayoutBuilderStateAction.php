<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

final class PersistLayoutBuilderStateAction
{
    use AsAction;

    public function handle(Layout $layout, ?Model $page, array $containers, Closure $persistBlockAssets): void
    {
        DB::transaction(function () use ($layout, $page, $containers, $persistBlockAssets): void {
            $layout->update([
                'containers' => $containers,
            ]);

            if ($page instanceof Model && $page->getAttribute('layout_id') !== $layout->getKey()) {
                $page->update([
                    'layout_id' => $layout->getKey(),
                ]);
            }

            $persistBlockAssets();
        });

        InvalidateLayoutPreviewImageAction::run($layout);
    }
}
