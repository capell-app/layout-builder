<?php

declare(strict_types=1);

namespace Capell\SeoTools\Contracts;

use Capell\SeoTools\Models\AiCreatorSession;

interface ContentTargetContract
{
    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function apply(array $sections, AiCreatorSession $session): void;

    public function handles(): string;
}
