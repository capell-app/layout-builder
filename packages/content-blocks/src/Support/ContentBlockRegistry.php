<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Support;

use Capell\ContentBlocks\Data\ContentBlockDefinitionData;
use InvalidArgumentException;

class ContentBlockRegistry
{
    /**
     * @var array<string, ContentBlockDefinitionData>
     */
    private array $blocks = [];

    public function register(ContentBlockDefinitionData $block): void
    {
        if (isset($this->blocks[$block->key])) {
            throw new InvalidArgumentException(sprintf('Content block [%s] is already registered.', $block->key));
        }

        $this->blocks[$block->key] = $block;
    }

    /**
     * @return array<string, ContentBlockDefinitionData>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    public function get(string $key): ?ContentBlockDefinitionData
    {
        return $this->blocks[$key] ?? null;
    }
}
