<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Models\Block;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static int run(Blueprint $type)
 */
class InvalidateTypeLayoutPreviewImagesAction
{
    use AsObject;

    public function handle(Blueprint $type): int
    {
        $blockKeys = Block::query()
            ->where('blueprint_id', $type->getKey())
            ->pluck('key')
            ->all();

        return InvalidateBlockLayoutPreviewImagesAction::run($blockKeys);
    }
}
