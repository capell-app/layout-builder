<?php

declare(strict_types=1);

namespace Capell\Layout\Helpers;

use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\CapellLayoutCacheKeyEnum;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Models\Widget;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CapellLayoutHelper
{
    private static function getCached(string $key, callable $resolver, bool $asBool = false): mixed
    {
        $cached = Cache::driver('array')->get($key);
        if ($cached !== null) {
            return $asBool ? (bool) $cached : $cached;
        }
        $result = $resolver();
        Cache::driver('array')->forever($key, $result);
        return $asBool ? (bool) $result : $result;
    }

    public static function getWidgetOptions(?array $typeId, ?array $group, ?string $search = null): Collection
    {
        $cacheKey = CapellLayoutCacheKeyEnum::WidgetOptions->value . md5(json_encode([$typeId, $group, $search]));
        return self::getCached(
            $cacheKey,
            fn (): Collection => self::getWidgetOptionsQuery($typeId, $group)
                ->when(
                    $search,
                    fn (Builder $query, string $search) => $query
                        ->where('name', 'like', sprintf('%%%s%%', $search)),
                )
                ->enabled()
                ->ordered()
                ->get(),
        );
    }

    public static function getWidgetOptionsQuery(?array $typeId, ?array $groups): Builder
    {
        return CapellCore::getModel(ModelEnum::Widget->name)::query()
            ->with('type')
            ->when($typeId !== null && $typeId !== [], fn (Builder $query) => $query->whereIn('type_id', $typeId))
            ->when(
                $groups,
                fn (Builder $query) => $query->whereHas(
                    'type',
                    fn (BuilderContract $query) => $query->where(
                        fn ($query) => $query
                            ->when(
                                in_array('default', $groups),
                                fn (Builder $query): Builder => $query->whereNull('group'),
                            )
                            ->when(
                                count($groups) > 1 || ! in_array('default', $groups, true),
                                function (Builder $query) use ($groups): Builder {
                                    if (in_array('default', $groups, true)) {
                                        $groups = array_diff($groups, ['default']);

                                        return $query->orWhereIn('group', $groups);
                                    }

                                    return $query->whereIn('group', $groups);
                                },
                            ),
                    ),
                ),
            );
    }

    /**
     * Get a Widget model by its key, with caching.
     */
    public static function getWidgetByKey(string $widgetKey): ?Widget
    {
        $cacheKey = CapellLayoutCacheKeyEnum::WidgetByKey->value . $widgetKey;
        return self::getCached(
            $cacheKey,
            fn () => Widget::query()->firstWhere('key', $widgetKey)
        );
    }
}
