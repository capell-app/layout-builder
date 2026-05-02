<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data;

use Spatie\LaravelData\Data;

class LayoutPlanResultData extends Data
{
    public function __construct(
        public LayoutPlanData $plan,
        public string $status = 'draft',
        public bool $requiresApproval = true,
    ) {}
}
