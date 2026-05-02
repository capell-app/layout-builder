<?php

declare(strict_types=1);

namespace Capell\Mcp\Support;

use Capell\Mcp\Data\CapabilityData;
use Capell\Mcp\Enums\CapabilityServerEnum;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

final class CapellMcpCapabilityRegistry
{
    /** @var array<string, CapabilityData> */
    private array $capabilities = [];

    public function register(CapabilityData $capability): void
    {
        if (isset($this->capabilities[$capability->key])) {
            throw new InvalidArgumentException(sprintf('MCP capability [%s] is already registered.', $capability->key));
        }

        $this->capabilities[$capability->key] = $capability;
    }

    public function get(string $key): CapabilityData
    {
        if (! isset($this->capabilities[$key])) {
            throw new InvalidArgumentException(sprintf('MCP capability [%s] is not registered.', $key));
        }

        return $this->capabilities[$key];
    }

    public function has(string $key): bool
    {
        return isset($this->capabilities[$key]);
    }

    /**
     * @param  array<int, string>|null  $scopes
     * @return Collection<int, CapabilityData>
     */
    public function visibleFor(CapabilityServerEnum $server, ?array $scopes = null): Collection
    {
        return $this->all()
            ->filter(fn (CapabilityData $capability): bool => $capability->server->isVisibleOn($server))
            ->filter(fn (CapabilityData $capability): bool => $this->requiredPackageIsAvailable($capability))
            ->filter(function (CapabilityData $capability) use ($scopes): bool {
                if ($scopes === null || in_array('*', $scopes, true)) {
                    return true;
                }

                return in_array($capability->scope, $scopes, true);
            })
            ->values();
    }

    /** @return Collection<int, CapabilityData> */
    public function all(): Collection
    {
        return collect(array_values($this->capabilities));
    }

    private function requiredPackageIsAvailable(CapabilityData $capability): bool
    {
        if ($capability->requiredPackage === null) {
            return true;
        }

        if (! class_exists('Capell\\Core\\Facades\\CapellCore')) {
            return false;
        }

        try {
            $core = app()->make('Capell\\Core\\Support\\CapellCoreManager');

            return is_object($core) && method_exists($core, 'isPackageInstalled')
                ? (bool) $core->isPackageInstalled($capability->requiredPackage)
                : false;
        } catch (Throwable) {
            return false;
        }
    }
}
