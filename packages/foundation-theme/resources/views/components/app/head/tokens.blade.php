<?php

use Capell\Core\Actions\ColorConverterAction;
use Capell\Core\Enums\DefaultColorEnum;
use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
$site = Frontend::site();

$brandColorMeta = $site->getMeta('brand_color');
$linkColorMeta = $theme->getMeta('link_color');
$linkColorActiveMeta = $theme->getMeta('link_color_active');
$dividerColorMeta = $theme->getMeta('divider_color');

$resolveColorToken = static fn (mixed $value, string $fallback): string => is_string($value) && $value !== '' ? $value : $fallback;

$brandColor = ColorConverterAction::run($resolveColorToken($brandColorMeta, '#111827'));
$linkColor = ColorConverterAction::run($resolveColorToken($linkColorMeta, '#1d4ed8'));
$linkColorActive = ColorConverterAction::run($resolveColorToken(
    $linkColorActiveMeta,
    $resolveColorToken($linkColorMeta, '#1e40af'),
));
$dividerColor = ColorConverterAction::run($resolveColorToken($dividerColorMeta, '#e5e7eb'));

$isSafeToken = static fn (string $name, string $value): bool => preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $name) === 1
    && preg_match('/[\x00-\x1F\x7F;{}<>]/', $value) !== 1;

$paletteColors = collect(DefaultColorEnum::getKeyValues())
    ->merge($theme->colors)
    ->map(function (mixed $value, string $name) use ($isSafeToken): ?array {
        if (! is_string($value) || ! $isSafeToken($name, $value)) {
            return null;
        }

        try {
            $convertedValue = ColorConverterAction::run($value);
        } catch (Throwable) {
            return null;
        }

        if (! is_string($convertedValue) || ! $isSafeToken($name, $convertedValue)) {
            return null;
        }

        return ['name' => $name, 'value' => $convertedValue];
    })
    ->filter()
    ->values();

?>

<style>
    :root {
        @foreach ($paletteColors as $paletteColor)
        --color-{{ $paletteColor['name'] }}: {{ $paletteColor['value'] }};
        @endforeach
        --color-brand: {{ $brandColor }};
        --color-link: {{ $linkColor }};
        --color-link-active: {{ $linkColorActive }};
        --color-divider: {{ $dividerColor }};
    }
</style>
