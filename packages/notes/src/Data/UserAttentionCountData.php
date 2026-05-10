<?php

declare(strict_types=1);

namespace Capell\Notes\Data;

use Spatie\LaravelData\Data;

final class UserAttentionCountData extends Data
{
    public function __construct(
        public readonly int $assigned = 0,
        public readonly int $dueToday = 0,
        public readonly int $overdue = 0,
        public readonly int $mentions = 0,
    ) {}

    public function total(): int
    {
        return $this->assigned + $this->dueToday + $this->overdue + $this->mentions;
    }
}
