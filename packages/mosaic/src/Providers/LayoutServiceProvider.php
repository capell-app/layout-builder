<?php

declare(strict_types=1);

namespace Capell\Mosaic\Providers;

/**
 * Backward-compatible alias. Packages or code that depend on the old "layout" name
 * reference this class — it delegates everything to MosaicServiceProvider.
 */
class LayoutServiceProvider extends MosaicServiceProvider {}
