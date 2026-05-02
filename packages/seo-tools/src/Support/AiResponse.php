<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support;

final class AiResponse
{
    public function __construct(
        public string $content,
        public int $tokensUsed,
        public string $model,
        public float $duration,
        public array $metadata = [],
    ) {}
}
