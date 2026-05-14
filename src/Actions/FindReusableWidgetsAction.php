<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

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
