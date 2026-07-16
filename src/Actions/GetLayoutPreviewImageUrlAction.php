<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static string|null run(Layout $layout)
 */
class GetLayoutPreviewImageUrlAction
{
    use AsFake;
    use AsObject;

    public function handle(Layout $layout): ?string
    {
        if ($layout->image !== null) {
            return $layout->image->getUrl();
        }

        $admin = $layout->admin;

        if (is_array($admin) && isset($admin['image']) && is_string($admin['image']) && $admin['image'] !== '') {
            return Storage::disk()->url($admin['image']);
        }

        $generatedPreviewImage = is_array($admin) ? ($admin[LayoutPreviewMetaKey::IMAGE] ?? null) : null;

        if (is_string($generatedPreviewImage) && $generatedPreviewImage !== '') {
            return Storage::disk('public')->url($generatedPreviewImage);
        }

        return null;
    }
}
