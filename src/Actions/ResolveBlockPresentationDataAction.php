<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\ContentBlocks\Data\PublicBlockPresentationData;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\ContentBlocks\Support\NullBlockDefinition;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveBlockPresentationDataAction
{
    use AsObject;

    public function handle(Widget $block, ?string $themeKey = null): PublicBlockPresentationData
    {
        $registry = resolve(BlockRegistry::class);
        $definitionKey = $this->definitionKey($block, $registry);
        $definition = $registry->get($definitionKey)
            ?? NullBlockDefinition::make($definitionKey);

        $meta = is_array($block->meta) ? $block->meta : [];
        $settings = is_array($meta['block_settings'] ?? null) ? $meta['block_settings'] : [];
        $variant = is_string($meta['block_variant'] ?? null) ? $meta['block_variant'] : $definition->defaultVariant->value();

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

    private function definitionKey(Widget $block, BlockRegistry $registry): string
    {
        $meta = is_array($block->meta) ? $block->meta : [];
        $configuredKey = $meta['widget_key'] ?? null;

        if (is_string($configuredKey) && trim($configuredKey) !== '') {
            return trim($configuredKey);
        }

        $typeKey = $block->type?->key;
        if (is_string($typeKey) && $registry->has($typeKey)) {
            return $typeKey;
        }

        return $block->key;
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
