<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use BackedEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Type;
use Capell\Core\Support\Creator\TypeCreator as CoreTypeCreator;
use Capell\Mosaic\Data\WidgetDefinitionData;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Support\Creator\TypeCreator;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(?Collection $languages = null, bool $extraWidgets = false)
 */
class InstallMosaicWidgetCatalogAction
{
    use AsFake;
    use AsObject;

    public function handle(?Collection $languages = null, bool $extraWidgets = false): void
    {
        /** @var Collection<int, Language> $catalogLanguages */
        $catalogLanguages = $languages ?? Language::query()->get();

        $definitions = WidgetDefinitionData::defaultCatalog();

        if ($extraWidgets) {
            $definitions = [
                ...$definitions,
                ...WidgetDefinitionData::extraCatalog(),
            ];
        }

        $typeCreator = resolve(TypeCreator::class);

        foreach ($definitions as $definition) {
            $type = $this->createType($typeCreator, $definition);

            $widget = $this->installWidget($definition, $type);

            $this->installTranslations($widget, $definition, $catalogLanguages);
        }
    }

    private function createType(TypeCreator $typeCreator, WidgetDefinitionData $definition): Type
    {
        return match ($definition->typeCreatorMethod) {
            'assetsWidgetType' => $typeCreator->assetsWidgetType(),
            'contentsWidgetType' => $typeCreator->contentsWidgetType(),
            'defaultWidgetType' => $typeCreator->defaultWidgetType(),
            'mediaWidgetType' => $typeCreator->mediaWidgetType(),
            'navigationWidgetType' => $typeCreator->navigationWidgetType(),
            'pageContentWidgetType' => $typeCreator->pageContentWidgetType(),
            'pagesWidgetType' => $typeCreator->pagesWidgetType(),
            'resultsWidgetType' => $typeCreator->resultsWidgetType(),
            'systemWidgetType' => $typeCreator->systemWidgetType(),
        };
    }

    private function installWidget(WidgetDefinitionData $definition, Type $type): Widget
    {
        $meta = $this->normalizeArray($definition->meta);

        if ($definition->hasNavigation()) {
            $navigation = $this->installNavigation($definition);

            $meta = [
                'navigation' => $navigation->key,
                ...$meta,
            ];
        }

        $widget = Widget::query()->firstOrCreate([
            'key' => $definition->key,
        ], [
            'name' => $definition->name,
            'type_id' => $type->id,
            'meta' => $meta,
            'admin' => $this->normalizeArray($definition->admin),
        ]);

        $missingMeta = $this->missingMeta($widget, $meta);

        if ($missingMeta !== []) {
            $widget->forceFill([
                'meta' => [
                    ...($widget->meta ?? []),
                    ...$missingMeta,
                ],
            ])->save();
        }

        return $widget;
    }

    private function installNavigation(WidgetDefinitionData $definition): Navigation
    {
        $navigationType = Type::query()->navigationType()->default()->first();

        if (! $navigationType instanceof Type) {
            $navigationType = resolve(CoreTypeCreator::class)->createNavigationType();
        }

        $navigation = Navigation::query()->firstOrCreate([
            'key' => $definition->navigationKey,
            'type_id' => $navigationType->id,
            'site_id' => null,
        ], [
            'name' => $definition->navigationName,
            'items' => $this->normalizeArray($definition->navigationItems),
        ]);

        if ($definition->navigationItems !== [] && $navigation->items !== $definition->navigationItems) {
            $navigation->forceFill([
                'items' => $this->normalizeArray($definition->navigationItems),
            ])->save();
        }

        return $navigation;
    }

    /**
     * @param  Collection<int, Language>  $languages
     */
    private function installTranslations(Widget $widget, WidgetDefinitionData $definition, Collection $languages): void
    {
        if ($definition->translations === []) {
            return;
        }

        $translationData = $this->normalizeArray($definition->translations);

        $languages->each(function (Language $language) use ($widget, $translationData): void {
            $widget->translations()->firstOrCreate([
                'language_id' => $language->id,
            ], $translationData);
        });
    }

    /**
     * @param  array<string, mixed>  $expectedMeta
     * @return array<string, mixed>
     */
    private function missingMeta(Widget $widget, array $expectedMeta): array
    {
        $existingMeta = $widget->meta ?? [];
        $missingMeta = [];

        foreach ($expectedMeta as $metaKey => $metaValue) {
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
