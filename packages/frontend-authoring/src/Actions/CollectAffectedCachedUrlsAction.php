<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Actions;

use Capell\Core\Enums\CacheEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsObject;
use ReflectionClass;

class CollectAffectedCachedUrlsAction
{
    use AsObject;

    /**
     * @return list<string>
     */
    public function handle(Model $model): array
    {
        $cacheIndex = Cache::get(CacheEnum::modelUrlCacheKey());

        if (! is_array($cacheIndex) || $cacheIndex === []) {
            return [];
        }

        $modelCacheKey = (new ReflectionClass($model))->getShortName();
        $recordKey = (int) $model->getKey();
        $urls = [];

        foreach ($cacheIndex as $url => $models) {
            if (! is_string($url) || ! is_array($models)) {
                continue;
            }

            $ids = $models[$modelCacheKey] ?? null;

            if (! is_array($ids)) {
                continue;
            }

            if (in_array($recordKey, array_map('intval', $ids), true)) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }
}
