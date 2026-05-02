<?php

declare(strict_types=1);

namespace Capell\Mosaic\Actions;

use Lorisleiva\Actions\Concerns\AsObject;

class FindReusableWidgetsAction
{
    use AsObject;

    /**
     * @return array<int, mixed>
     */
    public function handle(string $intent): array
    {
        return [];
    }
}
