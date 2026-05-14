<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\LayoutPreviews;

use Capell\Core\Models\Layout;
use GdImage;
use RuntimeException;

class LayoutPreviewRenderer
{
    private const CANVAS_SIZE = 1200;

    private const PADDING = 56;

    private const GAP = 22;

    private const HEADER_HEIGHT = 54;

    private const WIDGET_HEIGHT = 78;

    private const WIDGET_GAP = 14;

    private const FOOTER_HEIGHT = 58;

    public function render(Layout $layout): string
    {
        throw_unless(function_exists('imagecreatetruecolor'), RuntimeException::class, 'The GD extension is required to generate layout previews.');

        $signature = resolve(LayoutPreviewSignature::class);
        $payload = $signature->payload($layout);

        $image = imagecreatetruecolor(self::CANVAS_SIZE, self::CANVAS_SIZE);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $background = imagecolorallocate($image, 248, 250, 252);
        imagefilledrectangle($image, 0, 0, self::CANVAS_SIZE, self::CANVAS_SIZE, $background);

        $this->drawTitle($image, $layout->name);

        $containers = is_array($payload['containers'] ?? null) ? $payload['containers'] : [];
        $this->drawContainers($image, $layout, $containers);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        throw_if(! is_string($png) || $png === '', RuntimeException::class, 'Unable to encode layout preview PNG.');

        return $png;
    }

    /**
     * @param  resource|GdImage  $image
     */
    private function drawTitle(mixed $image, string $title): void
    {
        $textColor = imagecolorallocate($image, 15, 23, 42);
        imagestring($image, 5, self::PADDING, 26, $this->fitText($title, 68), $textColor);
    }

    /**
     * @param  resource|GdImage  $image
     * @param  array<int, array<string, mixed>>  $containers
     */
    private function drawContainers(mixed $image, Layout $layout, array $containers): void
    {
        $columnWidth = (self::CANVAS_SIZE - (self::PADDING * 2) - (self::GAP * 11)) / 12;
        $cursorX = self::PADDING;
        $cursorY = 76;
        $usedColumns = 0;
        $currentRowHeight = 0;
        $hiddenContainers = 0;
        $usedHues = [];

        foreach ($containers as $container) {
            $colspan = min(12, max(1, (int) ($container['colspan'] ?? 12)));
            $containerHeight = $this->containerHeight($container);

            if ($usedColumns + $colspan > 12) {
                $cursorX = self::PADDING;
                $cursorY += $currentRowHeight + self::GAP;
                $usedColumns = 0;
                $currentRowHeight = 0;
            }

            if ($cursorY + $containerHeight > self::CANVAS_SIZE - self::FOOTER_HEIGHT) {
                $hiddenContainers++;

                continue;
            }

            $width = (int) round(($columnWidth * $colspan) + (self::GAP * ($colspan - 1)));
            $this->drawContainer($image, $layout, $container, (int) round($cursorX), $cursorY, $width, $containerHeight, $usedHues);

            $cursorX += $width + self::GAP;
            $usedColumns += $colspan;
            $currentRowHeight = max($currentRowHeight, $containerHeight);
        }

        if ($hiddenContainers > 0) {
            $this->drawOverflowFooter($image, $hiddenContainers);
        }
    }

    /**
     * @param  array<string, mixed>  $container
     */
    private function containerHeight(array $container): int
    {
        $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];
        $widgetCount = max(1, count($widgets));

