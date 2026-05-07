<?php

declare(strict_types=1);

namespace Capell\ContentSections\Support;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\ContentSections\Data\SectionDefinitionData;
use InvalidArgumentException;

class SectionRegistry
{
    /**
     * @var array<string, SectionDefinitionData>
     */
    private array $blocks = [];

    /**
     * @var array<string, string>
     */
    private array $configuratorIndex = [];

    public function register(SectionDefinitionData $block): void
    {
        if (isset($this->blocks[$block->key])) {
            throw new InvalidArgumentException(sprintf('Section [%s] is already registered.', $block->key));
        }

        $this->blocks[$block->key] = $block;
        $this->configuratorIndex[$this->normalizeConfigurator($block->configurator)] = $block->key;

        if (is_subclass_of($block->configurator, ConfiguratorInterface::class)) {
            $this->configuratorIndex[$this->normalizeConfigurator($block->configurator::getKey())] = $block->key;
        }
    }

    /**
     * @return array<string, SectionDefinitionData>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    public function get(string $key): ?SectionDefinitionData
    {
        return $this->blocks[$key] ?? null;
    }

    public function getByConfigurator(string $configurator): ?SectionDefinitionData
    {
        $key = $this->configuratorIndex[$this->normalizeConfigurator($configurator)] ?? null;

        return $key !== null ? $this->get($key) : null;
    }

    private function normalizeConfigurator(string $configurator): string
    {
        return ltrim($configurator, '\\');
    }
}
