<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\BlockLibrary\Actions\BuildBuilderBlockRenderDataAction;
use Capell\BlockLibrary\Actions\ResolveBuilderBlockMediaAction;
use Capell\Core\Contracts\Extensions\RegistersExtensionPublicRenderData;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Frontend\Contracts\PublicRenderDataContributor;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\PublicRenderDataCacheDependencyData;
use Capell\Frontend\Data\PublicRenderDataContributionData;
use Capell\Frontend\Data\PublicRenderDataContributionMetadataData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Override;
use WeakMap;

/** Load media outside the public Blade query guard, including its cache dependencies. */
final class BuilderBlockPublicRenderDataContributor implements PublicRenderDataContributor, RegistersExtensionPublicRenderData
{
    /** @var WeakMap<FrontendRenderContextData, array{value: PublicRenderDataContributionData, metadata: PublicRenderDataContributionMetadataData}> */
    private WeakMap $prepared;

    private readonly BuilderBlockRenderDataStore $renderData;

    public function __construct()
    {
        $this->prepared = new WeakMap;
        $this->renderData = new BuilderBlockRenderDataStore;
    }

    public function renderData(): BuilderBlockRenderDataStore
    {
        return $this->renderData;
    }

    #[Override]
    public function key(): string
    {
        return 'builder_blocks';
    }

    #[Override]
    public function supports(FrontendRenderContextData $context): bool
    {
        return ! $context->isError && $context->page instanceof Pageable;
    }

    #[Override]
    public function metadata(FrontendRenderContextData $context): PublicRenderDataContributionMetadataData
    {
        return $this->prepare($context)['metadata'];
    }

    #[Override]
    public function contribute(FrontendRenderContextData $context): PublicRenderDataContributionData
    {
        return $this->prepare($context)['value'];
    }

    /** @return list<class-string<Model>> */
    #[Override]
    public function cacheDependencyModelTypes(): array
    {
        $types = [Page::class, Widget::class, Media::class, Translation::class];
        foreach (CapellCore::getPageTypes() as $type) {
            if (is_string($type->model) && is_a($type->model, Pageable::class, true)) {
                $types[] = $type->model;
            }
        }

        return array_values(array_unique($types));
    }

    /** @return array{value: PublicRenderDataContributionData, metadata: PublicRenderDataContributionMetadataData} */
    private function prepare(FrontendRenderContextData $context): array
    {
        if (isset($this->prepared[$context])) {
            return $this->prepared[$context];
        }

        $owners = $context->page instanceof Model ? [$context->page] : [];
        if ($context->layout instanceof Layout && $context->language instanceof Language) {
            $loader = resolve(LayoutLoader::class);
            $loader->preloadLayoutWidgets($context->layout, $context->language, $context->page);
            foreach ($context->layout->containers ?? [] as $containerKey => $container) {
                foreach (LayoutWidgetData::fromContainer(is_array($container) ? $container : []) as $widgetData) {
                    $key = LayoutWidgetData::key($widgetData);
                    if ($key === null) {
                        continue;
                    }

                    $widget = $loader->getLayoutWidget($context->layout, $key, $context->language, $context->page, (string) $containerKey, LayoutWidgetData::occurrence($widgetData));
                    if ($widget instanceof Widget) {
                        $owners[] = $widget;
                    }
                }
            }
        }

        // Resolve owners first, then hydrate media and metadata once across the whole render.
        $owners = array_values(collect($owners)->keyBy(static fn (Model $owner): int => spl_object_id($owner))->all());
        foreach (collect($owners)->groupBy(static fn (Model $owner): string => $owner::class) as $group) {
            new ModelCollection($group->all())->loadMissing(['translation', 'blueprint']);
        }

        $translations = [];
        foreach ($owners as $owner) {
            $translation = $owner->getRelation('translation');
            if ($translation instanceof Translation) {
                $translation->setRelation('translatable', $owner);
                $translations[] = $translation;
            }
        }

        new ModelCollection($translations)->loadMissing('media');
        $allMedia = [];
        foreach ($translations as $translation) {
            $ownerMedia = $translation->getRelation('media');
            foreach ($ownerMedia instanceof Collection ? $ownerMedia : [] as $media) {
                if ($media instanceof Media && (is_int($media->getKey()) || is_string($media->getKey()))) {
                    $allMedia[$media->getKey()] = $media;
                }
            }
        }

        new ModelCollection(array_values($allMedia))->loadMissing('translations.language');
        $blocks = [];
        $dependencies = [];
        $store = $this->renderData;
        foreach ($owners as $owner) {
            $translation = $owner->getRelation('translation');
            $content = $translation instanceof Translation ? $translation->content : null;
            if (! $translation instanceof Translation || ! is_array($content)) {
                continue;
            }

            $dependencies[] = PublicRenderDataCacheDependencyData::forModel($owner);
            $dependencies[] = PublicRenderDataCacheDependencyData::forModel($translation);
            foreach ($content as $block) {
                if (! is_array($block) || ! is_string($block['type'] ?? null) || ! is_array($block['data'] ?? null)) {
                    continue;
                }

                $state = $block['data'];
                $mediaBlock = in_array($block['type'], ['logos', 'team'], true);
                $images = $mediaBlock ? ResolveBuilderBlockMediaAction::run($translation, $state['media_key'] ?? null) : [];
                $renderData = BuildBuilderBlockRenderDataAction::run($block['type'], $state, $images);
                $store->put($owner, $block['type'], $state, $renderData);
                $blocks[] = $renderData;
                $collection = $mediaBlock ? ResolveBuilderBlockMediaAction::collection($state['media_key'] ?? null) : null;
                foreach ($collection === null ? [] : $translation->getMedia($collection) as $media) {
                    if (! $media instanceof Media) {
                        continue;
                    }

                    $dependencies[] = PublicRenderDataCacheDependencyData::forModel($media);
                    $metadataTranslations = $media->getRelation('translations');
                    foreach ($metadataTranslations instanceof Collection ? $metadataTranslations : [] as $metadata) {
                        if ($metadata instanceof Translation) {
                            $dependencies[] = PublicRenderDataCacheDependencyData::forModel($metadata);
                        }
                    }
                }
            }
        }

        $value = new PublicRenderDataContributionData((object) ['blocks' => $blocks]);
        $metadata = new PublicRenderDataContributionMetadataData(hash('sha256', json_encode($value->value, JSON_THROW_ON_ERROR)), cacheDependencies: $dependencies);

        return $this->prepared[$context] = ['value' => $value, 'metadata' => $metadata];
    }
}
