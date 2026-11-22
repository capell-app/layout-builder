<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Support;

/**
 * Stands in for a final companion action and records each call made to it.
 */
final class LayoutBuilderCompanionActionSpy
{
    /** @var list<string> */
    public array $calls = [];

    public function handle(mixed ...$arguments): int
    {
        $this->calls[] = 'handle';

        return 0;
    }

    public function clear(mixed ...$arguments): void
    {
        $this->calls[] = 'clear';
    }
}
