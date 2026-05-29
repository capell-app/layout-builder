<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\PublicBlockPresentationData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array<int, LayoutDiagnosticData> run(BlockDefinitionData $definition, PublicBlockPresentationData $presentation, array<string, mixed> $payload, ?string $containerKey = null, ?int $blockIndex = null)
 */
final class BlockContractValidatorAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, LayoutDiagnosticData>
     */
    public function handle(
        BlockDefinitionData $definition,
        PublicBlockPresentationData $presentation,
        array $payload,
        ?string $containerKey = null,
        ?int $blockIndex = null,
    ): array {
        $diagnostics = [];

        foreach ($definition->contentContract->requiredFields as $requiredField) {
            $value = $payload[$requiredField] ?? null;

            if (in_array($value, [null, '', []], true)) {
                $diagnostics[] = new LayoutDiagnosticData(
                    severity: LayoutDiagnosticSeverity::Blocking,
                    code: 'missing_required_block_field',
                    message: __('capell-layout-builder::message.missing_required_block_field', ['field' => Str::headline($requiredField)]),
                    containerKey: $containerKey,
                    blockIndex: $blockIndex,
                );
            }
        }

        $items = $payload['items'] ?? null;
        if (is_array($items) && $definition->contentContract->maxItems !== null && count($items) > $definition->contentContract->maxItems) {
            $diagnostics[] = new LayoutDiagnosticData(
                severity: LayoutDiagnosticSeverity::Warning,
                code: 'too_many_block_items',
                message: __('capell-layout-builder::message.too_many_block_items', ['max' => $definition->contentContract->maxItems]),
                containerKey: $containerKey,
                blockIndex: $blockIndex,
            );
        }

        $cta = $payload['cta'] ?? null;
        $ctaIsMissing = $cta === null || $cta === '' || (is_array($cta) && $cta === []);

        if ($definition->contentContract->requiresCta && $presentation->showCta && $ctaIsMissing) {
            $diagnostics[] = new LayoutDiagnosticData(
                severity: LayoutDiagnosticSeverity::Warning,
                code: 'empty_block_cta',
                message: __('capell-layout-builder::message.empty_block_cta'),
                containerKey: $containerKey,
                blockIndex: $blockIndex,
            );
        }

        if ($this->requiresCtaAccessibleName($definition) && $presentation->showCta && ! $ctaIsMissing && ! $this->hasAccessibleName($cta)) {
            $diagnostics[] = new LayoutDiagnosticData(
                severity: LayoutDiagnosticSeverity::Warning,
                code: 'missing_block_cta_label',
                message: __('capell-layout-builder::message.missing_block_cta_label'),
                containerKey: $containerKey,
                blockIndex: $blockIndex,
            );
        }

        if ($this->requiresImageAlt($definition) && $this->mediaMissingAlt($payload)) {
            $diagnostics[] = new LayoutDiagnosticData(
                severity: LayoutDiagnosticSeverity::Warning,
                code: 'missing_block_image_alt',
                message: __('capell-layout-builder::message.missing_block_image_alt'),
                containerKey: $containerKey,
                blockIndex: $blockIndex,
            );
        }

        if ($definition->accessibilityContract->contrastPairs !== [] && ! $this->hasContrastProof($payload)) {
            $diagnostics[] = new LayoutDiagnosticData(
                severity: LayoutDiagnosticSeverity::Warning,
                code: 'unverified_block_contrast_pairs',
                message: __('capell-layout-builder::message.unverified_block_contrast_pairs'),
                containerKey: $containerKey,
                blockIndex: $blockIndex,
            );
        }

        return $diagnostics;
    }

    private function requiresCtaAccessibleName(BlockDefinitionData $definition): bool
    {
        return $this->hasRule($definition, [
            'requires_cta_accessible_name',
            'cta_accessible_name',
            'cta_label_required',
        ]);
    }

    private function requiresImageAlt(BlockDefinitionData $definition): bool
    {
        return $this->hasRule($definition, [
            'requires_image_alt',
            'requires_alt_text',
            'image_alt_required',
        ]);
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function hasRule(BlockDefinitionData $definition, array $needles): bool
    {
        $rules = [
            ...$definition->contentContract->accessibilityRules,
            ...$definition->accessibilityContract->mediaRules,
            ...$definition->accessibilityContract->semanticRules,
            ...$definition->accessibilityContract->keyboardRules,
        ];

        return collect($rules)
            ->map(static fn (string $rule): string => strtolower($rule))
            ->intersect($needles)
            ->isNotEmpty();
    }

    private function hasAccessibleName(mixed $cta): bool
    {
        if (is_string($cta)) {
            return trim($cta) !== '';
        }

        if (! is_array($cta)) {
            return false;
        }

        foreach (['label', 'text', 'title', 'aria_label', 'aria-label'] as $key) {
            $value = $cta[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function mediaMissingAlt(array $payload): bool
    {
        foreach (['image', 'media'] as $key) {
            $media = $payload[$key] ?? null;

            if (is_array($media) && ! $this->mediaHasAltOrDecorativeIntent($media)) {
                return true;
            }
        }

        $items = $payload['items'] ?? null;
        if (! is_array($items)) {
            return false;
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($this->assetItemMissingAlt($item)) {
                return true;
            }

            foreach (['image', 'media'] as $key) {
                $media = $item[$key] ?? null;

                if (is_array($media) && ! $this->mediaHasAltOrDecorativeIntent($media)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $media
     */
    private function mediaHasAltOrDecorativeIntent(array $media): bool
    {
        $alt = $media['alt'] ?? $media['alt_text'] ?? $media['image_alt'] ?? null;

        if (is_string($alt) && trim($alt) !== '') {
            return true;
        }

        return ($media['decorative'] ?? false) === true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function assetItemMissingAlt(array $item): bool
    {
        if (! array_key_exists('asset_id', $item) && ! array_key_exists('asset_type', $item)) {
            return false;
        }

        $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];

        return ! $this->mediaHasAltOrDecorativeIntent([
            ...$meta,
            'alt' => $item['alt'] ?? $meta['alt'] ?? null,
            'alt_text' => $item['alt_text'] ?? $meta['alt_text'] ?? null,
            'image_alt' => $item['image_alt'] ?? $meta['image_alt'] ?? null,
            'decorative' => $item['decorative'] ?? $meta['decorative'] ?? false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasContrastProof(array $payload): bool
    {
        $contrast = $payload['contrast'] ?? null;

        if (! is_array($contrast)) {
            return false;
        }

        return ($contrast['validated'] ?? false) === true;
    }
}
