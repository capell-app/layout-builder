<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Enums;

enum AssetKind: string
{
    case Css = 'css';
    case Js = 'js';
}
