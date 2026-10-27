<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\BlockLibrary\Data\BuilderBlockRenderData;
use Illuminate\Database\Eloquent\Model;
use WeakMap;

/** Per-owner prepared values; weak keys cannot retain models between requests. */
final class BuilderBlockRenderDataStore
{
    /** @var WeakMap<Model, array<string, BuilderBlockRenderData>> */
    private WeakMap $values;

    public function __construct()
    {
        $this->values = new WeakMap;
    }

    /** @param array<array-key, mixed> $state */
    public function put(Model $owner, string $type, array $state, BuilderBlockRenderData $data): void
    {
        $values = $this->values[$owner] ?? [];
        $values[$this->key($type, $state)] = $data;
        $this->values[$owner] = $values;
    }

    /** @param array<array-key, mixed> $state */
    public function get(mixed $owner, string $type, array $state): ?BuilderBlockRenderData
    {
        return $owner instanceof Model ? ($this->values[$owner][$this->key($type, $state)] ?? null) : null;
    }

    /** @param array<array-key, mixed> $state */
    private function key(string $type, array $state): string
    {
        return $type . ':' . hash('sha256', serialize($state));
    }
}
