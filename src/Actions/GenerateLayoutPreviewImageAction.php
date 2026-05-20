<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Enums\LayoutPreviewStatusEnum;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static void run(int $layoutId, string $signature)
 * @method static void dispatch(int $layoutId, string $signature)
 */
class GenerateLayoutPreviewImageAction
{
    use AsFake;
    use AsJob;
    use AsObject;

    public function handle(int $layoutId, string $signature): void
    {
        $layout = Layout::query()->find($layoutId);

        if (! $layout instanceof Layout) {
            return;
        }

        $admin = $this->admin($layout);

        if (($admin[LayoutPreviewMetaKey::SIGNATURE] ?? null) !== $signature) {
            return;
        }

        try {
            $png = resolve(LayoutPreviewRenderer::class)->render($layout);
            $path = $this->path($layout, $signature);

            Storage::disk('public')->put($path, $png);

            $admin[LayoutPreviewMetaKey::IMAGE] = $path;
            $admin[LayoutPreviewMetaKey::STATUS] = LayoutPreviewStatusEnum::Ready->value;
            $admin[LayoutPreviewMetaKey::ERROR] = null;

            $layout->forceFill(['admin' => $admin])->saveQuietly();
        } catch (Throwable $throwable) {
            $admin[LayoutPreviewMetaKey::IMAGE] = null;
            $admin[LayoutPreviewMetaKey::STATUS] = LayoutPreviewStatusEnum::Failed->value;
            $admin[LayoutPreviewMetaKey::ERROR] = str($throwable->getMessage())->limit(180)->toString();

            $layout->forceFill(['admin' => $admin])->saveQuietly();

            Log::warning('Failed to generate layout preview image.', [
                'layout_id' => $layoutId,
                'exception' => $throwable::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    private function path(Layout $layout, string $signature): string
    {
        return sprintf(
            'generated-layout-previews/layout-%s-%s.png',
            $layout->getKey(),
            substr($signature, 0, 16),
        );
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
