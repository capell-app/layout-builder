<?php

declare(strict_types=1);

namespace Capell\StarterSites\Support\Extensions;

use Closure;

final class StarterSitesActionSchemaRegistry
{
    /** @var array<string, Closure(): array<int, mixed>> */
    private array $schemaResolvers = [];

    /**
     * @param  Closure(): array<int, mixed>  $schema
     */
    public function register(string $action, Closure $schema): void
    {
        $this->schemaResolvers[$action] = $schema;
    }

    /**
     * @return array<int, mixed>
     */
    public function get(string $action): array
    {
        $schema = $this->schemaResolvers[$action] ?? null;

        if (! $schema instanceof Closure) {
            return [];
        }

        return $schema();
    }
}
