<?php

declare(strict_types=1);

namespace Capell\Assistant\Support;

class AiFeatureRegistry
{
    protected array $features = [];

    public function __construct(private array $config = [])
    {
        $this->registerDefaultFeatures();
    }

    public function register(string $name, array $feature): void
    {
        $this->features[$name] = array_merge(['name' => $name], $feature, $this->config[$name] ?? []);
    }

    public function get(string $name): ?array
    {
        return $this->features[$name] ?? null;
    }

    public function all(bool $enabledOnly = false): array
    {
        if (! $enabledOnly) {
            return $this->features;
        }

        return array_filter($this->features, static fn (array $feature): bool => (bool) ($feature['enabled'] ?? false));
    }

    public function is(string $name): bool
    {
        return (bool) ($this->features[$name]['enabled'] ?? false);
    }

    public function getHandler(string $name): ?string
    {
        return $this->features[$name]['handler'] ?? null;
    }

    public function getConfig(string $name): array
    {
        return $this->features[$name] ?? [];
    }

    protected function registerDefaultFeatures(): void
    {
        foreach ($this->config as $name => $config) {
            if (is_array($config) && isset($config['handler'])) {
                $this->register($name, $config);
            }
        }
    }
}
