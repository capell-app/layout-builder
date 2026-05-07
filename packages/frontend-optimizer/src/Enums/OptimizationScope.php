<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Enums;

enum OptimizationScope: string
{
    case Layout = 'layout';
    case RenderProfile = 'render_profile';
    case PageUrl = 'page_url';
}
