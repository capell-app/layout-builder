<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Support\Assets;

use Capell\Frontend\Contracts\FrontendAssetContributor;
use Capell\Frontend\Data\FrontendAssetContextData;
use Capell\Frontend\Data\FrontendAssetRequirementData;

final class FoundationThemeAssetContributor implements FrontendAssetContributor
{
    public function requirements(FrontendAssetContextData $context): array
    {
        $requirements = [
            new FrontendAssetRequirementData(
                handle: 'foundation-theme:css',
                kind: FrontendAssetRequirementData::KIND_CSS,
                source: $this->frontendCssPath(),
                buildPath: 'build',
            ),
        ];

        if ($this->shouldLoadRuntimeJavaScript($context)) {
            $requirements[] = new FrontendAssetRequirementData(
                handle: 'foundation-theme:runtime',
                kind: FrontendAssetRequirementData::KIND_JS,
                source: 'resources/js/capell-frontend.js',
                buildPath: 'vendor/capell-frontend',
                defer: true,
            );
        }

        if ($this->shouldLoadLayoutBuilderJavaScript($context)) {
            $requirements[] = new FrontendAssetRequirementData(
                handle: 'foundation-theme:layout-builder',
                kind: FrontendAssetRequirementData::KIND_JS,
                source: 'resources/js/layout-builder/capell-layout-builder.js',
                buildPath: 'vendor/capell-foundation-theme/layout-builder',
                defer: true,
            );
        }

        return $requirements;
    }

    private function frontendCssPath(): string
    {
        $path = config('capell-foundation-theme.tailwind.output_css', 'resources/css/capell/frontend.css');

        return is_string($path) && $path !== '' ? $path : 'resources/css/capell/frontend.css';
    }

    private function shouldLoadRuntimeJavaScript(FrontendAssetContextData $context): bool
    {
        return $context->runtime->usesAlpine
            || $context->runtime->usesBeacon
            || $context->runtime->usesIslands
            || $context->runtime->usesLivewire;
    }

    private function shouldLoadLayoutBuilderJavaScript(FrontendAssetContextData $context): bool
    {
        if (! $this->shouldLoadRuntimeJavaScript($context)) {
            return false;
        }

        $containers = $context->layout?->containers;

        if (! is_array($containers)) {
            return false;
        }

        foreach ($containers as $container) {
            if (! is_array($container)) {
                continue;
            }

            $widgets = $container['widgets'] ?? [];

            if (is_array($widgets) && $widgets !== []) {
                return true;
            }
        }

        return false;
    }
}
