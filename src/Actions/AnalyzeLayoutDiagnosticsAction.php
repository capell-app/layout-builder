<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Element;
use Lorisleiva\Actions\Concerns\AsAction;

final class AnalyzeLayoutDiagnosticsAction
{
    use AsAction;

    /**
     * @return array<int, LayoutDiagnosticData>
     */
    public function handle(LayoutBuilderStateData $state): array
    {
        $layoutElementKeys = collect($state->containers)
            ->flatMap(fn (array $container): array => array_map(
                static fn (array $element): mixed => $element['element_key'] ?? null,
                $container['elements'] ?? [],
            ))
            ->filter(static fn (mixed $elementKey): bool => is_string($elementKey) && $elementKey !== '')
            ->unique()
            ->values()
            ->all();

        $knownElementKeys = $layoutElementKeys === []
            ? []
            : Element::query()
                ->whereIn('key', $layoutElementKeys)
                ->pluck('key')
                ->all();

        $diagnostics = [];

        foreach ($state->containers as $containerKey => $container) {
            foreach (($container['elements'] ?? []) as $elementIndex => $element) {
                $elementKey = $element['element_key'] ?? null;

                if (! is_string($elementKey) || ! in_array($elementKey, $knownElementKeys, true)) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Blocking,
                        code: 'unknown_element',
                        message: __('capell-admin::message.unknown_element', ['element' => (string) $elementKey]),
                        containerKey: (string) $containerKey,
                        elementIndex: (int) $elementIndex,
                    );
                }
            }

            foreach (($container['meta']['responsive'] ?? []) as $breakpoint => $settings) {
                if (LayoutBreakpoint::tryFrom((string) $breakpoint) === null) {
                    continue;
                }

                $colspan = (int) ($settings['colspan'] ?? 12);
                if ($colspan < 1 || $colspan > 12) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Warning,
                        code: 'invalid_responsive_colspan',
                        message: __('capell-admin::message.invalid_responsive_colspan', ['container' => (string) $containerKey]),
                        containerKey: (string) $containerKey,
                        elementIndex: null,
                    );
                }
            }
        }

        return $diagnostics;
    }
}
