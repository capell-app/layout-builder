<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Bridges\Navigation;

use Capell\Core\Facades\CapellCore;
use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Capell\Navigation\Support\Creator\NavigationDemoCreator;

/**
 * The only place Layout Builder names Navigation classes. Each method returns
 * the class only while Navigation is installed, so callers never import it and
 * Layout Builder boots and works when Navigation is absent.
 */
final class LayoutBuilderNavigationBridge
{
    /** @return class-string<Navigation>|null */
    public static function navigationModel(): ?string
    {
        if (! CapellCore::isPackageAvailable('capell-app/navigation')) {
            return null;
        }

        if (! CapellCore::isPackageInstalled('capell-app/navigation')) {
            return null;
        }

        return Navigation::class;
    }

    /** @return class-string<NavigationSelect>|null */
    public static function navigationSelect(): ?string
    {
        if (! CapellCore::isPackageAvailable('capell-app/navigation')) {
            return null;
        }

        if (! CapellCore::isPackageInstalled('capell-app/navigation')) {
            return null;
        }

        return NavigationSelect::class;
    }

    /** @return class-string<NavigationCreator>|null */
    public static function navigationCreator(): ?string
    {
        if (! CapellCore::isPackageAvailable('capell-app/navigation')) {
            return null;
        }

        if (! CapellCore::isPackageInstalled('capell-app/navigation')) {
            return null;
        }

        return NavigationCreator::class;
    }

    /** @return class-string<NavigationDemoCreator>|null */
    public static function navigationDemoCreator(): ?string
    {
        if (! CapellCore::isPackageAvailable('capell-app/navigation')) {
            return null;
        }

        if (! CapellCore::isPackageInstalled('capell-app/navigation')) {
            return null;
        }

        return NavigationDemoCreator::class;
    }
}
