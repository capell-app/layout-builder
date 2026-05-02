<?php

declare(strict_types=1);

namespace Capell\Workspaces;

use Capell\Workspaces\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Registry of models that participate in the workspace/version system.
 *
 * First-party models (Page, Navigation, Site, Type, Layout, Translation,
 * PageUrl, AssetRelation) register themselves from the core service
 * provider. External packages call {@see self::register()} from their own
 * service provider to opt in.
 */
final class WorkspaceRegistry
{
    /** @var array<class-string<Model>, RegisteredDraftable> */
    private static array $registered = [];

    /**
     * @param  class-string<Model>  $modelClass
     * @param  null|callable(Model, Workspace): Model  $cloneUsing
     * @param  null|callable(Model): Model  $finalizeOnPublish
     */
    public static function register(
        string $modelClass,
        ?callable $cloneUsing = null,
        ?callable $finalizeOnPublish = null,
    ): void {
        self::$registered[$modelClass] = new RegisteredDraftable(
            modelClass: $modelClass,
            cloneUsing: $cloneUsing,
            finalizeOnPublish: $finalizeOnPublish,
        );
    }

    /**
     * @return array<class-string<Model>, RegisteredDraftable>
     */
    public static function all(): array
    {
        return self::$registered;
    }

    /** @param  class-string<Model>  $modelClass */
    public static function get(string $modelClass): RegisteredDraftable
    {
        if (! isset(self::$registered[$modelClass])) {
            throw new RuntimeException(sprintf(
                'Model %s is not registered as a workspace draftable. Call WorkspaceRegistry::register() from your service provider.',
                $modelClass,
            ));
        }

        return self::$registered[$modelClass];
    }

    /** @param  class-string<Model>  $modelClass */
    public static function isRegistered(string $modelClass): bool
    {
        return isset(self::$registered[$modelClass]);
    }

    /**
     * Used in tests to reset state between cases. Not for production use.
     */
    public static function reset(): void
    {
        self::$registered = [];
    }

    /**
     * @return array<int, class-string<Model>>
     */
    public static function modelClasses(): array
    {
        return array_keys(self::$registered);
    }
}
