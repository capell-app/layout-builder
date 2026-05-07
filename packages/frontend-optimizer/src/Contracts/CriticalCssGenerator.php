<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Contracts;

use Capell\FrontendOptimizer\Models\FrontendRenderProfile;

interface CriticalCssGenerator
{
    public function generate(FrontendRenderProfile $profile, string $url): string;
}
