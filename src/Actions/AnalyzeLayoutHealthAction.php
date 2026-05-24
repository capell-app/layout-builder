<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\ContentBlocks\Support\NullBlockDefinition;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Block;
use Capell\LayoutBuilder\Support\LayoutBlockData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array<int, LayoutDiagnosticData> run(LayoutBuilderStateData $state, ?string $themeKey = null)
 */
final class AnalyzeLayoutHealthAction
{
    use AsObject;

    /**
     * @return array<int, LayoutDiagnosticData>
     */
    public function handle(LayoutBuilderStateData $state, ?string $themeKey = null): array
    {
        $blockModels = $this->blocks($state);
        $knownBlockKeys = $blockModels->keys()->all();
        $diagnostics = AnalyzeLayoutDiagnosticsAction::run($state, $knownBlockKeys);
        $anchors = [];
        $registry = class_exists(BlockRegistry::class) ? resolve(BlockRegistry::class) : null;

        foreach ($state->containers as $containerKey => $container) {
            $containerBlocks = LayoutBlockData::normalizeMany($container['blocks'] ?? []);

            foreach ($containerBlocks as $blockIndex => $containerBlock) {
                $blockKey = $containerBlock['block_key'] ?? null;
                $meta = is_array($containerBlock['meta'] ?? null) ? $containerBlock['meta'] : [];
                $settings = is_array($meta['block_settings'] ?? null) ? $meta['block_settings'] : [];
                $anchorId = $this->anchorId($settings['anchor_id'] ?? null);

                if ($anchorId !== null && isset($anchors[$anchorId])) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Warning,
                        code: 'duplicate_block_anchor',
                        message: __('capell-layout-builder::message.duplicate_block_anchor', ['anchor' => $anchorId]),
                        containerKey: (string) $containerKey,
                        blockIndex: $blockIndex,
                    );
                }

                if ($anchorId !== null) {
                    $anchors[$anchorId] = true;
                }

                if (! is_string($blockKey)) {
                    continue;
                }

                if (! in_array($blockKey, $knownBlockKeys, true)) {
                    continue;
                }

                $assets = $state->assets[(string) $containerKey][$blockIndex] ?? [];
                if (is_array($assets) && count($assets) > 6) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Warning,
                        code: 'too_many_block_cards',
                        message: __('capell-layout-builder::message.too_many_block_cards', ['max' => 6]),
                        containerKey: (string) $containerKey,
                        blockIndex: $blockIndex,
                    );
                }

                if (! $registry instanceof BlockRegistry) {
                    continue;
                }

                $block = $blockModels->get($blockKey);
                if (! $block instanceof Block) {
                    continue;
                }

                $publicBlock = $this->blockWithPublicOccurrenceMeta($block, $meta);
                $definitionKey = $this->definitionKey($publicBlock, $registry);
                $definition = $registry->get($definitionKey) ?? NullBlockDefinition::make($definitionKey);
                $presentation = ResolveBlockPresentationDataAction::run($publicBlock, $themeKey);

                $diagnostics = [
                    ...$diagnostics,
                    ...$this->variantDiagnostics($definition, $meta, $themeKey, (string) $containerKey, $blockIndex),
                    ...BlockContractValidatorAction::run(
                        definition: $definition,
                        presentation: $presentation,
                        payload: $this->contentPayload($containerBlock, is_array($assets) ? $assets : []),
                        containerKey: (string) $containerKey,
                        blockIndex: $blockIndex,
                    ),
                ];
            }
        }

        return $diagnostics;
    }

    /**
     * @return Collection<string, Block>
     */
    private function blocks(LayoutBuilderStateData $state): Collection
    {
        $layoutBlockKeys = collect($state->containers)
            ->flatMap(fn (array $container): array => LayoutBlockData::normalizeMany($container['blocks'] ?? []))
            ->map(static fn (array $block): ?string => LayoutBlockData::key($block))
            ->filter(static fn (mixed $blockKey): bool => is_string($blockKey) && $blockKey !== '')
            ->unique()
            ->values()
            ->all();

        return $layoutBlockKeys === []
            ? collect()
            : Block::query()->with('type:id,key')->whereIn('key', $layoutBlockKeys)->get()->keyBy('key');
    }

    private function anchorId(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $anchorId = Str::slug($value);

        return $anchorId === '' ? null : $anchorId;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function blockWithPublicOccurrenceMeta(Block $block, array $meta): Block
    {
        $safeMeta = array_intersect_key($meta, array_flip([
            'block_key',
            'block_variant',
        ]));
        $settings = is_array($meta['block_settings'] ?? null) ? $meta['block_settings'] : [];
        $safeSettings = array_intersect_key($settings, array_flip([
            'spacing',
            'background',
            'media_position',
            'cards_per_row',
            'show_cta',
            'heading_width',
            'anchor_id',
        ]));

        if ($safeSettings !== []) {
            $safeMeta['block_settings'] = $safeSettings;
        }

        if ($safeMeta === []) {
            return $block;
        }

        $publicBlock = clone $block;
        $baseMeta = is_array($block->meta) ? $block->meta : [];
        $publicBlock->setAttribute('meta', array_replace_recursive($baseMeta, $safeMeta));

        return $publicBlock;
    }

    private function definitionKey(Block $block, BlockRegistry $registry): string
    {
        $meta = is_array($block->meta) ? $block->meta : [];
        $configuredKey = $meta['block_key'] ?? null;

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
     * @param  array<string, mixed>  $meta
     * @return array<int, LayoutDiagnosticData>
     */
    private function variantDiagnostics(BlockDefinitionData $definition, array $meta, ?string $themeKey, string $containerKey, int $blockIndex): array
    {
        $configuredVariant = $meta['block_variant'] ?? null;
        $variant = is_string($configuredVariant) && $configuredVariant !== ''
            ? $configuredVariant
            : $definition->defaultVariant->value();

        if ($definition->supportsVariant($variant) && $definition->compatibility->supportsTheme($themeKey)) {
            return [];
        }

        return [
            new LayoutDiagnosticData(
                severity: LayoutDiagnosticSeverity::Warning,
                code: 'unsupported_block_variant',
                message: __('capell-layout-builder::message.unsupported_block_variant', [
                    'variant' => $this->variantLabel($definition, $variant),
                ]),
                containerKey: $containerKey,
                blockIndex: $blockIndex,
            ),
        ];
    }

    private function variantLabel(BlockDefinitionData $definition, string $variantKey): string
    {
        foreach ($definition->variants as $variant) {
            if ($variant->key->value() === $variantKey) {
                $label = __($variant->labelKey);

                return $label === $variant->labelKey
                    ? Str::headline($variant->key->value())
                    : $label;
            }
        }

        return Str::headline($variantKey);
    }

    /**
     * @param  array<string, mixed>  $containerBlock
     * @param  array<int, mixed>  $assets
     * @return array<string, mixed>
     */
    private function contentPayload(array $containerBlock, array $assets): array
    {
        $meta = is_array($containerBlock['meta'] ?? null) ? $containerBlock['meta'] : [];
        $content = is_array($meta['content'] ?? null) ? $meta['content'] : [];

        if (! array_key_exists('items', $content) && $assets !== []) {
            $content['items'] = $assets;
        }

        return $content;
    }
}
