<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\ContentBlocks\Support\NullBlockDefinition;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Support\LayoutElementData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

final class AnalyzeLayoutHealthAction
{
    use AsObject;

    /**
     * @return array<int, LayoutDiagnosticData>
     */
    public function handle(LayoutBuilderStateData $state, ?string $themeKey = null): array
    {
        $elementModels = $this->elements($state);
        $knownElementKeys = $elementModels->keys()->all();
        $diagnostics = AnalyzeLayoutDiagnosticsAction::run($state, $knownElementKeys);
        $anchors = [];
        $registry = resolve(BlockRegistry::class);

        foreach ($state->containers as $containerKey => $container) {
            $containerElements = LayoutElementData::normalizeMany($container['elements'] ?? []);

            foreach ($containerElements as $elementIndex => $containerElement) {
                $elementKey = $containerElement['element_key'] ?? null;
                $meta = is_array($containerElement['meta'] ?? null) ? $containerElement['meta'] : [];
                $settings = is_array($meta['block_settings'] ?? null) ? $meta['block_settings'] : [];
                $anchorId = $this->anchorId($settings['anchor_id'] ?? null);

                if ($anchorId !== null && isset($anchors[$anchorId])) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Warning,
                        code: 'duplicate_block_anchor',
                        message: __('capell-layout-builder::message.duplicate_block_anchor', ['anchor' => $anchorId]),
                        containerKey: (string) $containerKey,
                        elementIndex: $elementIndex,
                    );
                }

                if ($anchorId !== null) {
                    $anchors[$anchorId] = true;
                }

                if (! is_string($elementKey)) {
                    continue;
                }

                if (! in_array($elementKey, $knownElementKeys, true)) {
                    continue;
                }

                $assets = $state->assets[(string) $containerKey][$elementIndex] ?? [];
                if (is_array($assets) && count($assets) > 6) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Warning,
                        code: 'too_many_block_cards',
                        message: __('capell-layout-builder::message.too_many_block_cards', ['max' => 6]),
                        containerKey: (string) $containerKey,
                        elementIndex: $elementIndex,
                    );
                }

                $element = $elementModels->get($elementKey);
                if (! $element instanceof Element) {
                    continue;
                }

                $publicElement = $this->elementWithPublicOccurrenceMeta($element, $meta);
                $definitionKey = $this->definitionKey($publicElement, $registry);
                $definition = $registry->get($definitionKey) ?? NullBlockDefinition::make($definitionKey);
                $presentation = ResolveBlockPresentationDataAction::run($publicElement, $themeKey);

                $diagnostics = [
                    ...$diagnostics,
                    ...$this->variantDiagnostics($definition, $meta, $themeKey, (string) $containerKey, $elementIndex),
                    ...BlockContractValidatorAction::run(
                        definition: $definition,
                        presentation: $presentation,
                        payload: $this->contentPayload($containerElement, is_array($assets) ? $assets : []),
                        containerKey: (string) $containerKey,
                        elementIndex: $elementIndex,
                    ),
                ];
            }
        }

        return $diagnostics;
    }

    /**
     * @return Collection<string, Element>
     */
    private function elements(LayoutBuilderStateData $state): Collection
    {
        $layoutElementKeys = collect($state->containers)
            ->flatMap(fn (array $container): array => LayoutElementData::normalizeMany($container['elements'] ?? []))
            ->map(static fn (array $element): ?string => LayoutElementData::key($element))
            ->filter(static fn (mixed $elementKey): bool => is_string($elementKey) && $elementKey !== '')
            ->unique()
            ->values()
            ->all();

        return $layoutElementKeys === []
            ? collect()
            : Element::query()->with('type:id,key')->whereIn('key', $layoutElementKeys)->get()->keyBy('key');
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
    private function elementWithPublicOccurrenceMeta(Element $element, array $meta): Element
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
            return $element;
        }

        $publicElement = clone $element;
        $baseMeta = is_array($element->meta) ? $element->meta : [];
        $publicElement->setAttribute('meta', array_replace_recursive($baseMeta, $safeMeta));

        return $publicElement;
    }

    private function definitionKey(Element $element, BlockRegistry $registry): string
    {
        $meta = is_array($element->meta) ? $element->meta : [];
        $configuredKey = $meta['block_key'] ?? null;

        if (is_string($configuredKey) && trim($configuredKey) !== '') {
            return trim($configuredKey);
        }

        $typeKey = $element->type?->key;

        if (is_string($typeKey) && $registry->has($typeKey)) {
            return $typeKey;
        }

        return $element->key;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<int, LayoutDiagnosticData>
     */
    private function variantDiagnostics(BlockDefinitionData $definition, array $meta, ?string $themeKey, string $containerKey, int $elementIndex): array
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
                elementIndex: $elementIndex,
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
     * @param  array<string, mixed>  $containerElement
     * @param  array<int, mixed>  $assets
     * @return array<string, mixed>
     */
    private function contentPayload(array $containerElement, array $assets): array
    {
        $meta = is_array($containerElement['meta'] ?? null) ? $containerElement['meta'] : [];
        $content = is_array($meta['content'] ?? null) ? $meta['content'] : [];

        if (! array_key_exists('items', $content) && $assets !== []) {
            $content['items'] = $assets;
        }

        return $content;
    }
}
