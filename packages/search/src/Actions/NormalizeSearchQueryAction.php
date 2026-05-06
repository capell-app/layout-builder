<?php

declare(strict_types=1);

namespace Capell\Search\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

final class NormalizeSearchQueryAction
{
    use AsAction;

    public function handle(string $query): string
    {
        return trim((string) preg_replace('/\s+/', ' ', mb_strtolower($query)));
    }
}
