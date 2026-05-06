<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\BlockLibrary\Data\ContentBlockDefinitionData;
use InvalidArgumentException;

class ContentBlockRegistry
{
    /**
     * @var array<string, ContentBlockDefinitionData>
     */
    private array $blocks = [];

    /**
     * @var array<string, string>
     */
    private array $configuratorIndex = [];

    public function register(ContentBlockDefinitionData $block): void
    {
        if (isset($this->blocks[$block->key])) {
            throw new InvalidArgumentException(sprintf('Content block [%s] is already registered.', $block->key));
        }

        $this->blocks[$block->key] = $block;
        $this->configuratorIndex[$this->normalizeConfigurator($block->configurator)] = $block->key;

        if (is_subclass_of($block->configurator, ConfiguratorInterface::class)) {
            $this->configuratorIndex[$this->normalizeConfigurator($block->configurator::getKey())] = $block->key;
        }
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

    public function getByConfigurator(string $configurator): ?ContentBlockDefinitionData
    {
        $key = $this->configuratorIndex[$this->normalizeConfigurator($configurator)] ?? null;

        return $key !== null ? $this->get($key) : null;
    }

    private function normalizeConfigurator(string $configurator): string
    {
        return ltrim($configurator, '\\');
    }
}
