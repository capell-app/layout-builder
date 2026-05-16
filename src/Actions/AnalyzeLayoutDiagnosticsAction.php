<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Support\LayoutElementData;
use Lorisleiva\Actions\Concerns\AsAction;

final class AnalyzeLayoutDiagnosticsAction
{
    use AsAction;

    /**
     * @return array<int, LayoutDiagnosticData>
     */
    /**
     * @param  array<int, string>|null  $knownElementKeys
     */
    public function handle(LayoutBuilderStateData $state, ?array $knownElementKeys = null): array
    {
        $knownElementKeys ??= $this->knownElementKeys($state);

        $diagnostics = [];

        foreach ($state->containers as $containerKey => $container) {
            foreach (LayoutElementData::normalizeMany($container['elements'] ?? []) as $elementIndex => $element) {
                $elementKey = LayoutElementData::key($element);

                if (! is_string($elementKey) || ! in_array($elementKey, $knownElementKeys, true)) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Blocking,
                        code: 'unknown_element',
                        message: __('capell-admin::message.unknown_element', ['element' => (string) $elementKey]),
                        containerKey: (string) $containerKey,
                        elementIndex: $elementIndex,
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

    /**
     * @return array<int, string>
     */
    private function knownElementKeys(LayoutBuilderStateData $state): array
    {
        $layoutElementKeys = collect($state->containers)
            ->flatMap(fn (array $container): array => LayoutElementData::normalizeMany($container['elements'] ?? []))
            ->map(static fn (array $element): ?string => LayoutElementData::key($element))
            ->filter(static fn (mixed $elementKey): bool => is_string($elementKey) && $elementKey !== '')
            ->unique()
            ->values()
            ->all();

        return $layoutElementKeys === []
            ? []
            : Element::query()
                ->whereIn('key', $layoutElementKeys)
                ->pluck('key')
                ->all();
    }
}
