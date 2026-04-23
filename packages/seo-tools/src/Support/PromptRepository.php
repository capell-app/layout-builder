<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support;

class PromptRepository
{
    public function __construct(private array $prompts = []) {}

    public function get(string $key): ?array
    {
        return $this->prompts[$key] ?? null;
    }
}
