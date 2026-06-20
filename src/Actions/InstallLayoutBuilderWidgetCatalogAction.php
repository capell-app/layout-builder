<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use BackedEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Support\Creator\BlueprintCreator as CoreTypeCreator;
use Capell\LayoutBuilder\Data\LayoutWidgetCatalogDefinitionData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(?Collection<int, Language> $languages = null, bool $extraWidgets = false)
 */
class InstallLayoutBuilderWidgetCatalogAction
{
    use AsFake;
    use AsObject;

    private const string NavigationPackage = 'capell-app/navigation';

    /**
     * @param  Collection<array-key, mixed>  $languages
     */
    public function handle(?Collection $languages = null, bool $extraWidgets = false): void
    {
        /** @var Collection<int, Language> $catalogLanguages */
        $catalogLanguages = $languages ?? Language::query()->get();

        $definitions = LayoutWidgetCatalogDefinitionData::defaultCatalog();

        if ($extraWidgets) {
            $definitions = [
                ...$definitions,
                ...LayoutWidgetCatalogDefinitionData::extraCatalog(),
            ];
        }

        $typeCreator = resolve(TypeCreator::class);

        foreach ($definitions as $definition) {
            $type = $this->createType($typeCreator, $definition);

            $widget = $this->installWidget($definition, $type);

            $this->installTranslations($widget, $definition, $catalogLanguages);
        }
    }

    private function createType(TypeCreator $typeCreator, LayoutWidgetCatalogDefinitionData $definition): Blueprint
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
            'kitchenSinkReferenceWidgetType' => $typeCreator->kitchenSinkReferenceWidgetType(),
            default => $typeCreator->defaultWidgetType(),
        };
    }

    private function installWidget(LayoutWidgetCatalogDefinitionData $definition, Blueprint $type): Widget
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

        $widget = Widget::query()->firstOrCreate([
            'key' => $definition->key,
        ], [
            'name' => $definition->name,
            'blueprint_id' => $type->id,
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

    private function installNavigation(LayoutWidgetCatalogDefinitionData $definition): ?Model
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
    private function installTranslations(Widget $widget, LayoutWidgetCatalogDefinitionData $definition, Collection $languages): void
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
