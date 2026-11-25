<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Bridges\FrontendAuthoring;

use Capell\Core\Facades\CapellCore;
use Capell\FrontendAuthoring\Support\EditorSurfaceRegistry;
use Illuminate\Contracts\Foundation\Application;

/** Contributes editable regions and the editor surface to Frontend Authoring; nothing when it is not available. */
final class LayoutBuilderFrontendAuthoringRegistrar
{
    public static function register(Application $app): void
    {
        if (! CapellCore::isPackageAvailable('capell-app/frontend-authoring')) {
            return;
        }

        $app->tag([LayoutBuilderEditableRegionContributor::class], 'capell-frontend-authoring:editable-regions');

        $register = static function (EditorSurfaceRegistry $registry) use ($app): void {
            $registry->register($app->make(LayoutBuilderEditorSurface::class));
        };

        $app->afterResolving(EditorSurfaceRegistry::class, $register);

        if ($app->resolved(EditorSurfaceRegistry::class)) {
            $register($app->make(EditorSurfaceRegistry::class));
        }
    }
}
