<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Models\Widget;

/**
 * Lets a package say, before public Blade renders, that one of its widgets has
 * nothing to show on this page, such as a form widget whose form is missing or
 * inactive on the current site. An unavailable widget renders no markup at all,
 * not even its wrapper. Implementations run once per widget occurrence before
 * the public query guard, so they may query, and must return false only when
 * the widget would otherwise render empty.
 */
interface PublicLayoutWidgetAvailability
{
    public const string TAG = 'capell.layout_builder.public_layout_widget_availability';

    public function available(Widget $widget, FrontendRenderContextData $context): bool;
}
