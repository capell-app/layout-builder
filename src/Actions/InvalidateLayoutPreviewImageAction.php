<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutPreviewStatusEnum;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewSignature;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static bool run(Layout $layout, bool $force = false)
 */
class InvalidateLayoutPreviewImageAction
{
    use AsObject;

    public function handle(Layout $layout, bool $force = false): bool
    {
        $layout->refresh();

        $signature = resolve(LayoutPreviewSignature::class)->forLayout($layout);
        $admin = $this->admin($layout);

        if (! $force && ($admin[LayoutPreviewMetaKey::SIGNATURE] ?? null) === $signature) {
            return false;
        }

        $this->deleteGeneratedImage($admin);

        $admin[LayoutPreviewMetaKey::IMAGE] = null;
        $admin[LayoutPreviewMetaKey::SIGNATURE] = $signature;
        $admin[LayoutPreviewMetaKey::STATUS] = LayoutPreviewStatusEnum::Pending->value;
        $admin[LayoutPreviewMetaKey::ERROR] = null;

        $layout->forceFill(['admin' => $admin])->saveQuietly();

        GenerateLayoutPreviewImageAction::dispatch((int) $layout->getKey(), $signature);

        return true;
    }

    /**
     * @param  array<string, mixed>  $admin
     */
    private function deleteGeneratedImage(array $admin): void
    {
        $path = $admin[LayoutPreviewMetaKey::IMAGE] ?? null;

        if (! is_string($path) || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function admin(Layout $layout): array
    {
        $admin = $layout->getAttribute('admin');

        return is_array($admin) ? $admin : [];
    }
}
