<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use BackedEnum;
use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory as IconFactory;
use Capell\Core\Data\PackageData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Data\WidgetPickerPreviewData;
use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static WidgetPickerPreviewData run(Widget $widget) */
final class ResolveWidgetPickerPreviewAction
{
    use AsFake;
    use AsObject;

    private const string FALLBACK_ICON = 'heroicon-o-squares-2x2';

    public function handle(Widget $widget): WidgetPickerPreviewData
    {
        // The picker query hydrates these relations. Never lazy-load from a card.
        $previewWidget = clone $widget;
        if (! $previewWidget->relationLoaded('image')) {
            $previewWidget->setRelation('image', null);
        }

        $name = $widget->getAttribute('name');
        $name = is_string($name) ? trim($name) : '';

        $imageUrl = GetLayoutPreviewImageUrlAction::run($previewWidget);
        $blueprint = $widget->relationLoaded('blueprint') ? $widget->getRelation('blueprint') : null;
        $icon = $widget->admin['icon'] ?? ($blueprint instanceof Blueprint ? ($blueprint->admin['icon'] ?? null) : null);
        $identifiers = array_filter([$widget->key, $widget->getMetaComponent(), $widget->getViewFile()]);

        foreach (CapellCore::getInstalledPackages() as $package) {
            if (! CapellCore::isPackageEnabled($package->name)) {
                continue;
            }

            $path = $this->declaredPath($package, $identifiers);

            if ($path === null) {
                continue;
            }

            $icon ??= $package->icon;
            $imageUrl ??= $this->imageUrl($package, $path);
        }

        return new WidgetPickerPreviewData(
            name: $name !== '' ? $name : __('capell-layout-builder::generic.widget'),
            imageUrl: $imageUrl,
            icon: $this->icon($icon),
            disabled: $widget->status === false,
        );
    }

    /**
     * Packages declare picker previews in resources/previews/previews.json, written by
     * scripts/build-widget-previews.mjs, so no runtime contribution receipt is involved.
     *
     * @param  array<int|string, mixed>  $identifiers
     */
    private function declaredPath(PackageData $package, array $identifiers): ?string
    {
        if (! is_string($package->path)) {
            return null;
        }

        $index = $package->path . '/resources/previews/previews.json';

        if (! is_file($index) || ! is_readable($index) || filesize($index) > 64 * 1024) {
            return null;
        }

        $previews = $this->previews($index);

        foreach ($previews as $preview) {
            if (is_array($preview) && in_array($preview['widget'] ?? null, $identifiers, true) && is_string($preview['path'] ?? null)) {
                return $preview['path'];
            }
        }

        return null;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function previews(string $index): array
    {
        // The picker renders many cards per request; read each package index once per file version.
        static $memo = [];
        $version = $index . '@' . filemtime($index);

        return $memo[$version] ??= (function () use ($index): array {
            $decoded = json_decode((string) file_get_contents($index), true);

            return is_array($decoded) && is_array($decoded['previews'] ?? null) ? $decoded['previews'] : [];
        })();
    }

    private function imageUrl(PackageData $package, string $path): ?string
    {
        if (! is_string($package->path) || ! str_starts_with($path, 'resources/previews/')) {
            return null;
        }

        $packageRoot = realpath($package->path);
        $root = realpath($package->path . '/resources/previews');
        $file = realpath($package->path . '/' . $path);
        if ($packageRoot === false || $root !== $packageRoot . '/resources/previews'
            || $file === false || ! str_starts_with($file, $root . DIRECTORY_SEPARATOR)
            || ! is_file($file) || ! is_readable($file) || filesize($file) > 512 * 1024) {
            return null;
        }

        $image = @getimagesize($file);
        $mime = is_array($image) ? $image['mime'] : null;
        if (! in_array($mime, ['image/webp', 'image/png', 'image/jpeg'], true)) {
            return null;
        }

        $bytes = file_get_contents($file);

        return is_string($bytes) ? 'data:' . $mime . ';base64,' . base64_encode($bytes) : null;
    }

    private function icon(mixed $icon): string
    {
        $icon = $icon instanceof BackedEnum ? $icon->value : $icon;
        if (is_string($icon) && $icon !== '') {
            try {
                resolve(IconFactory::class)->svg($icon);

                return $icon;
            } catch (SvgNotFound) {
                // A removed optional icon must not break the widget picker.
            }
        }

        return self::FALLBACK_ICON;
    }
}
