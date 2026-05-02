<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Enums\NavigationItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

/**
 * @method static Collection<int, Model> run(array|DataCollection $items)
 */
class ResolveNavigationItemModelsAction
{
    use AsAction;

    /**
     * @param  array<int|string, array<string, mixed>>|DataCollection  $items
     * @return Collection<int, Model>
     */
    public function handle(array|DataCollection $items): Collection
    {
        if ($items instanceof DataCollection) {
            $items = $items->all();
        }

        /** @var Collection<string, array<int, int>> $pageableIdsByType */
        $pageableIdsByType = $this->collectPageableIdsByType($items);

        return collect($pageableIdsByType)
            ->flatMap(function (array $pageableIds, string $pageableType): Collection {
                if ($pageableIds === []) {
                    return collect();
                }

                $modelClass = Relation::getMorphedModel($pageableType) ?? $pageableType;

                if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                    return collect();
                }

                /** @var class-string<Model> $modelClass */
                $queryModel = new $modelClass;

                return $modelClass::query()
                    ->whereIn($queryModel->getKeyName(), $pageableIds)
                    ->get();
            })
            ->values();
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $items
     * @return Collection<string, array<int, int>>
     */
    private function collectPageableIdsByType(array $items): Collection
    {
        return collect($this->flattenItems($items))
            ->filter(fn (array $item): bool => ($item['type'] ?? null) === NavigationItemType::Page->value)
            ->map(function (array $item): ?array {
                $pageableType = $item['data']['pageable_type'] ?? null;
                $pageableId = $item['data']['pageable_id'] ?? null;

                if (! is_string($pageableType) || $pageableType === '' || ! is_numeric($pageableId)) {
                    return null;
                }

                return [
                    'pageable_type' => $pageableType,
                    'pageable_id' => (int) $pageableId,
                ];
            })
            ->filter()
            ->groupBy('pageable_type')
            ->map(
                fn (Collection $pageables): array => $pageables
                    ->pluck('pageable_id')
                    ->map(fn (mixed $pageableId): int => (int) $pageableId)
                    ->unique()
                    ->values()
                    ->all(),
            );
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function flattenItems(array $items): array
    {
        $flattenedItems = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $flattenedItems[] = $item;

            $children = $item['children'] ?? null;

            if (is_array($children) && $children !== []) {
                $flattenedItems = [
                    ...$flattenedItems,
                    ...$this->flattenItems($children),
                ];
            }
        }

        return $flattenedItems;
    }
}
