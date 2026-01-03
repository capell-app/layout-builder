<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget\Assets;

use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\View\Components\Widget\Assets;

class Carousel extends Assets
{
    protected static string $defaultView = WidgetComponentEnum::AssetCarousel->value;
}
