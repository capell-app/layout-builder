<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Support\StaticSite;

use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Closure;

final class StaticSiteExtensionRegistry
{
    private static ?self $instance = null;

    /** @var array<string, callable(Site, SiteDomain, Closure(string): void): void> */
    private array $handlers = [];

    public static function instance(): self
    {
        return self::$instance ??= new self;
    }

    /**
     * @param  callable(Site, SiteDomain, Closure(string): void): void  $handler
     */
    public function register(string $key, callable $handler): void
    {
        $this->handlers[$key] = $handler;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->handlers);
    }

    /**
     * @return array<string, callable(Site, SiteDomain, Closure(string): void): void>
     */
    public function all(): array
    {
        return $this->handlers;
    }

    public function clear(): void
    {
        $this->handlers = [];
    }
}
