<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\NullBlockDefinition;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
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
        $widgetModels = $this->widgets($state);
        $knownWidgetKeys = $widgetModels->keys()->all();
        $diagnostics = AnalyzeLayoutDiagnosticsAction::run($state, $knownWidgetKeys);
        $anchors = [];
        $registry = class_exists(BlockRegistry::class) ? resolve(BlockRegistry::class) : null;

        foreach ($state->containers as $containerKey => $container) {
            $containerWidgets = LayoutWidgetData::fromContainer($container);

            foreach ($containerWidgets as $widgetIndex => $containerWidget) {
                $widgetKey = $containerWidget['widget_key'] ?? null;
                $meta = is_array($containerWidget['meta'] ?? null) ? $containerWidget['meta'] : [];
                $settings = is_array($meta['widget_settings'] ?? null) ? $meta['widget_settings'] : [];
                $anchorId = $this->anchorId($settings['anchor_id'] ?? null);

                if ($anchorId !== null && isset($anchors[$anchorId])) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Warning,
                        code: 'duplicate_widget_anchor',
                        message: __('capell-layout-builder::message.duplicate_widget_anchor', ['anchor' => $anchorId]),
                        containerKey: (string) $containerKey,
                        widgetIndex: $widgetIndex,
                    );
                }

                if ($anchorId !== null) {
                    $anchors[$anchorId] = true;
                }

                if (! is_string($widgetKey)) {
                    continue;
                }

                if (! in_array($widgetKey, $knownWidgetKeys, true)) {
                    continue;
                }

                $assets = $state->assets[(string) $containerKey][$widgetIndex] ?? [];
                if (is_array($assets) && count($assets) > 6) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Warning,
                        code: 'too_many_widget_cards',
                        message: __('capell-layout-builder::message.too_many_widget_cards', ['max' => 6]),
                        containerKey: (string) $containerKey,
                        widgetIndex: $widgetIndex,
                    );
                }

                if (! $registry instanceof BlockRegistry) {
                    continue;
                }

                $widget = $widgetModels->get($widgetKey);
                if (! $widget instanceof Widget) {
                    continue;
                }

                $publicWidget = $this->widgetWithPublicOccurrenceMeta($widget, $meta);
                $definitionKey = $this->definitionKey($publicWidget, $registry);
                $definition = $registry->get($definitionKey) ?? NullBlockDefinition::make($definitionKey);
                $presentation = ResolveWidgetPresentationDataAction::run($publicWidget, $themeKey);

                $diagnostics = [
                    ...$diagnostics,
                    ...$this->variantDiagnostics($definition, $meta, $themeKey, (string) $containerKey, $widgetIndex),
                    ...WidgetContractValidatorAction::run(
                        definition: $definition,
                        presentation: $presentation,
                        payload: $this->contentPayload($containerWidget, is_array($assets) ? $assets : []),
                        containerKey: (string) $containerKey,
                        widgetIndex: $widgetIndex,
                    ),
                ];
            }
        }

        return $diagnostics;
    }

    /**
     * @return Collection<string, Widget>
     */
    private function widgets(LayoutBuilderStateData $state): Collection
    {
        $layoutWidgetKeys = collect($state->containers)
            ->flatMap(fn (array $container): array => LayoutWidgetData::fromContainer($container))
            ->map(static fn (array $widget): ?string => LayoutWidgetData::key($widget))
            ->filter(static fn (mixed $widgetKey): bool => is_string($widgetKey) && $widgetKey !== '')
            ->unique()
            ->values()
            ->all();

        return $layoutWidgetKeys === []
            ? collect()
            : Widget::query()->with('blueprint:id,key')->whereIn('key', $layoutWidgetKeys)->get()->keyBy('key');
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
    private function widgetWithPublicOccurrenceMeta(Widget $widget, array $meta): Widget
    {
        $safeMeta = array_intersect_key($meta, array_flip([
            'widget_key',
            'widget_variant',
        ]));
        $settings = is_array($meta['widget_settings'] ?? null) ? $meta['widget_settings'] : [];
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
            $safeMeta['widget_settings'] = $safeSettings;
        }

        if ($safeMeta === []) {
            return $widget;
        }

        $publicWidget = clone $widget;
        $baseMeta = is_array($widget->meta) ? $widget->meta : [];
        $publicWidget->setAttribute('meta', array_replace_recursive($baseMeta, $safeMeta));

        return $publicWidget;
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
     * @param  array<string, mixed>  $meta
     * @return array<int, LayoutDiagnosticData>
     */
    private function variantDiagnostics(BlockDefinitionData $definition, array $meta, ?string $themeKey, string $containerKey, int $widgetIndex): array
    {
        $configuredVariant = $meta['widget_variant'] ?? null;
        $variant = is_string($configuredVariant) && $configuredVariant !== ''
            ? $configuredVariant
            : $definition->defaultVariant->value();

        if ($definition->supportsVariant($variant) && $definition->compatibility->supportsTheme($themeKey)) {
            return [];
        }

        return [
            new LayoutDiagnosticData(
                severity: LayoutDiagnosticSeverity::Warning,
                code: 'unsupported_widget_variant',
                message: __('capell-layout-builder::message.unsupported_widget_variant', [
                    'variant' => $this->variantLabel($definition, $variant),
                ]),
                containerKey: $containerKey,
                widgetIndex: $widgetIndex,
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
     * @param  array<string, mixed>  $containerWidget
     * @param  array<int, mixed>  $assets
     * @return array<string, mixed>
     */
    private function contentPayload(array $containerWidget, array $assets): array
    {
        $meta = is_array($containerWidget['meta'] ?? null) ? $containerWidget['meta'] : [];
        $content = is_array($meta['content'] ?? null) ? $meta['content'] : [];

        if (! array_key_exists('items', $content) && $assets !== []) {
            $content['items'] = $assets;
        }

        return $content;
    }
}
