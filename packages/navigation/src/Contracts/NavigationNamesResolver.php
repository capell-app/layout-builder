<?php

declare(strict_types=1);

namespace Capell\Navigation\Contracts;

interface NavigationNamesResolver
{
    /**
     * Resolve navigation names for the given site and language IDs.
     *
     * @param  array<int, int>  $languageIds
     * @return array<int, string>
     */
    public function resolve(?int $siteId, array $languageIds): array;
}
