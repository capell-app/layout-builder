<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Bridges\FilamentPeek;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\FilamentPeek\Actions\StoreLayoutBuilderPreviewStateAction;

/** Keeps Filament Peek previews in step with unsaved layout changes; nothing when Peek is not available. */
final class LayoutBuilderPeekBridge
{
    public static function clear(Pageable $page): void
    {
        if (! CapellCore::isPackageAvailable('capell-app/filament-peek')) {
            return;
        }

        StoreLayoutBuilderPreviewStateAction::make()->clear($page);
    }

    /**
     * @param  array<array-key, mixed>|null  $containers
     * @param  array<array-key, mixed>  $assets
     */
    public static function store(Pageable $page, Layout $layout, ?array $containers, array $assets): void
    {
        if (! CapellCore::isPackageAvailable('capell-app/filament-peek')) {
            return;
        }

        StoreLayoutBuilderPreviewStateAction::run(page: $page, layout: $layout, containers: $containers, assets: $assets);
    }
}
