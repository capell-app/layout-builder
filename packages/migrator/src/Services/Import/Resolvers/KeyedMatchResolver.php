<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Import\Resolvers;

use Illuminate\Database\Eloquent\Model;

/**
 * Match by stable key, falling back to a normalised-name lookup.
 *
 * Use when an incoming record has a developer-chosen identifier (layout
 * "key", type "key", site "slug"). The resolver prefers that exact match
 * because it survives renames; it only normalises the display name as a
 * last resort for packages produced by older exporters that lacked keys.
 *
 * @template TModel of Model
 */
final readonly class KeyedMatchResolver implements MatchResolver
{
    /**
     * @param  class-string<TModel>  $modelClass
     */
    public function __construct(
        private string $modelClass,
        private string $keyColumn = 'key',
        private ?string $nameColumn = 'name',
    ) {}

    public function resolve(array $descriptor): ?MatchResolution
    {
        $key = $descriptor[$this->keyColumn] ?? null;
        if (is_string($key) && $key !== '') {
            $model = $this->modelClass::query()->where($this->keyColumn, $key)->first();
            if ($model instanceof Model) {
                return new MatchResolution(localId: $model->getKey(), strategy: $this->keyColumn);
            }
        }

        if ($this->nameColumn !== null) {
            $name = $descriptor[$this->nameColumn] ?? null;
            if (is_string($name) && $name !== '') {
                $normalised = $this->normalise($name);
                $model = $this->modelClass::query()
                    ->whereRaw(sprintf('LOWER(TRIM(%s)) = ?', $this->nameColumn), [$normalised])
                    ->first();
                if ($model instanceof Model) {
                    return new MatchResolution(
                        localId: $model->getKey(),
                        strategy: $this->nameColumn . ':normalised',
                        confidence: 0.7,
                    );
                }
            }
        }

        return null;
    }

    private function normalise(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
