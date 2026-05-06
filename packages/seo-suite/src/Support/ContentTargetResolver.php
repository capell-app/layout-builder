<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support;

use Capell\SeoSuite\Contracts\ContentTargetContract;

class ContentTargetResolver
{
    /** @var array<string, ContentTargetContract> */
    private array $targets = [];

    public function register(ContentTargetContract $target): void
    {
        $this->targets[$target->handles()] = $target;
    }

    public function resolve(string $handle): ?ContentTargetContract
    {
        return $this->targets[$handle] ?? null;
    }

    public function preferred(): ?ContentTargetContract
    {
        return array_values($this->targets)[count($this->targets) - 1] ?? null;
    }

    /** @return array<string, ContentTargetContract> */
    public function all(): array
    {
        return $this->targets;
    }
}
