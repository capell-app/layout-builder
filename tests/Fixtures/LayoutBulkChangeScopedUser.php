<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Collection;

final class LayoutBulkChangeScopedUser extends User
{
    /** @var array<int, list<int>> */
    public static array $assignedSiteIdsByUser = [];

    protected $table = 'users';

    public function isGlobalAdmin(): bool
    {
        return false;
    }

    public function getMorphClass(): string
    {
        return User::class;
    }

    /** @return Collection<int, int> */
    public function getAssignedSiteIds(): Collection
    {
        $key = $this->getKey();

        return is_numeric($key)
            ? collect(self::$assignedSiteIdsByUser[(int) $key] ?? [])
            : collect();
    }
}
