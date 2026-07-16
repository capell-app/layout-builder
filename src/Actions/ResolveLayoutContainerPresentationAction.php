<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Contracts\LayoutContainerThemePresentationProjector;
use Capell\LayoutBuilder\Data\LayoutContainerPresentationData;
use Capell\LayoutBuilder\Data\LayoutContainerResponsivePaddingData;
use Capell\LayoutBuilder\Data\LayoutContainerThemePresentationData;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

final class ResolveLayoutContainerPresentationAction
{
    use AsFake;
    use AsObject;

    private const array SPACING_VALUES = ['none', 'sm', 'md', 'lg'];

    private const array BORDER_VALUES = ['none', 'subtle', 'strong', 'top', 'bottom', 'vertical'];

    /**
     * @param  array<string, mixed>  $container
     */
    public function handle(array $container, ?string $themeKey = null, ?string $containerKey = null): LayoutContainerPresentationData
    {
        $meta = is_array($container['meta'] ?? null) ? $container['meta'] : [];

        return new LayoutContainerPresentationData(
            spacing: $this->allowedString($meta['spacing'] ?? null, self::SPACING_VALUES),
            padding: new LayoutContainerResponsivePaddingData(
                base: $this->padding($meta['padding'] ?? null) ?? [],
                tablet: $this->padding($meta['padding_tablet'] ?? null),
                desktop: $this->padding($meta['padding_desktop'] ?? null),
            ),
            border: $this->allowedString($meta['border'] ?? null, self::BORDER_VALUES),
            margin: $this->padding($meta['margin'] ?? null) ?? [],
            theme: $this->themePresentation($meta, $themeKey, $containerKey),
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function themePresentation(array $meta, ?string $themeKey, ?string $containerKey): ?LayoutContainerThemePresentationData
    {
        if (! is_string($themeKey) || trim($themeKey) === '') {
            return null;
        }

        $themeKey = trim($themeKey);
        $themeSettings = is_array($meta['theme_settings'] ?? null) ? $meta['theme_settings'] : [];
        $state = is_array($themeSettings[$themeKey] ?? null) ? $themeSettings[$themeKey] : [];

        foreach (app()->tagged(LayoutContainerThemePresentationProjector::TAG) as $projector) {
            if (! $projector instanceof LayoutContainerThemePresentationProjector || $projector->themeKey() !== $themeKey) {
                continue;
            }

            try {
                return $projector->project($state);
            } catch (Throwable $throwable) {
                $this->reportProjectionFailure($themeKey, $containerKey, $throwable);

                return null;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function allowedString(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    /**
     * @return list<string>|null
     */
    private function padding(mixed $value): ?array
    {
        return NormalizeLayoutContainerPaddingAction::run($value);
    }

    private function reportProjectionFailure(string $themeKey, ?string $containerKey, Throwable $throwable): void
    {
        try {
            Log::warning('Layout container theme presentation projection failed.', [
                'theme_key' => $themeKey,
                'container_key' => $containerKey,
                'failure_type' => $throwable::class,
            ]);
        } catch (Throwable) {
            // Public rendering remains isolated from diagnostics.
        }
    }
}
