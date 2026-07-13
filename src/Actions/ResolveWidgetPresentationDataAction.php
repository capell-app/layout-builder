<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\BlockLibrary\Data\PublicBlockPresentationData;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\NullBlockDefinition;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveWidgetPresentationDataAction
{
    use AsObject;

    public function handle(Widget $widget, ?string $themeKey = null): PublicBlockPresentationData
    {
        $registry = resolve(BlockRegistry::class);
        $definitionKey = $this->definitionKey($widget, $registry);
        $definition = $registry->get($definitionKey)
            ?? NullBlockDefinition::make($definitionKey);

        $meta = is_array($widget->meta) ? $widget->meta : [];
        $settings = is_array($meta['widget_settings'] ?? null) ? $meta['widget_settings'] : [];
        $variant = is_string($meta['widget_variant'] ?? null) ? $meta['widget_variant'] : $definition->defaultVariant->value();

        if (! $definition->supportsVariant($variant) || ! $definition->compatibility->supportsTheme($themeKey)) {
            $variant = $definition->defaultVariant->value();
            $settings = [];
        }

        return new PublicBlockPresentationData(
            variant: $variant,
            spacing: $this->allowedString($settings['spacing'] ?? null, ['tight', 'normal', 'spacious'], 'normal'),
            background: $this->allowedString($settings['background'] ?? null, ['default', 'muted', 'dark', 'image'], 'default'),
            mediaPosition: $this->allowedString($settings['media_position'] ?? null, ['left', 'right', 'top'], 'top'),
            cardsPerRow: max(1, min(6, (int) ($settings['cards_per_row'] ?? 3))),
            showCta: (bool) ($settings['show_cta'] ?? true),
            headingWidth: $this->allowedString($settings['heading_width'] ?? null, ['narrow', 'normal', 'wide'], 'normal'),
            anchorId: $this->anchorId($settings['anchor_id'] ?? null),
        );
    }

    private function definitionKey(Widget $widget, BlockRegistry $registry): string
    {
        $meta = is_array($widget->meta) ? $widget->meta : [];
        $configuredKey = $meta['widget_key'] ?? null;

        if (is_string($configuredKey) && trim($configuredKey) !== '') {
            return trim($configuredKey);
        }

        $blueprint = $widget->relationLoaded('blueprint') ? $widget->getRelation('blueprint') : null;
        $typeKey = $blueprint instanceof Blueprint ? $blueprint->key : null;
        if (is_string($typeKey) && $registry->has($typeKey)) {
            return $typeKey;
        }

        return $widget->key;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function allowedString(mixed $value, array $allowed, string $fallback): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function anchorId(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $anchorId = Str::slug($value);

        return $anchorId === '' ? null : $anchorId;
    }
}
