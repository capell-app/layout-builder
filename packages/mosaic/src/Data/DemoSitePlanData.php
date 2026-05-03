<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data;

use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class DemoSitePlanData extends Data
{
    /**
     * @param  array<string, mixed>  $contentTree
     */
    public function __construct(
        public readonly Site $site,
        public readonly array $contentTree,
        public readonly ?Model $user = null,
    ) {}
}
