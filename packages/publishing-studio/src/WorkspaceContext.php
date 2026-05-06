<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use Capell\PublishingStudio\Models\Workspace;

/**
 * Process-local holder for the current workspace context.
 *
 * The middleware sets this at the start of a request; the global
 * {@see WorkspaceContextScope} reads it to filter queries. A `null` value
 * means "live" — queries only see rows with `workspace_id = 0`.
 *
 * Resetting to `null` is idempotent and the only destructor in the class —
 * stateful singletons usually hide bugs in test suites, so we keep the
 * surface tiny on purpose.
 */
final class WorkspaceContext
{
    private static ?Workspace $currentWorkspace = null;

    public static function set(?Workspace $workspace): void
    {
        self::$currentWorkspace = $workspace;
    }

    public static function current(): ?Workspace
    {
        return self::$currentWorkspace;
    }

    public static function currentId(): ?int
    {
        return self::$currentWorkspace?->id;
    }

    public static function clear(): void
    {
        self::$currentWorkspace = null;
    }

    public static function isInWorkspace(): bool
    {
        return self::$currentWorkspace instanceof Workspace;
    }

    /**
     * Execute a callback with the given workspace (or null for live) as the
     * active context, then restore the previous context.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function runWith(?Workspace $workspace, callable $callback): mixed
    {
        $previousWorkspace = self::$currentWorkspace;
        self::$currentWorkspace = $workspace;

        try {
            return $callback();
        } finally {
            self::$currentWorkspace = $previousWorkspace;
        }
    }
}
