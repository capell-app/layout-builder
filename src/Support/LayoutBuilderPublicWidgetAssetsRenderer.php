<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Frontend\Actions\RenderRenderableAction;
use Capell\Frontend\Actions\ResolveDeferredFragmentPlaceholderDataAction;
use Capell\Frontend\Contracts\DeferredFragmentReferenceBuilder;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Support\Fragments\DeferredFragmentPlaceholderData;
use Capell\Frontend\Support\Renderables\RenderableDynamicDataRegistry;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetAssetsAction;
use Capell\LayoutBuilder\Contracts\Assets\PublicLayoutWidgetAssetsRenderer;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final readonly class LayoutBuilderPublicWidgetAssetsRenderer implements PublicLayoutWidgetAssetsRenderer
{
    public function __construct(
        private ResolvePublicWidgetAssetsAction $assets,
        private RenderableDynamicDataRegistry $dynamicData,
    ) {}

    /**
     * @param  array<string, mixed>  $widgetData
     * @param  array<string, mixed>  $options
     */
    public function render(
        mixed $widget,
        string $containerKey,
        array $widgetData = [],
        mixed $widgetAssets = null,
        mixed $widgetAssetsByWidget = null,
        array $options = [],
    ): string {
        if (! $widget instanceof Widget) {
            return '';
        }

        $rendered = $this->assetsFor($widget, $containerKey, $widgetData, $widgetAssets, $widgetAssetsByWidget)
            ->map(fn (WidgetAsset $widgetAsset): string => $this->renderWidgetAsset($widgetAsset, $options))
            ->filter(fn (string $html): bool => trim($html) !== '')
            ->implode("\n");

        return $rendered === '' ? '' : $rendered;
    }

    /**
     * @param  array<string, mixed>  $widgetData
     * @return Collection<int, WidgetAsset>
     */
    private function assetsFor(
        Widget $widget,
        string $containerKey,
        array $widgetData,
        mixed $widgetAssets,
        mixed $widgetAssetsByWidget,
    ): Collection {
        if ($widgetAssets instanceof Collection) {
            return $widgetAssets->filter(fn (mixed $asset): bool => $asset instanceof WidgetAsset)->values();
        }

        $occurrence = is_numeric($widgetData['occurrence'] ?? null) ? (int) $widgetData['occurrence'] : 1;
        $assetGroupKey = implode(':', [$widget->getKey(), $containerKey, (string) $occurrence]);

        if ($widgetAssetsByWidget instanceof Collection && $widgetAssetsByWidget->offsetExists($assetGroupKey)) {
            $assets = $widgetAssetsByWidget[$assetGroupKey];

            if ($assets instanceof Collection) {
                return $assets->filter(fn (mixed $asset): bool => $asset instanceof WidgetAsset)->values();
            }
        }

        $context = $this->frontendContext();
        $page = $context?->page();
        $language = $context?->language();

        if (! $page instanceof Page || ! $language instanceof Language) {
            return collect();
        }

        return $this->assets->handle($widget, $page, $language, $containerKey, $occurrence);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function renderWidgetAsset(WidgetAsset $widgetAsset, array $options): string
    {
        $asset = $widgetAsset->asset;

        if (! $asset instanceof Model) {
            return '';
        }

        $translation = $asset->getRelationValue('translation');

        if (! $translation instanceof Model) {
            return '';
        }

        $meta = is_array($asset->getAttribute('meta')) ? $asset->getAttribute('meta') : [];
        $deferred = $this->deferredPlaceholder($asset, $meta);

        if ($deferred !== null) {
            return view('capell::components.deferred-fragment-placeholder', ['placeholder' => $deferred])->render();
        }

        $renderableType = is_string($options['renderableType'] ?? null) ? $options['renderableType'] : 'section';
        $renderableKeyMeta = is_string($options['renderableKeyMeta'] ?? null) ? $options['renderableKeyMeta'] : 'kind';
        $defaultRenderableKey = is_string($options['defaultRenderableKey'] ?? null) ? $options['defaultRenderableKey'] : 'section';
        $implementation = is_string($options['implementation'] ?? null) ? $options['implementation'] : 'blade';
        $renderableKey = is_string($meta[$renderableKeyMeta] ?? null) && $meta[$renderableKeyMeta] !== ''
            ? $meta[$renderableKeyMeta]
            : $defaultRenderableKey;
        $dynamicData = $this->dynamicData->data($renderableType, $renderableKey, $asset, $translation, $meta);

        return RenderRenderableAction::run(
            type: $renderableType,
            key: $renderableKey,
            asset: $asset,
            translation: $translation,
            meta: $meta,
            dynamicData: $dynamicData,
            implementation: $implementation,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function deferredPlaceholder(Model $asset, array $meta): ?DeferredFragmentPlaceholderData
    {
        if (! app()->bound(DeferredFragmentReferenceBuilder::class)) {
            return null;
        }

        $builder = app(DeferredFragmentReferenceBuilder::class);
        $reference = $builder->reference($asset, $meta);
        $url = $reference === [] ? '' : $builder->url($reference);

        return ResolveDeferredFragmentPlaceholderDataAction::run($meta, $reference, $url);
    }

    private function frontendContext(): ?FrontendContextReader
    {
        return app()->bound(FrontendContextReader::class) ? app(FrontendContextReader::class) : null;
    }
}
