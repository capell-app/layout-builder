<?php

declare(strict_types=1);

namespace Capell\AccessGate\Support;

use Capell\AccessGate\Contracts\AccessRequestMethod;
use InvalidArgumentException;

final class AccessRequestMethodRegistry
{
    /** @var array<string, AccessRequestMethod|class-string<AccessRequestMethod>> */
    private array $methods = [];

    /**
     * @param  AccessRequestMethod|class-string<AccessRequestMethod>  $method
     */
    public function register(AccessRequestMethod|string $method): void
    {
        $resolvedMethod = is_string($method) ? resolve($method) : $method;

        throw_unless($resolvedMethod instanceof AccessRequestMethod, InvalidArgumentException::class, 'Access gate request methods must implement AccessRequestMethod.');

        $this->methods[$resolvedMethod->key()] = $method;
    }

    /**
     * @return array<string, AccessRequestMethod>
     */
    public function all(): array
    {
        return collect($this->methods)
            ->mapWithKeys(function (AccessRequestMethod|string $method): array {
                $resolvedMethod = is_string($method) ? resolve($method) : $method;

                return [$resolvedMethod->key() => $resolvedMethod];
            })
            ->all();
    }
}
