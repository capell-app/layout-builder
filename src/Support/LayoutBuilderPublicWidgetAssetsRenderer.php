<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Frontend\Actions\Fragments\ResolvePublicFragmentContentVersionAction;
use Capell\Frontend\Actions\RenderRenderableAction;
use Capell\Frontend\Actions\ResolveDeferredFragmentPlaceholderDataAction;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\Fragments\PublicFragmentReferenceData;
use Capell\Frontend\Support\Fragments\DeferredFragmentPlaceholderData;
use Capell\Frontend\Support\Fragments\PublicFragmentUrlResolverRegistry;
use Capell\Frontend\Support\Render\PublicViewQueryGuard;
use Capell\Frontend\Support\Renderables\RenderableDynamicDataRegistry;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetAssetsAction;
use Capell\LayoutBuilder\Contracts\Assets\PublicLayoutWidgetAssetsRenderer;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LogicException;

final readonly class LayoutBuilderPublicWidgetAssetsRenderer implements PublicLayoutWidgetAssetsRenderer
{
    public function __construct(
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
        $widgetKey = $widget->getKey();
        $widgetKeyString = is_scalar($widgetKey) ? (string) $widgetKey : '';
        $assetGroupKey = implode(':', [$widgetKeyString, $containerKey, (string) $occurrence]);

        if ($widgetAssetsByWidget instanceof Collection && $widgetAssetsByWidget->offsetExists($assetGroupKey)) {
            $assets = $widgetAssetsByWidget[$assetGroupKey];

            if ($assets instanceof Collection) {
                return $assets->filter(fn (mixed $asset): bool => $asset instanceof WidgetAsset)->values();
            }
        }

        if ($this->publicViewQueryGuardIsActive()) {
            return collect();
        }

        $context = $this->frontendContext();
        $page = $context?->page();
        $language = $context?->language();

        if (! $page instanceof Page || ! $language instanceof Language) {
            return collect();
        }

        return ResolvePublicWidgetAssetsAction::run($widget, $page, $language, $containerKey, $occurrence);
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
        $performance = is_array($meta['performance'] ?? null) ? $meta['performance'] : [];
        $owner = is_string($performance['fragment_owner'] ?? null)
            ? trim($performance['fragment_owner'])
            : '';
        $context = $this->frontendContext();
        $page = $context?->page();
        $site = $context?->site();
        $language = $context?->language();
        $layout = $context?->layout();
        $assetId = $asset->getKey();

        if (($performance['defer'] ?? false) !== true
            || $owner === ''
            || ! $page instanceof Page
            || ! $site instanceof Site
            || ! $language instanceof Language
            || ! $layout instanceof Layout
            || (! is_int($assetId) && ! is_string($assetId))) {
            return null;
        }

        $registry = resolve(PublicFragmentUrlResolverRegistry::class);

        if (! $registry->has($owner)) {
            return null;
        }

        $ownerContext = [
            'layoutId' => $this->scalarKey($layout),
            'assetType' => $asset->getMorphClass(),
            'assetId' => $assetId,
            'assetVersion' => $this->assetVersion($asset, $language),
        ];
        $contentVersion = ResolvePublicFragmentContentVersionAction::run(
            $page,
            $site,
            $language,
            $layout,
            $ownerContext,
        );
        $reference = new PublicFragmentReferenceData(
            owner: $owner,
            formatVersion: 1,
            pageableType: $page->getMorphClass(),
            pageableId: $this->scalarKey($page),
            siteId: $this->scalarKey($site),
            languageId: $this->scalarKey($language),
            contentVersion: $contentVersion,
            ownerContext: $ownerContext,
        );
        $url = $registry->url($reference);

        return ResolveDeferredFragmentPlaceholderDataAction::run(
            $meta,
            $this->fragmentCacheIdentity($reference),
            $url,
        );
    }

    private function fragmentCacheIdentity(PublicFragmentReferenceData $reference): string
    {
        $ownerContext = $reference->ownerContext;
        ksort($ownerContext);

        return json_encode([
            'owner' => $reference->owner,
            'formatVersion' => $reference->formatVersion,
            'pageableType' => $reference->pageableType,
            'pageableId' => $reference->pageableId,
            'siteId' => $reference->siteId,
            'languageId' => $reference->languageId,
            'contentVersion' => $reference->contentVersion,
            'ownerContext' => $ownerContext,
        ], JSON_THROW_ON_ERROR);
    }

    private function assetVersion(Model $asset, Language $language): string
    {
        $freshAsset = $asset->newQuery()->whereKey($asset->getKey())->firstOrFail();
        $translation = Translation::query()
            ->where('translatable_type', $freshAsset->getMorphClass())
            ->where('translatable_id', $freshAsset->getKey())
            ->where('language_id', $language->getKey())
            ->first();
        $assetAttributes = $freshAsset->getAttributes();
        $translationAttributes = $translation?->getAttributes();

        ksort($assetAttributes);
        if (is_array($translationAttributes)) {
            ksort($translationAttributes);
        }

        return hash('sha256', json_encode([
            'asset' => $assetAttributes,
            'translation' => $translationAttributes,
        ], JSON_THROW_ON_ERROR));
    }

    private function scalarKey(Model $model): int|string
    {
        $key = $model->getKey();

        if (! is_int($key) && ! is_string($key)) {
            throw new LogicException('Public fragment context records require scalar identifiers.');
        }

        return $key;
    }

    private function frontendContext(): ?FrontendContextReader
    {
        return app()->bound(FrontendContextReader::class) ? app(FrontendContextReader::class) : null;
    }

    private function publicViewQueryGuardIsActive(): bool
    {
        if (! app()->bound(PublicViewQueryGuard::class)) {
            return false;
        }

        return app(PublicViewQueryGuard::class)->isActive();
    }
}
