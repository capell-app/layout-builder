<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Bridges\HtmlCache;

use Capell\Core\Facades\CapellCore;
use Capell\HtmlCache\Actions\ClearCachedUrlsForModelAction;
use Illuminate\Database\Eloquent\Model;

/** Clears cached public pages that show a changed model; nothing when HTML Cache is not available. */
final class LayoutBuilderHtmlCacheBridge
{
    public static function clearForModel(Model $model, bool $refresh): void
    {
        if (! CapellCore::isPackageAvailable('capell-app/html-cache')) {
            return;
        }

        ClearCachedUrlsForModelAction::run($model, refresh: $refresh);
    }
}
