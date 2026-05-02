<?php

declare(strict_types=1);

namespace Capell\Mcp\Data;

use Spatie\LaravelData\Data;

final class AuthenticatedMcpClientData extends Data
{
    /**
     * @param  array<int, string>  $scopes
     */
    public function __construct(
        public readonly int $tokenId,
        public readonly string $name,
        public readonly array $scopes,
    ) {}

    public function can(string $scope): bool
    {
        return in_array($scope, $this->scopes, true) || in_array('*', $this->scopes, true);
    }
}