        return self::HEADER_HEIGHT + 28 + ($widgetCount * self::WIDGET_HEIGHT) + (($widgetCount - 1) * self::WIDGET_GAP);
    }

    /**
     * @param  resource|GdImage  $image
     * @param  array<string, mixed>  $container
     * @param  array<int, int>  $usedHues
     */
    private function drawContainer(mixed $image, Layout $layout, array $container, int $left, int $top, int $width, int $height, array &$usedHues): void
    {
        $containerColor = $this->color($layout, 'container:' . ($container['key'] ?? 'container'), 0.22, $usedHues);
        $borderColor = imagecolorallocate($image, 203, 213, 225);
        $fillColor = imagecolorallocate($image, $containerColor[0], $containerColor[1], $containerColor[2]);
        imagefilledrectangle($image, $left, $top, $left + $width, $top + $height, $fillColor);
        imagerectangle($image, $left, $top, $left + $width, $top + $height, $borderColor);

        $textColor = $this->textColor($image, $containerColor);
        imagestring($image, 5, $left + 18, $top + 17, $this->fitText((string) ($container['key'] ?? 'container'), max(8, (int) floor($width / 11))), $textColor);

        $widgets = is_array($container['widgets'] ?? null) ? $container['widgets'] : [];
        $widgetTop = $top + self::HEADER_HEIGHT + 12;

        if ($widgets === []) {
            $this->drawEmptyWidget($image, $left + 14, $widgetTop, $width - 28);

            return;
        }

        foreach ($widgets as $widget) {
            if (! is_array($widget)) {
                continue;
            }

            $this->drawWidget($image, $layout, $widget, $left + 14, $widgetTop, $width - 28, $usedHues);
            $widgetTop += self::WIDGET_HEIGHT + self::WIDGET_GAP;
        }
    }

    /**
     * @param  resource|GdImage  $image
     */
    private function drawEmptyWidget(mixed $image, int $left, int $top, int $width): void
    {
        $fillColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 100, 116, 139);
        imagefilledrectangle($image, $left, $top, $left + $width, $top + self::WIDGET_HEIGHT, $fillColor);
        imagestring($image, 4, $left + 18, $top + 30, 'empty', $textColor);
    }

    /**
     * @param  resource|GdImage  $image
     * @param  array<string, mixed>  $widget
     * @param  array<int, int>  $usedHues
     */
    private function drawWidget(mixed $image, Layout $layout, array $widget, int $left, int $top, int $width, array &$usedHues): void
    {
        $widgetKey = (string) ($widget['key'] ?? 'widget');
        $widgetColor = $this->color($layout, 'widget:' . $widgetKey, 0.54, $usedHues);
        $fillColor = imagecolorallocate($image, $widgetColor[0], $widgetColor[1], $widgetColor[2]);
        imagefilledrectangle($image, $left, $top, $left + $width, $top + self::WIDGET_HEIGHT, $fillColor);

        $textColor = $this->textColor($image, $widgetColor);
        $iconLabel = $this->iconLabel($widget);
        imagestring($image, 5, $left + 16, $top + 16, $iconLabel, $textColor);
        imagestring($image, 5, $left + 58, $top + 16, $this->fitText($widgetKey, max(8, (int) floor(($width - 74) / 11))), $textColor);

        $name = (string) ($widget['name'] ?? '');
        if ($name !== '' && $name !== $widgetKey) {
            imagestring($image, 3, $left + 58, $top + 44, $this->fitText($name, max(8, (int) floor(($width - 74) / 8))), $textColor);
        }
    }

    /**
     * @param  resource|GdImage  $image
     */
    private function drawOverflowFooter(mixed $image, int $hiddenContainers): void
    {
        $fillColor = imagecolorallocate($image, 226, 232, 240);
        $textColor = imagecolorallocate($image, 51, 65, 85);
        imagefilledrectangle($image, self::PADDING, self::CANVAS_SIZE - 72, self::CANVAS_SIZE - self::PADDING, self::CANVAS_SIZE - 28, $fillColor);
        imagestring($image, 5, self::PADDING + 18, self::CANVAS_SIZE - 58, '+' . $hiddenContainers . ' more', $textColor);
    }

    /**
     * @param  array<int, int>  $usedHues
     * @return array{0: int, 1: int, 2: int}
     */
    private function color(Layout $layout, string $key, float $lightness, array &$usedHues): array
    {
        $hash = crc32($layout->getKey() . ':' . $layout->key . ':' . $key);
        $hue = $this->spacedHue($hash % 360, $usedHues);
        $saturation = 56 + ($hash % 18);
        $usedHues[] = $hue;

        return $this->hslToRgb($hue / 360, $saturation / 100, $lightness);
    }

    /**
     * @param  array<int, int>  $usedHues
     */
    private function spacedHue(int $hue, array $usedHues): int
    {
        $candidateHue = $hue;

        for ($attempt = 0; $attempt < 12; $attempt++) {
            if ($this->hasEnoughHueDistance($candidateHue, $usedHues)) {
                return $candidateHue;
            }

            $candidateHue = ($candidateHue + 37) % 360;
        }

        return $candidateHue;
    }

    /**
     * @param  array<int, int>  $usedHues
     */
    private function hasEnoughHueDistance(int $candidateHue, array $usedHues): bool
    {
        foreach ($usedHues as $usedHue) {
            $distance = abs($candidateHue - $usedHue);
            $distance = min($distance, 360 - $distance);

            if ($distance < 28) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        if ($saturation === 0.0) {
            $value = (int) round($lightness * 255);

            return [$value, $value, $value];
        }

        $temporarySecond = $lightness < 0.5
            ? $lightness * (1 + $saturation)
            : $lightness + $saturation - ($lightness * $saturation);
        $temporaryFirst = (2 * $lightness) - $temporarySecond;

        return [
            $this->hueToRgb($temporaryFirst, $temporarySecond, $hue + (1 / 3)),
            $this->hueToRgb($temporaryFirst, $temporarySecond, $hue),
            $this->hueToRgb($temporaryFirst, $temporarySecond, $hue - (1 / 3)),
        ];
    }

    private function hueToRgb(float $temporaryFirst, float $temporarySecond, float $hue): int
    {
        if ($hue < 0) {
            $hue++;
        }

        if ($hue > 1) {
            $hue--;
        }

        $value = match (true) {
            6 * $hue < 1 => $temporaryFirst + ($temporarySecond - $temporaryFirst) * 6 * $hue,
            2 * $hue < 1 => $temporarySecond,
            3 * $hue < 2 => $temporaryFirst + ($temporarySecond - $temporaryFirst) * ((2 / 3) - $hue) * 6,
            default => $temporaryFirst,
        };

        return (int) round($value * 255);
    }

    /**
     * @param  resource|GdImage  $image
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function textColor(mixed $image, array $color): int
    {
        $luminance = (($color[0] * 299) + ($color[1] * 587) + ($color[2] * 114)) / 1000;

        return $luminance > 145
            ? imagecolorallocate($image, 15, 23, 42)
            : imagecolorallocate($image, 255, 255, 255);
    }

    /**
     * @param  array<string, mixed>  $widget
     */
    private function iconLabel(array $widget): string
    {
        $icon = (string) ($widget['icon'] ?? $widget['type_icon'] ?? '');

        if ($icon === '') {
            return '[]';
        }

        return strtoupper(substr(str_replace(['heroicon-', 'o-', 'm-', 's-'], '', $icon), 0, 2));
    }

    private function fitText(string $text, int $maximumCharacters): string
    {
        if (mb_strlen($text) <= $maximumCharacters) {
            return $text;
        }

        return mb_substr($text, 0, max(1, $maximumCharacters - 3)) . '...';
    }
}
