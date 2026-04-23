<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Events;

class AiGenerationCompleted
{
    public function __construct(public string $actionClass, public mixed $result, public array $metadata = []) {}
}
