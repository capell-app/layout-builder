<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use BackedEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Support\Creator\BlueprintCreator as CoreTypeCreator;
use Capell\LayoutBuilder\Data\BlockDefinitionData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(?Collection<int, Language> $languages = null, bool $extraBlocks = false)
 */
class InstallLayoutBuilderBlockCatalogAction
{
    use AsFake;
    use AsObject;

    private const string NavigationPackage = 'capell-app/navigation';

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function handle(?Collection $languages = null, bool $extraBlocks = false): void
    {
        /** @var Collection<int, Language> $catalogLanguages */
        $catalogLanguages = $languages ?? Language::query()->get();

        $definitions = BlockDefinitionData::defaultCatalog();

        if ($extraBlocks) {
            $definitions = [
                ...$definitions,
                ...BlockDefinitionData::extraCatalog(),
            ];
        }

        $typeCreator = resolve(TypeCreator::class);

        foreach ($definitions as $definition) {
            $type = $this->createType($typeCreator, $definition);

            $block = $this->installBlock($definition, $type);

            $this->installTranslations($block, $definition, $catalogLanguages);
        }
    }

    private function createType(TypeCreator $typeCreator, BlockDefinitionData $definition): Blueprint
    {
        return match ($definition->typeCreatorMethod) {
            'assetsBlockType' => $typeCreator->assetsBlockType(),
            'contentsBlockType' => $typeCreator->contentsBlockType(),
            'defaultBlockType' => $typeCreator->defaultBlockType(),
            'mediaBlockType' => $typeCreator->mediaBlockType(),
            'navigationBlockType' => $typeCreator->navigationBlockType(),
            'pageContentBlockType' => $typeCreator->pageContentBlockType(),
            'pagesBlockType' => $typeCreator->pagesBlockType(),
            'resultsBlockType' => $typeCreator->resultsBlockType(),
            'systemBlockType' => $typeCreator->systemBlockType(),
            'kitchenSinkReferenceBlockType' => $typeCreator->kitchenSinkReferenceBlockType(),
            default => $typeCreator->defaultBlockType(),
        };
    }

    private function installBlock(BlockDefinitionData $definition, Blueprint $type): Widget
    {
        $meta = $this->normalizeArray($definition->meta);

        if ($definition->hasNavigation()) {
            $navigation = $this->installNavigation($definition);

            if ($navigation instanceof Model) {
                $meta = [
                    'navigation' => (string) $navigation->getAttribute('key'),
                    ...$meta,
                ];
            }
        }

        $block = Widget::query()->firstOrCreate([
            'key' => $definition->key,
        ], [
            'name' => $definition->name,
            'blueprint_id' => $type->id,
            'meta' => $meta,
            'admin' => $this->normalizeArray($definition->admin),
        ]);

        $missingMeta = $this->missingMeta($block, $meta);

        if ($missingMeta !== []) {
            $block->forceFill([
                'meta' => [
                    ...($block->meta ?? []),
                    ...$missingMeta,
                ],
            ])->save();
        }

        return $block;
    }

    private function installNavigation(BlockDefinitionData $definition): ?Model
    {
        $navigationModel = Navigation::class;

        if (! CapellCore::isPackageInstalled(self::NavigationPackage) || ! class_exists($navigationModel)) {
            return null;
        }

        $navigationType = Blueprint::query()->navigationType()->default()->first();

        if (! $navigationType instanceof Blueprint) {
            $navigationType = resolve(CoreTypeCreator::class)->createNavigationType();
        }

        $navigation = $navigationModel::query()->firstOrCreate([
            'key' => $definition->navigationKey,
            'blueprint_id' => $navigationType->id,
            'site_id' => null,
        ], [
            'name' => $definition->navigationName,
            'items' => $this->normalizeArray($definition->navigationItems),
        ]);

        if ($definition->navigationItems !== [] && $navigation->getAttribute('items') !== $definition->navigationItems) {
            $navigation->forceFill([
                'items' => $this->normalizeArray($definition->navigationItems),
            ])->save();
        }

        return $navigation;
    }

    /**
     * @param  Collection<int, Language>  $languages
     */
    private function installTranslations(Widget $block, BlockDefinitionData $definition, Collection $languages): void
    {
        if ($definition->translations === []) {
            return;
        }

        $translationData = $this->normalizeArray($definition->translations);

        $languages->each(function (Language $language) use ($block, $translationData): void {
            $block->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], $translationData);
        });
    }

    /**
     * @param  array<string, mixed>  $expectedMeta
     * @return array<string, mixed>
     */
    private function missingMeta(Widget $block, array $expectedMeta): array
    {
        $existingMeta = $block->meta ?? [];
        $missingMeta = [];

        foreach ($expectedMeta as $metaKey => $metaValue) {
            if (in_array($metaKey, ['component', 'component_item', 'livewire', 'view_file'], true)) {
                continue;
            }

            if (! array_key_exists($metaKey, $existingMeta)) {
                $missingMeta[$metaKey] = $metaValue;
            }
        }

        return $missingMeta;
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function normalizeArray(array $values): array
    {
        $normalizedValues = [];

        foreach ($values as $valueKey => $value) {
            $normalizedValues[$valueKey] = $this->normalizeValue($value);
        }

        return $normalizedValues;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (! is_array($value)) {
            return $value;
        }

        return $this->normalizeArray($value);
    }
}
