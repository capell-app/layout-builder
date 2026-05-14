<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Support;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use InvalidArgumentException;

final class BlockRegistry
{
    /**
     * @var array<string, BlockDefinitionData>
     */
    private array $definitions = [];

    public function register(BlockDefinitionData $definition): void
    {
        if (isset($this->definitions[$definition->key])) {
            throw new InvalidArgumentException(sprintf('Content block [%s] is already registered.', $definition->key));
        }

        $this->definitions[$definition->key] = $definition;
    }

    /**
     * @return array<string, BlockDefinitionData>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<string, BlockDefinitionData>
     */
    public function forCategory(string $category): array
    {
        return array_filter(
            $this->definitions,
            static fn (BlockDefinitionData $definition): bool => $definition->category === $category,
        );
    }

    public function get(string $key): ?BlockDefinitionData
    {
        return $this->definitions[$key] ?? null;
    }

    public function getOrFail(string $key): BlockDefinitionData
    {
        return $this->get($key)
            ?? throw new InvalidArgumentException(sprintf('Content block [%s] is not registered.', $key));
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }
}
