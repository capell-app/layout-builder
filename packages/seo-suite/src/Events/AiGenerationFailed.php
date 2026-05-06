<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Events;

use Throwable;

class AiGenerationFailed
{
    public function __construct(public string $actionClass, public Throwable $exception) {}
}
